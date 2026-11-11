<?php

namespace App\Console\Commands;

use App\Livewire\Documents\TableDocuments;
use App\Models\Client;
use App\Models\Document;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Cuadra el registro de ventas de SIRE (el Excel que manda el contador) con
 * los comprobantes del sistema y registra los que se emitieron a mano en el
 * portal SOL de SUNAT (series E001/EB01, ver Document::PREFIJO_PORTAL).
 *
 * - Los del portal que falten se crean como aceptados, sin XML ni CDR. Si el
 *   cliente no existe se crea (Lima/Lima/Lima, corregible en /clients).
 * - Un comprobante del portal se enlaza a una venta solo si hay UNA candidata:
 *   mismo cliente, no anulada, de los 30 dias previos y sin comprobante
 *   vigente (p. ej. la factura del sistema fue rechazada). Si no, queda sin venta.
 * - Una NC del portal se enlaza a su comprobante afectado, toma su venta y lo
 *   deja en status 'nota_credito'. No repone stock ni toca la venta.
 * - Los demas comprobantes del Excel solo se comparan: no se modifica nada.
 *
 * Es idempotente: lo ya registrado se salta, asi que se puede volver a correr
 * con el mismo archivo o con uno mas completo.
 */
class RegistrarComprobantesPortal extends Command
{
    protected $signature = 'sire:registrar-portal
                            {archivo : Excel del registro de ventas de SIRE}
                            {--dry-run : Solo mostrar qué haría, sin escribir nada}
                            {--force : No pedir confirmación (para ejecución vía ruta web)}
                            {--user= : ID del usuario que registra (queda en documents.user_id)}';

    protected $description = 'Cuadra el registro de ventas de SIRE con el sistema y registra los comprobantes emitidos en el portal SUNAT';

    /** Encabezado del Excel (normalizado: minusculas, sin tildes) => campo. Se busca por inicio de texto. */
    private const COLUMNAS = [
        'periodo'    => 'periodo',
        'fecha'      => 'fecha de emision',
        'tipo'       => 'tipo cp/doc',
        'serie'      => 'serie del cdp',
        'numero'     => 'nro cp o doc',
        'tipo_doc'   => 'tipo doc identidad',
        'nro_doc'    => 'nro doc identidad',
        'nombre'     => 'apellidos nombres',
        'igv'        => 'igv / ipm',
        'total'      => 'total cp',
        'moneda'     => 'moneda',
        'serie_mod'  => 'serie cp modificado',
        'numero_mod' => 'nro cp modificado',
    ];

    /** Tipo de comprobante de SUNAT (catalogo 01). */
    private const FACTURA = 1;
    private const BOLETA = 3;
    private const NOTA_CREDITO = 7;

    /** Tipo de documento de identidad de SUNAT (catalogo 06) => clients.document_type y largo. */
    private const TIPOS_DOC = [
        '1' => ['tipo' => 'DNI', 'digitos' => 8],
        '4' => ['tipo' => 'CE', 'digitos' => null],
        '6' => ['tipo' => 'RUC', 'digitos' => 11],
    ];

    /** Ubicacion de los clientes creados desde el Excel: LIMA / LIMA / LIMA. */
    private const UBICACION = ['department_id' => 15, 'province_id' => 127, 'district_id' => 1251];

    private const DIAS_VENTA = 30;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        try {
            $filas = $this->leerExcel((string) $this->argument('archivo'));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if ($filas === []) {
            $this->error('El Excel no tiene comprobantes.');
            return self::FAILURE;
        }

        $usuario = null;
        if (! $dryRun) {
            $usuario = User::find($this->option('user'));
            if (! $usuario) {
                $this->error('Indique con --user= el ID del usuario que registra (queda como emisor de los comprobantes).');
                return self::FAILURE;
            }
        }

        $existentes = $this->documentosExistentes($filas);
        $plan = $this->planificar($filas, $existentes);

        $this->mostrarPortal($plan);
        $this->mostrarCruce($filas, $existentes);

        $porRegistrar = array_filter($plan, fn ($p) => $p['accion'] === 'registrar');

        if ($dryRun) {
            $this->mostrarTotales($filas, $plan, false);
            $this->newLine();
            $this->line('<options=bold>Vista previa: no se escribió nada.</>');
            return self::SUCCESS;
        }

        if ($porRegistrar === []) {
            $this->mostrarTotales($filas, $plan, true);
            $this->newLine();
            $this->info('No hay comprobantes del portal por registrar.');
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('¿Registrar ' . count($porRegistrar) . ' comprobante(s) del portal SUNAT?')) {
            $this->line('Cancelado.');
            return self::SUCCESS;
        }

        $creados = $this->registrar($plan, $usuario);

        Log::info('sire:registrar-portal: comprobantes del portal registrados', [
            'archivo' => basename((string) $this->argument('archivo')),
            'user_id' => $usuario->id,
            'creados' => $creados,
        ]);

        $this->mostrarTotales($filas, $plan, true);
        $this->newLine();
        $this->info('Registrados: ' . implode(', ', $creados));

        return self::SUCCESS;
    }

