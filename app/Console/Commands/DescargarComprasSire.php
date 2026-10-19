<?php

namespace App\Console\Commands;

use App\Livewire\Purchases\TableSirePurchases;
use App\Models\SireDownload;
use App\Services\SireService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Lo mismo que el boton "Descargar de SUNAT" de /sire-purchases, pero de una
 * vez (espera el ticket aqui). Con --archivo lee un ZIP o txt exportado desde
 * SIRE web, sin llamar a la API: sirve para probar la lectura del formato.
 */
class DescargarComprasSire extends Command
{
    protected $signature = 'sire:compras
                            {periodo? : yyyymm; por defecto el ultimo disponible}
                            {--archivo= : ZIP o txt de la propuesta exportado desde SIRE web}
                            {--dry-run : Solo mostrar lo que se leyo, sin guardar}
                            {--user= : ID del usuario que ejecuta}';

    protected $description = 'Descarga de SUNAT (SIRE) la propuesta del Registro de Compras de un periodo';

    /** Entre consultas del ticket y cuantas veces. */
    private const SEGUNDOS_ENTRE_CONSULTAS = 5;

    private const MAX_CONSULTAS = 60;

    public function handle(SireService $sire): int
    {
        $periodo = $this->argument('periodo') ?: SireService::ultimoPeriodoDisponible();

        if (! TableSirePurchases::periodoValido($periodo)) {
            $this->error("Periodo inválido: {$periodo}. Use yyyymm, por ejemplo 202609.");

            return self::FAILURE;
        }

        if ($archivo = $this->option('archivo')) {
            return $this->desdeArchivo($sire, $periodo, $archivo);
        }

        if ($this->option('dry-run')) {
            $this->error('--dry-run solo se puede usar con --archivo: la API no tiene vista previa.');

            return self::FAILURE;
        }

        if (! SireService::configurado()) {
            $this->error('Faltan SIRE_CLIENT_ID, SIRE_CLIENT_SECRET, SIRE_USUARIO_SOL o SIRE_CLAVE_SOL en el .env.');

            return self::FAILURE;
        }

        $descarga = $sire->solicitar($periodo, $this->option('user') ? (int) $this->option('user') : null);
        $this->line("Ticket {$descarga->ticket} pedido para {$periodo}. Esperando a SUNAT...");

        for ($i = 0; $i < self::MAX_CONSULTAS && $descarga->status === SireDownload::STATUS_REQUESTED; $i++) {
            sleep(self::SEGUNDOS_ENTRE_CONSULTAS);
            $descarga = $sire->avanzar($descarga);
        }

        if ($descarga->status === SireDownload::STATUS_DONE) {
            $this->info("Listo: {$descarga->total_rows} comprobante(s) de {$periodo}.");

            return self::SUCCESS;
        }

        $this->error($descarga->status === SireDownload::STATUS_ERROR
            ? $descarga->message
            : "SUNAT sigue procesando el ticket {$descarga->ticket}. La pantalla /sire-purchases lo seguirá consultando.");

        return self::FAILURE;
    }

    private function desdeArchivo(SireService $sire, string $periodo, string $archivo): int
    {
        if (! is_file($archivo)) {
            $this->error("No existe el archivo {$archivo}.");

            return self::FAILURE;
        }

        $contenido = file_get_contents($archivo);

        try {
            $filas = SireService::leer($contenido, $periodo);
        } catch (\UnexpectedValueException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Fecha', 'Tipo', 'Comprobante', 'RUC', 'Proveedor', 'Mon.', 'Base', 'IGV', 'Total'],
            array_map(fn ($f) => [
                $f['issue_date'],
                $f['document_type'],
                "{$f['serie']}-{$f['number']}",
                $f['issuer_document_number'],
                mb_strimwidth((string) $f['issuer_name'], 0, 30, '...'),
                $f['currency'],
                number_format($f['taxable_base'], 2),
                number_format($f['igv'], 2),
                number_format($f['total'], 2),
            ], $filas)
        );
        $this->line('  Total: ' . count($filas) . ' comprobante(s).');

        if ($this->option('dry-run')) {
            $this->warn('Dry-run: no se guardó nada.');

            return self::SUCCESS;
        }

        // Todo o nada: si la importacion falla no queda una descarga "lista" sin filas.
        $guardadas = DB::transaction(function () use ($sire, $periodo, $contenido, $archivo) {
            $descarga = SireDownload::create([
                'period'      => $periodo,
                'status'      => SireDownload::STATUS_DONE,
                'message'     => 'Archivo: ' . mb_substr(basename($archivo), 0, 480),
                'user_id'     => $this->option('user') ? (int) $this->option('user') : null,
                'finished_at' => now(),
            ]);

            $descarga->update(['total_rows' => $sire->importar($periodo, $contenido, $descarga)]);

            return $descarga->total_rows;
        });

        $this->info("Listo: {$guardadas} comprobante(s) de {$periodo} guardados.");

        return self::SUCCESS;
    }
}
