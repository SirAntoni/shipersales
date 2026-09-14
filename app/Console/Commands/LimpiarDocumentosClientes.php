<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Quita espacios, tabuladores y espacios duros del numero de documento y del
 * nombre de los clientes. Llegan al pegar datos en el formulario y SUNAT
 * rechaza la factura (error 2017) porque valida el numero tal cual.
 *
 * Ademas informa, sin tocarlos, los clientes tipo RUC cuyo numero sigue sin
 * ser un RUC valido (hay que corregirlos a mano) y los que tras la limpieza
 * quedan con el mismo numero que otro cliente (duplicados a fusionar).
 */
class LimpiarDocumentosClientes extends Command
{
    protected $signature = 'clientes:limpiar-documentos
                            {--dry-run : Solo mostrar qué haría, sin escribir nada}
                            {--force : No pedir confirmación (para ejecución vía ruta web)}
                            {--user= : ID del usuario que ejecuta (queda en el log)}';

    protected $description = 'Quita espacios sobrantes del número de documento y del nombre de los clientes';

    public function handle(): int
    {
        $clientes = DB::table('clients')->orderBy('id')->get(['id', 'name', 'document_type', 'document_number']);

        $cambios = [];

        foreach ($clientes as $c) {
            $numero = (string) Client::normalizarNumeroDocumento($c->document_number);
            $nombre = (string) Client::normalizarNombre($c->name);

            if ($numero !== (string) $c->document_number || $nombre !== (string) $c->name) {
                $cambios[] = [
                    'id'         => $c->id,
                    'tipo'       => $c->document_type,
                    'numero'     => (string) $c->document_number,
                    'numero_ok'  => $numero,
                    'nombre'     => (string) $c->name,
                    'nombre_ok'  => $nombre,
                ];
            }
        }

        $this->newLine();
        $this->line('<options=bold>Clientes a limpiar</> (espacios visibles como "·", tabuladores como "\t")');

        if ($cambios === []) {
            $this->line('  (ninguno)');
        } else {
            $this->table(
                ['ID', 'Tipo', 'Número actual', 'Número limpio', 'Nombre actual', 'Nombre limpio'],
                array_map(fn ($x) => [
                    $x['id'],
                    $x['tipo'],
                    $this->visible($x['numero']),
                    $x['numero_ok'],
                    mb_strimwidth($this->visible($x['nombre']), 0, 34, '...'),
                    mb_strimwidth($x['nombre_ok'], 0, 34, '...'),
                ], $cambios)
            );
        }

        // Duplicados: mismo numero limpio en mas de un cliente, cuando alguno se limpia
        $porNumero = $clientes->groupBy(fn ($c) => (string) Client::normalizarNumeroDocumento($c->document_number));
        $idsCambio = array_column($cambios, 'id');
        $duplicados = [];

        foreach ($porNumero as $numero => $grupo) {
            if ($numero !== '' && $grupo->count() > 1 && $grupo->whereIn('id', $idsCambio)->isNotEmpty()) {
                $duplicados[] = [$numero, $grupo->map(fn ($c) => "#{$c->id} " . Client::normalizarNombre($c->name))->implode(' | ')];
            }
        }

        if ($duplicados !== []) {
            $this->newLine();
            $this->line('<options=bold>Quedan con el mismo número que otro cliente</> (mismo dato dos veces: fusionar a mano, el sistema no los toca)');
            $this->table(['Número', 'Clientes'], $duplicados);
        }

        // RUC invalidos: se informan, no se pueden adivinar
        $rucInvalidos = $clientes
            ->where('document_type', 'RUC')
            ->filter(fn ($c) => ! Client::rucValido($c->document_number))
            ->map(fn ($c) => [$c->id, Client::normalizarNombre($c->name), $this->visible((string) $c->document_number)])
            ->values()
            ->all();

        if ($rucInvalidos !== []) {
            $this->newLine();
            $this->line('<options=bold>Clientes tipo RUC con número inválido</> (corregir a mano en Clientes; una factura a ellos daría el error 2017)');
            $this->table(['ID', 'Cliente', 'Número'], $rucInvalidos);
        }

        $this->newLine();
        $this->line('  Total: ' . count($cambios) . ' clientes a limpiar · ' . count($duplicados) . ' números duplicados · ' . count($rucInvalidos) . ' RUC inválidos.');

        if ($cambios === []) {
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry-run: no se escribió nada.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Se limpiarán ' . count($cambios) . ' clientes. ¿Continuar?')) {
            $this->warn('Cancelado.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($cambios) {
            foreach ($cambios as $x) {
                DB::table('clients')->where('id', $x['id'])->update([
                    'document_number' => $x['numero_ok'],
                    'name'            => $x['nombre_ok'],
                    'updated_at'      => now(),
                ]);
            }
        });

        Log::info('Clientes limpiados de espacios en número/nombre', [
            'cantidad'      => count($cambios),
            'ejecutado_por' => $this->option('user'),
            'cambios'       => $cambios,
        ]);

        $this->info('Listo: ' . count($cambios) . ' clientes limpiados.');

        return self::SUCCESS;
    }

    /** Hace visibles los espacios para que se entienda qué se limpia. */
    private function visible(string $valor): string
    {
        return str_replace([' ', "\t", "\u{00A0}"], ['·', '\t', '⍽'], $valor);
    }
}
