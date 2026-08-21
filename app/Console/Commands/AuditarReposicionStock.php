<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Contrasta las TRES fuentes que dejan rastro cuando se anula un comprobante:
 *
 *   1. documents.stock_restored       -> lo que se respondio en el dialogo
 *   2. inventory_adjustments          -> el ajuste que compensa el kardex
 *   3. stock_change_logs              -> el movimiento REAL de articles.stock
 *
 * La (3) es la prueba fisica: el ArticleObserver registra todo cambio de
 * articles.stock venga de donde venga, asi que si no hay entrada, la
 * mercaderia no volvio al inventario por mas que alguien recuerde lo contrario.
 */
class AuditarReposicionStock extends Command
{
    protected $signature = 'kardex:auditar-reposicion
        {referencia? : Comprobante a auditar, ej. BC01-15}
        {--articulo= : SKU o id de articulo: su historial completo de stock}
        {--dia= : Audita todas las anulaciones de esa fecha (Y-m-d)}';

    protected $description = 'Audita si un comprobante anulado repuso stock, contrastando lo declarado con los movimientos reales';

    /** Margen alrededor del comprobante donde buscar el movimiento de stock. */
    private const VENTANA_ANTES   = 10;
    private const VENTANA_DESPUES = 180;

    public function handle(): int
    {
        if ($sku = $this->option('articulo')) {
            return $this->historialArticulo($sku);
        }

        if ($dia = $this->option('dia')) {
            return $this->resumenDia($dia);
        }

        if ($referencia = $this->argument('referencia')) {
            return $this->auditarComprobante($referencia);
        }

        $this->error('Indica un comprobante (ej. BC01-15), --articulo=SKU o --dia=Y-m-d.');

        return self::FAILURE;
    }

    // ---------------------------------------------------------------- comprobante

    private function auditarComprobante(string $referencia): int
    {
        $documento = $this->buscarDocumento($referencia);

        if (! $documento) {
            $this->error("No se encontro el comprobante {$referencia}.");

            return self::FAILURE;
        }

        $a = $this->analizar($documento);

        $this->newLine();
        $this->line("<options=bold>Comprobante {$a['numero']}</>  ({$a['tipo']}, id {$documento->id})");
        $this->line("  Emitido        {$documento->created_at} por {$a['usuario']}");

        if ($a['afectado']) {
            $this->line("  Anula          {$a['afectado']}");
        }

        if ($a['venta']) {
            $this->line("  Venta          {$a['venta']}");
        }

        $this->line('  Declarado      stock_restored = <options=bold>' . ($a['declara_repuso'] ? 'SI' : 'NO')
            . '</> → "' . ($a['declara_repuso'] ? 'con reposicion' : 'sin reposicion') . ' de stock"');

        $this->newLine();
        $this->line('<options=bold>Articulos</>');
        $this->table(
            ['SKU', 'Articulo', 'Cant.', 'Stock hoy', 'Kardex'],
            collect($a['articulos'])->map(fn ($x) => [
                $x['sku'],
                mb_strimwidth($x['title'], 0, 44, '...'),
                $x['cantidad'],
                $x['stock'],
                $x['kardex'],
            ])->all()
        );

        $this->line('<options=bold>Evidencia</>');
        $this->line('  [1] documents.stock_restored ............ ' . ($a['declara_repuso'] ? 'repuso' : 'NO repuso'));
        $this->line("  [2] ajustes 'anulacion:sin_reposicion' .. " . count($a['ajustes'])
            . ' (esperados: ' . $a['ajustes_esperados'] . ')');

        foreach ($a['ajustes'] as $aj) {
            $this->line("        #{$aj->id}  delta {$aj->delta}  {$aj->created_at}  {$aj->reason}");
        }

        $this->line('  [3] movimientos reales de articles.stock en la ventana del comprobante:');

        if (empty($a['movimientos'])) {
            $this->line('        <fg=yellow>(ninguno)</>');
        } else {
            foreach ($a['movimientos'] as $m) {
                $this->line("        {$m->created_at}  {$m->old_stock} → {$m->new_stock}  (delta {$m->delta})  {$m->context}");
            }
        }

        $this->newLine();

        if ($a['consistente']) {
            $this->line('<fg=green;options=bold>VEREDICTO: CONSISTENTE</> — '
                . ($a['declara_repuso'] ? 'el stock SI se repuso.' : 'el stock NO se repuso.'));
        } else {
            $this->line('<fg=red;options=bold>VEREDICTO: INCONSISTENTE</>');
        }

        foreach ($a['notas'] as $nota) {
            $this->line('  ' . $nota);
        }

        $this->newLine();

        return $a['consistente'] ? self::SUCCESS : self::FAILURE;
    }

    // ---------------------------------------------------------------------- dia

