<?php

namespace App\Console\Commands;

use App\Models\InventoryAdjustment;
use App\Models\Sale;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reactiva las ventas que quedaron anuladas por anular su comprobante SIN
 * reponer stock (nota de credito o baja respondida con "No reponer").
 *
 * Con la regla anterior esa anulacion cerraba la venta y registraba un ajuste
 * negativo en el kardex ('anulacion:sin_reposicion') para compensar la salida
 * que dejaba de contar. Con la regla actual la venta sigue activa, porque la
 * mercaderia se quedo con el cliente, asi que ese ajuste sobra. Este comando
 * deshace ambas cosas a la vez y el kardex queda igual: vuelve a contar la
 * salida de la venta (-q) y desaparece el ajuste (-q). articles.stock no se
 * toca.
 *
 * Solo entra una venta cuando la evidencia es completa: un ajuste por cada
 * detalle con la misma cantidad, y ningun ingreso real de stock registrado al
 * momento de la anulacion. El resto se lista para revisar a mano.
 */
class ReactivarVentasSinReposicion extends Command
{
    protected $signature = 'ventas:reactivar-sin-reposicion
                            {--dry-run : Solo mostrar qué haría, sin escribir nada}
                            {--force : No pedir confirmación (para ejecución vía ruta web)}
                            {--user= : ID del usuario que ejecuta (queda en el log)}';

    protected $description = 'Reactiva las ventas anuladas por un comprobante sin reposición de stock y retira su ajuste de kardex';

    /**
     * Ventana alrededor de la anulación (sales.deletion_date, fijada en la misma
     * transacción que cualquier reposición) donde buscar un ingreso real de
     * stock. Misma asimetría que kardex:auditar-reposicion.
     */
    private const VENTANA_ANTES   = 10;
    private const VENTANA_DESPUES = 180;

    private const ESTADOS = [
        Sale::SALE_APPROVED => 'Completado',
        Sale::SALE_RETURN   => 'Devolución pendiente',
    ];

    public function handle(): int
    {
        $evaluadas = $this->candidatas()->map(fn (Sale $venta) => $this->evaluar($venta));

        if ($evaluadas->isEmpty()) {
            $this->info('No hay ventas anuladas por comprobante pendientes de revisar.');

            return self::SUCCESS;
        }

        [$elegibles, $descartadas] = $evaluadas->partition(fn ($e) => $e['motivos'] === []);

        $this->newLine();
        $this->line('<options=bold>Ventas a reactivar</> (la venta vuelve a contar; se retira su ajuste de kardex)');

        if ($elegibles->isEmpty()) {
            $this->line('  (ninguna)');
        } else {
            $this->table(
                ['ID', 'N. orden', 'Cliente', 'Anulada por', 'Fecha', 'Unid.', 'Ajustes', 'Nuevo estado'],
                $elegibles->map(fn ($e) => [
                    $e['venta']->id,
                    $e['venta']->number,
                    mb_strimwidth((string) ($e['venta']->client->name ?? '-'), 0, 24, '...'),
                    $e['numero'],
                    $e['venta']->deletion_date,
                    $e['unidades'],
                    $e['ajustes']->count(),
                    self::ESTADOS[$e['nuevo_status']],
                ])->all()
            );
        }

        if ($descartadas->isNotEmpty()) {
            $this->newLine();
            $this->line('<options=bold>Se mantienen anuladas</>');
            $this->table(
                ['ID', 'N. orden', 'Anulada por', 'Motivo'],
                $descartadas->map(fn ($e) => [
                    $e['venta']->id,
                    $e['venta']->number,
                    $e['numero'] ?? mb_strimwidth((string) $e['venta']->deletion_reason, 0, 30, '...'),
                    implode('; ', $e['motivos']),
                ])->all()
            );
        }

        $this->newLine();
        $this->line("  Total: {$evaluadas->count()} ventas anuladas por comprobante · {$elegibles->count()} a reactivar · {$descartadas->count()} se mantienen.");

        if ($elegibles->isEmpty()) {
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry-run: no se escribió nada.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Se reactivarán {$elegibles->count()} ventas y se eliminarán sus ajustes de kardex. ¿Continuar?")) {
            $this->warn('Cancelado.');

            return self::SUCCESS;
        }

        $userId = $this->option('user');

        DB::transaction(function () use ($elegibles, $userId) {
            foreach ($elegibles as $e) {
                // Constancia en el log: la venta pierde su motivo de anulación y
                // los ajustes desaparecen de la tabla.
                Log::warning('Venta reactivada: su comprobante se anuló sin reposición de stock', [
                    'sale'               => $e['venta']->only(['id', 'number', 'status', 'deletion_date', 'deletion_reason']),
                    'nuevo_status'       => $e['nuevo_status'],
                    'ajustes_eliminados' => $e['ajustes']->map(fn ($a) => (array) $a)->all(),
                    'ejecutado_por'      => $userId,
                ]);

                // Uno por uno para que cada ajuste retirado quede en la auditoria
                InventoryAdjustment::whereIn('id', $e['ajustes']->pluck('id'))->get()->each->delete();

                $e['venta']->update([
                    'status'          => $e['nuevo_status'],
                    'deletion_date'   => null,
                    'deletion_reason' => null,
                ]);
            }
        });

        $ajustes = $elegibles->sum(fn ($e) => $e['ajustes']->count());
        $this->info("Listo: {$elegibles->count()} ventas reactivadas y {$ajustes} ajustes de kardex retirados.");

        return self::SUCCESS;
    }