    /* ------------------------------------------------------------------ */
    /* Lectura del Excel                                                    */
    /* ------------------------------------------------------------------ */

    private function leerExcel(string $ruta): array
    {
        if (! is_file($ruta)) {
            throw new \RuntimeException("No existe el archivo {$ruta}.");
        }

        try {
            $lector = IOFactory::createReaderForFile($ruta);
            $lector->setReadDataOnly(true);
            $hoja = $lector->load($ruta)->getActiveSheet()->toArray(null, true, false, false);
        } catch (\Throwable $e) {
            throw new \RuntimeException('No se pudo leer el Excel: ' . $e->getMessage());
        }

        $encabezados = array_map(fn ($h) => $this->normalizar((string) $h), array_shift($hoja) ?? []);
        $indices = [];

        foreach (self::COLUMNAS as $campo => $buscado) {
            foreach ($encabezados as $i => $h) {
                if (str_starts_with($h, $buscado)) {
                    $indices[$campo] = $i;
                    break;
                }
            }
        }

        if ($faltan = array_diff(array_keys(self::COLUMNAS), array_keys($indices))) {
            throw new \RuntimeException('No parece el registro de ventas de SIRE: faltan las columnas ' . implode(', ', $faltan) . '.');
        }

        $filas = [];

        foreach ($hoja as $n => $fila) {
            $serie = strtoupper(trim((string) ($fila[$indices['serie']] ?? '')));

            // Filas vacias y la de TOTAL del pie
            if ($serie === '') {
                continue;
            }

            $tipoDoc = trim((string) $fila[$indices['tipo_doc']]);
            $serieMod = strtoupper(trim((string) ($fila[$indices['serie_mod']] ?? '')));

            $filas[] = [
                'fila'     => $n + 2,
                'periodo'  => trim((string) $fila[$indices['periodo']]),
                'fecha'    => $this->fecha($fila[$indices['fecha']]),
                'tipo'     => (int) $fila[$indices['tipo']],
                'serie'    => $serie,
                'numero'   => (int) $fila[$indices['numero']],
                'clave'    => $serie . '-' . (int) $fila[$indices['numero']],
                'tipo_doc' => $tipoDoc,
                // Excel guarda el numero de documento como numero y pierde los ceros a la izquierda
                'nro_doc'  => $this->numeroDocumento($tipoDoc, $fila[$indices['nro_doc']]),
                'nombre'   => Client::normalizarNombre((string) $fila[$indices['nombre']]),
                // SIRE trae las notas de credito en negativo; el sistema las guarda en positivo
                'igv'      => round(abs((float) $fila[$indices['igv']]), 2),
                'total'    => round(abs((float) $fila[$indices['total']]), 2),
                'signo'    => (float) $fila[$indices['total']] < 0 ? -1 : 1,
                'moneda'   => trim((string) $fila[$indices['moneda']]) ?: 'PEN',
                'mod'      => $serieMod !== '' ? $serieMod . '-' . (int) $fila[$indices['numero_mod']] : null,
            ];
        }

        return $filas;
    }