    private function resumenDia(string $dia): int
    {
        try {
            $fecha = Carbon::parse($dia)->toDateString();
        } catch (\Throwable) {
            $this->error("Fecha invalida: {$dia}. Usa Y-m-d.");

            return self::FAILURE;
        }

        $documentos = Document::whereDate('created_at', $fecha)
            ->where(fn ($q) => $q->where('serie', 'LIKE', 'BC%')
                ->orWhere('serie', 'LIKE', 'FC%')
                ->orWhere('status', 'anulado'))
            ->orderBy('created_at')
            ->get();

        if ($documentos->isEmpty()) {
            $this->info("No hay anulaciones registradas el {$fecha}.");

            return self::SUCCESS;
        }

        $filas = [];
        $inconsistentes = 0;

        foreach ($documentos as $documento) {
            $a = $this->analizar($documento);

            if (! $a['consistente']) {
                $inconsistentes++;
            }

            $filas[] = [
                $a['numero'],
                substr((string) $documento->created_at, 11, 8),
                mb_strimwidth($a['usuario'], 0, 18, '...'),
                $a['declara_repuso'] ? 'SI' : 'NO',
                count($a['ajustes']),
                count($a['movimientos']),
                $a['unidades'],
                $a['consistente'] ? 'ok' : 'REVISAR',
            ];
        }

        $this->newLine();
        $this->line("<options=bold>Anulaciones del {$fecha}</>");
        $this->table(['Comprobante', 'Hora', 'Emitido por', 'Repuso', 'Ajustes', 'Movim.', 'Unid.', ''], $filas);

        $sinReposicion = collect($filas)->where(3, 'NO')->count();
        $unidadesSinReponer = collect($filas)->where(3, 'NO')->sum(6);

        $this->line("  Total: {$documentos->count()} comprobantes · {$sinReposicion} sin reposicion "
            . "({$unidadesSinReponer} unidades que NO volvieron al inventario)");

        if ($inconsistentes > 0) {
            $this->line("  <fg=red;options=bold>{$inconsistentes} con evidencia contradictoria: revisalos uno por uno.</>");
        }

        $this->newLine();

        return $inconsistentes === 0 ? self::SUCCESS : self::FAILURE;
    }

    // ----------------------------------------------------------------- articulo

    private function historialArticulo(string $referencia): int
    {
        $articulo = DB::table('articles')
            ->where('sku', $referencia)
            ->orWhere('id', is_numeric($referencia) ? (int) $referencia : 0)
            ->first();

        if (! $articulo) {
            $this->error("No se encontro el articulo {$referencia}.");

            return self::FAILURE;
        }

        $kardex = DB::table('v_kardex_stock')->where('article_id', $articulo->id)->value('kardex_stock');

        $this->newLine();
        $this->line("<options=bold>{$articulo->sku}</>  {$articulo->title}");
        $this->line("  Stock almacen {$articulo->stock} · kardex {$kardex} · diferencia "
            . ((int) $articulo->stock - (int) $kardex));

        $movimientos = DB::table('stock_change_logs as l')
            ->leftJoin('users as u', 'u.id', '=', 'l.user_id')
            ->where('l.article_id', $articulo->id)
            ->orderByDesc('l.id')
            ->limit(30)
            ->get(['l.created_at', 'l.old_stock', 'l.new_stock', 'l.delta', 'l.context', 'u.name as usuario']);

        $this->newLine();
        $this->line('<options=bold>Movimientos reales de stock (ultimos 30)</>');
        $this->table(
            ['Fecha', 'De', 'A', 'Delta', 'Origen', 'Usuario'],
            $movimientos->map(fn ($m) => [
                $m->created_at, $m->old_stock, $m->new_stock, $m->delta,
                mb_strimwidth((string) $m->context, 0, 46, '...'), $m->usuario ?? '-',
            ])->all()
        );

        $ajustes = DB::table('inventory_adjustments as ia')
            ->leftJoin('users as u', 'u.id', '=', 'ia.created_by')
            ->where('ia.article_id', $articulo->id)
            ->orderByDesc('ia.id')
            ->get(['ia.created_at', 'ia.delta', 'ia.source', 'ia.reason', 'u.name as usuario']);

        if ($ajustes->isNotEmpty()) {
            $this->line('<options=bold>Ajustes de inventario</>');
            $this->table(
                ['Fecha', 'Delta', 'Origen', 'Motivo', 'Usuario'],
                $ajustes->map(fn ($a) => [
                    $a->created_at, $a->delta, $a->source,
                    mb_strimwidth((string) $a->reason, 0, 46, '...'), $a->usuario ?? '-',
                ])->all()
            );
        }

        $this->newLine();

        return self::SUCCESS;
    }

    // ------------------------------------------------------------------ analisis

    private function buscarDocumento(string $referencia): ?Document
    {
        if (! preg_match('/^([A-Za-z0-9]+)-0*(\d+)$/', trim($referencia), $m)) {
            return null;
        }

        return Document::where('serie', strtoupper($m[1]))
            ->whereRaw('CAST(correlative AS UNSIGNED) = ?', [(int) $m[2]])
            ->first();
    }