    /** Ventas anuladas cuyo motivo registró un comprobante (baja o nota de crédito). */
    private function candidatas(): Collection
    {
        return Sale::query()
            ->with('client:id,name')
            ->anuladaPorComprobante()
            ->orderBy('id')
            ->get();
    }

    /** Decide si la venta se reactiva y, si no, por qué. */
    private function evaluar(Sale $venta): array
    {
        preg_match(
            '/^(Baja del comprobante|Nota de crédito|Devolución con nota de crédito) ([A-Z0-9]+-\d+)$/u',
            (string) $venta->deletion_reason,
            $m
        );

        $numero  = $m[2] ?? null;
        $motivos = [];

        // Una devolución pendiente vuelve a serlo; lo demás, a completado (el
        // estado previo no se guarda y es el habitual en ventas con comprobante).
        $nuevoStatus = ($m[1] ?? '') === 'Devolución con nota de crédito' ? Sale::SALE_RETURN : Sale::SALE_APPROVED;

        $detalles = DB::table('sale_details')
            ->where('sale_id', $venta->id)
            ->whereNull('deleted_at')
            ->get(['article_id', 'quantity']);

        // Ajustes de ESTE comprobante (BC01-1 no debe traer los de BC01-15)
        $ajustes = collect();

        if ($numero) {
            $ajustes = DB::table('inventory_adjustments')
                ->where('source', 'anulacion:sin_reposicion')
                ->where('reason', 'LIKE', "%{$numero}%")
                ->orderBy('id')
                ->get()
                ->filter(fn ($a) => (bool) preg_match('/' . preg_quote($numero, '/') . '(?!\d)/', (string) $a->reason))
                ->values();

            if ($ajustes->isEmpty()) {
                $motivos[] = 'sin ajuste de kardex: el stock se repuso al anular, la venta sí se revirtió';
            }
        } else {
            $motivos[] = 'motivo de anulación no reconocido';
        }

        // Cada detalle debe tener su ajuste con la misma cantidad, sin sobrar ninguno
        if ($ajustes->isNotEmpty()) {
            $esperado = $detalles->map(fn ($d) => $d->article_id . ':' . -(int) $d->quantity)->sort()->values()->all();
            $real     = $ajustes->map(fn ($a) => $a->article_id . ':' . (int) $a->delta)->sort()->values()->all();

            if ($esperado !== $real) {
                $motivos[] = 'los ajustes no coinciden con los detalles de la venta';
            }
        }

        // Prueba física: si al anular hubo un ingreso real de stock, la
        // mercadería sí volvió por más que el flag diga lo contrario.
        if ($venta->deletion_date && $detalles->isNotEmpty()) {
            $ref = Carbon::parse($venta->deletion_date);

            $ingresos = DB::table('stock_change_logs')
                ->whereIn('article_id', $detalles->pluck('article_id'))
                ->whereBetween('created_at', [$ref->copy()->subSeconds(self::VENTANA_ANTES), $ref->copy()->addSeconds(self::VENTANA_DESPUES)])
                ->where('delta', '>', 0)
                ->where(fn ($q) => $q->where('context', 'LIKE', '%CreditNote%')
                    ->orWhere('context', 'LIKE', '%TableDocuments%'))
                ->count();

            if ($ingresos > 0) {
                $motivos[] = "hubo {$ingresos} ingreso(s) real(es) de stock al anular: revisar a mano";
            }
        }

        return [
            'venta'        => $venta,
            'numero'       => $numero,
            'nuevo_status' => $nuevoStatus,
            'ajustes'      => $ajustes,
            'unidades'     => (int) $detalles->sum('quantity'),
            'motivos'      => $motivos,
        ];
    }
}