    private function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);

        return preg_replace('/\s+/', ' ', $texto);
    }

    private function fecha($valor): string
    {
        if (is_numeric($valor)) {
            return ExcelDate::excelToDateTimeObject((float) $valor)->format('Y-m-d');
        }

        $valor = trim((string) $valor);

        foreach (['d/m/Y', 'Y-m-d'] as $formato) {
            try {
                return Carbon::createFromFormat($formato, $valor)->format('Y-m-d');
            } catch (\Throwable) {
            }
        }

        throw new \RuntimeException("Fecha de emisión no reconocida: \"{$valor}\".");
    }

    private function numeroDocumento(string $tipoDoc, $valor): string
    {
        $numero = (string) Client::normalizarNumeroDocumento(is_float($valor) ? (string) (int) $valor : (string) $valor);
        $digitos = self::TIPOS_DOC[$tipoDoc]['digitos'] ?? null;

        return $digitos && ctype_digit($numero) ? str_pad($numero, $digitos, '0', STR_PAD_LEFT) : $numero;
    }

    /* ------------------------------------------------------------------ */
    /* Plan                                                                 */
    /* ------------------------------------------------------------------ */

    /** Documentos del sistema con la misma serie-numero que alguna fila, por clave. */
    private function documentosExistentes(array $filas): array
    {
        $existentes = [];

        foreach (collect($filas)->groupBy('serie') as $serie => $grupo) {
            $docs = Document::with('client:id,document_number')
                ->where('serie', $serie)
                ->whereIn('correlative', $grupo->pluck('numero')->all())
                ->get();

            foreach ($docs as $doc) {
                $existentes["{$doc->serie}-{$doc->correlative}"][] = $doc;
            }
        }

        // Comprobantes afectados por NC del portal que no estan en el Excel (de otro mes)
        $mods = collect($filas)->pluck('mod')->filter()->reject(fn ($m) => isset($existentes[$m]));
        foreach ($mods as $mod) {
            [$serie, $numero] = explode('-', $mod, 2);
            foreach (Document::where('serie', $serie)->where('correlative', (int) $numero)->get() as $doc) {
                $existentes[$mod][] = $doc;
            }
        }

        return $existentes;
    }

    private function planificar(array $filas, array $existentes): array
    {
        $plan = [];
        $clientesNuevos = [];
        $ventasTomadas = [];

        // Primero los comprobantes y despues las NC: una NC puede anular un
        // comprobante del portal que se registra en esta misma corrida.
        $portal = collect($filas)
            ->filter(fn ($f) => str_starts_with($f['serie'], Document::PREFIJO_PORTAL))
            ->sortBy(fn ($f) => $f['tipo'] === self::NOTA_CREDITO ? 1 : 0)
            ->values();

        foreach ($portal as $f) {
            $p = $f + ['accion' => 'registrar', 'motivo' => null, 'cliente' => null, 'cliente_nuevo' => false,
                       'venta' => null, 'ventas_posibles' => [], 'afectado' => null, 'document_type' => null];

            if (isset($existentes[$f['clave']])) {
                $plan[$f['clave']] = ['accion' => 'ya registrado'] + $p;
                continue;
            }

            if (! in_array($f['tipo'], [self::FACTURA, self::BOLETA, self::NOTA_CREDITO], true)) {
                $plan[$f['clave']] = ['accion' => 'error', 'motivo' => "tipo de comprobante {$f['tipo']} no soportado"] + $p;
                continue;
            }

            // Cliente: el que ya existe con ese numero, o uno nuevo con los datos de SIRE
            $cliente = self::clientesConNumero($f['nro_doc'])
                ->orderByRaw('deleted_at IS NOT NULL')->orderBy('id')->first();

            if ($cliente) {
                $p['cliente'] = $cliente;
            } elseif (isset($clientesNuevos[$f['nro_doc']]) || isset(self::TIPOS_DOC[$f['tipo_doc']])) {
                $p['cliente_nuevo'] = true;
                $clientesNuevos[$f['nro_doc']] = true;
            } else {
                $plan[$f['clave']] = ['accion' => 'error', 'motivo' => "cliente {$f['nro_doc']} con tipo de documento {$f['tipo_doc']}: regístrelo a mano en /clients"] + $p;
                continue;
            }

            if ($f['tipo'] === self::NOTA_CREDITO) {
                $afectado = $f['mod'] ? ($existentes[$f['mod']][0] ?? null) : null;
                $afectadoPlan = $f['mod'] && ($plan[$f['mod']]['accion'] ?? null) === 'registrar' ? $plan[$f['mod']] : null;

                if (! $afectado && ! $afectadoPlan) {
                    $plan[$f['clave']] = ['accion' => 'error', 'motivo' => 'no se encuentra el comprobante afectado ' . ($f['mod'] ?? '(vacío)')] + $p;
                    continue;
                }

                if ($afectado && $afectado->esNotaCredito()) {
                    $plan[$f['clave']] = ['accion' => 'error', 'motivo' => "el comprobante afectado {$f['mod']} es otra nota de crédito"] + $p;
                    continue;
                }

                $p['afectado'] = $f['mod'];
                $p['document_type'] = $afectado ? $afectado->document_type : $afectadoPlan['document_type'];
                $p['venta'] = $afectado ? $afectado->sale_id : $afectadoPlan['venta'];
            } else {
                $p['document_type'] = $f['tipo'] === self::FACTURA ? '1' : '2';

                if ($cliente) {
                    $candidatas = $this->ventasCandidatas($cliente, $f['fecha'])
                        ->reject(fn ($v) => isset($ventasTomadas[$v->id]))->values();

                    $p['ventas_posibles'] = $candidatas->all();

                    if ($candidatas->count() === 1) {
                        $p['venta'] = $candidatas[0]->id;
                        $ventasTomadas[$p['venta']] = true;
                    }
                }
            }

            $plan[$f['clave']] = $p;
        }

        return $plan;
    }

    /** Clientes con ese numero de documento, sin importar espacios ni ceros a la izquierda (hay DNI guardados sin el 0). */
    private static function clientesConNumero(string $numero)
    {
        return Client::whereRaw("TRIM(LEADING '0' FROM TRIM(document_number)) = ?", [ltrim(trim($numero), '0')]);
    }

    /** Ventas del cliente que podrian ser las de este comprobante (ver el comentario de la clase). */
    private function ventasCandidatas(Client $cliente, string $fecha)
    {
        $clientes = self::clientesConNumero((string) $cliente->document_number)->pluck('id');

        return Sale::with(['documents' => fn ($q) => $q->orderBy('id')])
            ->whereIn('client_id', $clientes)
            ->where('status', '!=', Sale::SALE_CANCELED)
            ->whereNull('deleted_at')
            ->whereBetween('date', [Carbon::parse($fecha)->subDays(self::DIAS_VENTA)->format('Y-m-d'), $fecha])
            ->whereDoesntHave('documents', fn ($q) => $q->vigente())
            ->orderBy('date')
            ->get();
    }

    /* ------------------------------------------------------------------ */
    /* Escritura                                                            */
    /* ------------------------------------------------------------------ */

    private function registrar(array $plan, User $usuario): array
    {
        $creados = [];
        $hoy = now()->format('d/m/Y');

        DB::transaction(function () use ($plan, $usuario, $hoy, &$creados) {
            $clientes = [];
            $documentos = [];

            foreach ($plan as $clave => $p) {
                if ($p['accion'] !== 'registrar') {
                    continue;
                }

                // Eloquent y no DB::table: asi queda en audit_logs
                $cliente = $p['cliente'] ?? ($clientes[$p['nro_doc']] ??= Client::create([
                    'name'            => $p['nombre'],
                    'document_number' => $p['nro_doc'],
                    'document_type'   => self::TIPOS_DOC[$p['tipo_doc']]['tipo'],
                ] + self::UBICACION));

                $afectado = $p['afectado']
                    ? ($documentos[$p['afectado']] ?? Document::where('serie', explode('-', $p['afectado'])[0])
                        ->where('correlative', (int) explode('-', $p['afectado'])[1])->first())
                    : null;

                $documentos[$clave] = Document::create([
                    'status'               => 'enviado',
                    'document_type'        => $p['document_type'],
                    'serie'                => $p['serie'],
                    'correlative'          => $p['numero'],
                    'date'                 => $p['fecha'],
                    'currency'             => $p['moneda'],
                    'payment_method'       => 'CONTADO',
                    'subtotal'             => round($p['total'] - $p['igv'], 2),
                    'tax'                  => $p['igv'],
                    'total'                => $p['total'],
                    'xml_path'             => '',
                    'cdr_path'             => '',
                    'status_sunat'         => 'aceptado',
                    'notes'                => ["Emitido en el portal SUNAT. Registrado el {$hoy} desde el registro de ventas de SIRE (periodo {$p['periodo']})."],
                    'sale_id'              => $afectado ? $afectado->sale_id : $p['venta'],
                    'affected_document_id' => $afectado?->id,
                    'stock_restored'       => false,
                    'client_id'            => $cliente->id,
                    'user_id'              => $usuario->id,
                ]);

                // Mismo efecto que una NC del sistema sobre el comprobante afectado,
                // pero sin reponer stock ni cerrar la venta.
                if ($afectado && ! in_array($afectado->status, ['anulado', 'nota_credito'], true)) {
                    $afectado->update(['status' => 'nota_credito']);
                }

                $creados[] = $clave;
            }
        });

        return $creados;
    }

    /* ------------------------------------------------------------------ */
    /* Reporte                                                              */
    /* ------------------------------------------------------------------ */

    private function mostrarPortal(array $plan): void
    {
        $this->newLine();
        $this->line('<options=bold>Comprobantes emitidos en el portal SUNAT</>');

        if ($plan === []) {
            $this->line('  (ninguno)');
            return;
        }

        $this->table(
            ['Comprobante', 'Fecha', 'Cliente', 'Total', 'Acción', 'Venta / detalle'],
            array_map(function ($p) {
                $nc = $p['tipo'] === self::NOTA_CREDITO;

                $detalle = match (true) {
                    $p['accion'] === 'error'         => $p['motivo'],
                    $p['accion'] === 'ya registrado' => '',
                    $nc                              => "anula {$p['afectado']}" . ($p['venta'] ? " (venta {$p['venta']})" : ''),
                    $p['venta'] !== null             => $this->describirVenta($p['ventas_posibles'][0]),
                    count($p['ventas_posibles']) > 1 => 'sin venta: hay ' . count($p['ventas_posibles']) . ' posibles ('
                        . collect($p['ventas_posibles'])->pluck('id')->implode(', ') . '), enlácela a mano',
                    default                          => 'sin venta en el sistema',
                };

                if ($p['cliente_nuevo'] && $p['accion'] === 'registrar') {
                    $detalle .= ($detalle ? '; ' : '') . 'cliente nuevo';
                }

                return [
                    $p['clave'],
                    Carbon::parse($p['fecha'])->format('d/m/Y'),
                    "{$p['nro_doc']} " . mb_strimwidth((string) $p['nombre'], 0, 34, '…'),
                    ($nc ? '-' : '') . number_format($p['total'], 2),
                    strtoupper($p['accion']),
                    $detalle,
                ];
            }, array_values($plan))
        );

        if (collect($plan)->contains(fn ($p) => $p['cliente_nuevo'] && $p['accion'] === 'registrar')) {
            $this->line('  Los clientes nuevos se crean con dirección LIMA / LIMA / LIMA: corríjala en /clients si hace falta.');
        }
    }

    private function describirVenta(Sale $venta): string
    {
        $docs = $venta->documents->map(fn ($d) => "{$d->serie}-{$d->correlative} {$d->status_sunat}")->implode(', ');

        return "venta {$venta->id} del " . Carbon::parse($venta->date)->format('d/m/Y')
            . ' (S/ ' . number_format((float) $venta->total, 2) . ($docs ? "; {$docs}" : '; sin comprobante') . ')';
    }

    /** Compara con el sistema los comprobantes del Excel que no son del portal, y al reves. */
    private function mostrarCruce(array $filas, array $existentes): void
    {
        $sistema = collect($filas)->reject(fn ($f) => str_starts_with($f['serie'], Document::PREFIJO_PORTAL));
        $faltan = [];
        $diferencias = [];

        foreach ($sistema as $f) {
            $docs = $existentes[$f['clave']] ?? [];

            if ($docs === []) {
                $faltan[] = [$f['clave'], Carbon::parse($f['fecha'])->format('d/m/Y'), "{$f['nro_doc']} {$f['nombre']}", number_format($f['signo'] * $f['total'], 2)];
                continue;
            }

            foreach ($docs as $d) {
                $dif = [];
                if (abs(round((float) $d->total, 2) - $f['total']) > 0.009) {
                    $dif[] = 'monto ' . number_format((float) $d->total, 2) . ' en el sistema';
                }
                if (Carbon::parse($d->date)->format('Y-m-d') !== $f['fecha']) {
                    $dif[] = 'fecha ' . Carbon::parse($d->date)->format('d/m/Y') . ' en el sistema';
                }
                if (ltrim(trim((string) $d->client?->document_number), '0') !== ltrim($f['nro_doc'], '0')) {
                    $dif[] = 'cliente ' . trim((string) $d->client?->document_number) . ' en el sistema';
                }
                if ($d->status_sunat !== 'aceptado') {
                    $dif[] = "{$d->status_sunat} en el sistema";
                }
                if (count($docs) > 1) {
                    $dif[] = count($docs) . ' registros con ese número';
                }
                if ($dif) {
                    $diferencias[] = [$f['clave'], "documento {$d->id}", implode('; ', $dif)];
                }
            }
        }

        // Aceptados en el sistema, en los meses del Excel, que SIRE no tiene
        $claves = array_flip(array_column($filas, 'clave'));
        $sobran = [];

        foreach (collect($filas)->pluck('periodo')->unique() as $periodo) {
            if (! preg_match('/^(\d{4})(\d{2})$/', $periodo, $m)) {
                continue;
            }

            $docs = Document::with('client:id,name')
                ->where('status_sunat', 'aceptado')
                ->whereYear('date', $m[1])->whereMonth('date', $m[2])
                ->orderBy('date')->orderBy('id')
                ->get();

            foreach ($docs as $d) {
                if (! isset($claves["{$d->serie}-{$d->correlative}"])) {
                    $sobran[] = ["{$d->serie}-{$d->correlative}", Carbon::parse($d->date)->format('d/m/Y'), $d->client?->name, number_format((float) $d->total, 2), "documento {$d->id}, venta " . ($d->sale_id ?? '—')];
                }
            }
        }

        $this->newLine();
        $this->line('<options=bold>Cruce del resto de comprobantes con el sistema</> (' . $sistema->count() . ' en el Excel; aquí no se modifica nada)');

        if (! $faltan && ! $diferencias && ! $sobran) {
            $this->line('  Todos calzan: mismo número, fecha, cliente y monto, y no hay aceptados del sistema que SIRE no tenga.');
            return;
        }

        if ($faltan) {
            $this->line('  En SIRE y no en el sistema:');
            $this->table(['Comprobante', 'Fecha', 'Cliente', 'Total'], $faltan);
        }
        if ($diferencias) {
            $this->line('  Con diferencias:');
            $this->table(['Comprobante', 'Registro', 'Diferencia'], $diferencias);
        }
        if ($sobran) {
            $this->line('  Aceptados en el sistema y no en SIRE:');
            $this->table(['Comprobante', 'Fecha', 'Cliente', 'Total', 'Registro'], $sobran);
        }
    }

    /** Total de SIRE contra el total de /documents del mismo mes (solo aceptados, NC restan). */
    private function mostrarTotales(array $filas, array $plan, bool $registrado): void
    {
        $this->newLine();
        $this->line('<options=bold>Totales por periodo</> (mismo criterio que el pie de /documents)');

        $fila = [];

        foreach (collect($filas)->groupBy('periodo') as $periodo => $grupo) {
            if (! preg_match('/^(\d{4})(\d{2})$/', (string) $periodo, $m)) {
                continue;
            }

            $sire = round($grupo->sum(fn ($f) => $f['signo'] * $f['total']), 2);
            $sistema = TableDocuments::resumen(['anio' => $m[1], 'mes' => $m[2]])['neto'];

            // En la vista previa se suma lo que se registraria
            if (! $registrado) {
                $sistema = round($sistema + collect($plan)
                    ->filter(fn ($p) => $p['accion'] === 'registrar' && substr($p['fecha'], 0, 7) === "{$m[1]}-{$m[2]}")
                    ->sum(fn ($p) => $p['signo'] * $p['total']), 2);
            }

            $fila[] = [
                $periodo,
                $grupo->count(),
                number_format($sire, 2),
                number_format($sistema, 2),
                abs($sire - $sistema) < 0.005 ? 'CALZA' : 'diferencia ' . number_format($sistema - $sire, 2),
            ];
        }

        $this->table(['Periodo', 'Comprobantes SIRE', 'Total SIRE', $registrado ? 'Total sistema' : 'Sistema tras registrar', ''], $fila);
    }
}