    /** Reune las tres fuentes y decide si se contradicen. */
    private function analizar(Document $documento): array
    {
        $numero    = $documento->serie . '-' . $documento->correlative;
        $esNota    = str_starts_with($documento->serie, 'FC') || str_starts_with($documento->serie, 'BC');
        $declara   = (bool) $documento->stock_restored;

        // Quien EMITIO la anulacion (documents.user_id), no quien hizo la venta.
        $usuario = DB::table('users')->where('id', $documento->user_id)->value('name') ?? '-';

        // Los articulos son los de la nota; en una baja, los de la venta anulada.
        $items = DB::table('document_details as dd')
            ->join('articles as a', 'a.id', '=', 'dd.article_id')
            ->where('dd.document_id', $documento->id)
            ->get(['a.id', 'a.sku', 'a.title', 'a.stock', 'dd.quantity']);

        if ($items->isEmpty() && $documento->sale_id) {
            $items = DB::table('sale_details as sd')
                ->join('articles as a', 'a.id', '=', 'sd.article_id')
                ->where('sd.sale_id', $documento->sale_id)
                ->whereNull('sd.deleted_at')
                ->get(['a.id', 'a.sku', 'a.title', 'a.stock', 'sd.quantity']);
        }

        $kardex = DB::table('v_kardex_stock')
            ->whereIn('article_id', $items->pluck('id'))
            ->pluck('kardex_stock', 'article_id');

        $articulos = $items->map(fn ($i) => [
            'id'       => $i->id,
            'sku'      => $i->sku,
            'title'    => $i->title,
            'cantidad' => (int) $i->quantity,
            'stock'    => (int) $i->stock,
            'kardex'   => (int) ($kardex[$i->id] ?? 0),
        ])->all();

        // [2] Ajustes. El LIKE se afina en PHP para que BC01-1 no traiga BC01-15.
        $ajustes = DB::table('inventory_adjustments')
            ->where('source', 'anulacion:sin_reposicion')
            ->where('reason', 'LIKE', "%{$numero}%")
            ->orderBy('id')
            ->get()
            ->filter(fn ($a) => (bool) preg_match('/' . preg_quote($numero, '/') . '(?!\d)/', (string) $a->reason))
            ->values()
            ->all();

        // [3] Movimientos reales dentro de la ventana del comprobante.
        $desde = Carbon::parse($documento->created_at)->subSeconds(self::VENTANA_ANTES);
        $hasta = Carbon::parse($documento->created_at)->addSeconds(self::VENTANA_DESPUES);

        $movimientos = DB::table('stock_change_logs')
            ->whereIn('article_id', $items->pluck('id'))
            ->whereBetween('created_at', [$desde, $hasta])
            ->where('delta', '>', 0)
            ->where(fn ($q) => $q->where('context', 'LIKE', '%CreditNote%')
                ->orWhere('context', 'LIKE', '%TableDocuments%'))
            ->orderBy('id')
            ->get()
            ->all();

        // La venta solo genera ajuste si ESTE comprobante fue el que la anulo:
        // si ya estaba anulada, el stock volvio antes y no hay nada que compensar.
        $venta = $documento->sale_id ? DB::table('sales')->where('id', $documento->sale_id)->first() : null;
        $anuladaPorEste = $venta && str_contains((string) $venta->deletion_reason, $numero);

        $unidades = collect($articulos)->sum('cantidad');
        $repuesto = collect($movimientos)->sum('delta');

        $ajustesEsperados = (! $declara && $anuladaPorEste) ? count($articulos) : 0;

        $notas = [];

        if ($declara) {
            $consistente = $repuesto > 0;
            $notas[] = $consistente
                ? "El observador de stock registra +{$repuesto} unidad(es) al momento de emitirlo."
                : 'Dice haber repuesto, pero no hay ningun ingreso de stock registrado en esa ventana.';
        } else {
            $consistente = count($movimientos) === 0;
            $notas[] = $consistente
                ? 'No hay ningun ingreso de stock registrado: la mercaderia no volvio al inventario.'
                : "Dice NO haber repuesto, pero hay +{$repuesto} unidad(es) registradas en esa ventana.";
        }

        if (count($ajustes) !== $ajustesEsperados) {
            $consistente = false;
            $notas[] = 'Los ajustes de kardex no cuadran: hay ' . count($ajustes)
                . " y se esperaban {$ajustesEsperados}.";
        }

        if (! $declara && ! $anuladaPorEste && $venta) {
            $notas[] = 'La venta ya estaba anulada antes: el stock se resolvio en esa anulacion, no aqui.';
        }

        $afectado = null;
        if ($documento->affected_document_id) {
            $af = DB::table('documents')->where('id', $documento->affected_document_id)->first();
            $afectado = $af ? "{$af->serie}-{$af->correlative} (emitida {$af->date})" : null;
        }

        return [
            'numero'            => $numero,
            'tipo'              => $esNota ? 'nota de credito' : 'comprobante dado de baja',
            'usuario'           => $usuario,
            'afectado'          => $afectado,
            'venta'             => $venta ? $venta->number . ' · ' . ($venta->deletion_reason ?: 'sin motivo') : null,
            'declara_repuso'    => $declara,
            'articulos'         => $articulos,
            'unidades'          => $unidades,
            'ajustes'           => $ajustes,
            'ajustes_esperados' => $ajustesEsperados,
            'movimientos'       => $movimientos,
            'consistente'       => $consistente,
            'notas'             => $notas,
        ];
    }
}
