<?php

namespace App\Console\Commands;

use App\Models\UsaPurchase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Pasa a ENTREGADO los registros de compras USA que siguen en PARCIAL aunque
 * ya se importaron completos al stock. La importacion no tocaba el estado (es
 * manual), asi que la fila quedaba en PARCIAL tras importar el saldo, y el
 * cambio masivo de estado no la alcanza porque excluye las procesadas.
 *
 * Desde que registrarImportacion() hace este cambio al completar una fila,
 * solo quedan por corregir las importadas antes de esa regla.
 */
class CerrarParcialesUsa extends Command
{
    protected $signature = 'compras-usa:cerrar-parciales
                            {--dry-run : Solo mostrar qué haría, sin escribir nada}
                            {--force : No pedir confirmación (para ejecución vía ruta web)}
                            {--user= : ID del usuario que ejecuta (queda en el log)}';

    protected $description = 'Pasa a ENTREGADO los registros USA en PARCIAL que ya se importaron completos';

    public function handle(): int
    {
        $registros = UsaPurchase::parcialesCompletas()->orderBy('id')->get();

        $this->newLine();
        $this->line('<options=bold>Registros USA a pasar de PARCIAL a ENTREGADO</> (importados completos al stock)');

        if ($registros->isEmpty()) {
            $this->line('  (ninguno)');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Pedido', 'SKU', 'Descripción', 'Importado', 'Compra', 'Últ. cambio'],
            $registros->map(fn (UsaPurchase $r) => [
                $r->id,
                mb_strimwidth((string) $r->order_number, 0, 30, '...'),
                $r->sku,
                mb_strimwidth(trim((string) $r->description), 0, 34, '...'),
                "{$r->imported_quantity}/{$r->quantity}",
                $r->purchase_id ? "#{$r->purchase_id}" : '-',
                $r->updated_at?->format('Y-m-d H:i'),
            ])->all()
        );

        $this->newLine();
        $this->line("  Total: {$registros->count()} registro(s).");

        if ($this->option('dry-run')) {
            $this->warn('Dry-run: no se escribió nada.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Se pasarán {$registros->count()} registro(s) a ENTREGADO. ¿Continuar?")) {
            $this->warn('Cancelado.');

            return self::SUCCESS;
        }

        $ids = $registros->pluck('id')->all();

        // Por query builder para no pisar updated_at: es el unico rastro de
        // cuando se completo la importacion. La constancia del cambio va al log.
        $actualizados = DB::table('usa_purchases')
            ->whereIn('id', $ids)
            ->where('status', UsaPurchase::STATUS_PARTIAL)
            ->update(['status' => UsaPurchase::STATUS_DELIVERED]);

        Log::info('Compras USA: registros importados completos pasados de PARCIAL a ENTREGADO', [
            'ids'           => $ids,
            'actualizados'  => $actualizados,
            'ejecutado_por' => $this->option('user'),
        ]);

        $this->info("Listo: {$actualizados} registro(s) pasados a ENTREGADO.");

        return self::SUCCESS;
    }
}
