<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\InventoryAdjustment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RegularizeKardex extends Command
{
    protected $signature = 'kardex:regularize
                            {article? : ID del artículo a regularizar}
                            {--list : Listar artículos activos con kardex descuadrado, sin escribir nada}
                            {--all : Regularizar TODOS los artículos activos descuadrados bajo el mismo motivo}
                            {--reason= : Motivo del ajuste (default: Regularización kardex vs stock de almacén)}
                            {--user= : ID de usuario que firma el ajuste (default: sin usuario)}
                            {--dry-run : Solo mostrar qué haría, sin escribir nada}
                            {--force : No pedir confirmación (para ejecución vía ruta web o cron)}';

    protected $description = 'Registra un ajuste de kardex que iguala el saldo documental al stock de almacén (articles.stock)';

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->listMismatches();
        }

        if ($this->option('all')) {
            if ($this->argument('article')) {
                $this->error('--all no se combina con un ID de artículo.');
                return self::FAILURE;
            }
            return $this->regularizeAll();
        }

        if (!$this->argument('article')) {
            $this->error('Indica el ID del artículo, usa --list para ver los descuadrados o --all para regularizarlos todos.');
            return self::FAILURE;
        }

        $articleId = (int) $this->argument('article');

        $article = Article::find($articleId);
        if (!$article) {
            $this->error("No existe el artículo {$articleId}.");
            return self::FAILURE;
        }

        $transit = (int) ($this->transitQtys()[$articleId] ?? 0);
        if ($transit > 0) {
            $this->error("El artículo tiene {$transit} unidades en compras abiertas (status 2/3): el kardex ya las cuenta pero aún no ingresan al almacén, así que parte del desfase es tránsito legítimo. Regulariza después de recibir la mercadería.");
            return self::FAILURE;
        }

        $stock  = (int) $article->stock;
        $kardex = (int) DB::table('v_kardex_stock')
            ->where('article_id', $articleId)
            ->value('kardex_stock');
        $delta  = $stock - $kardex;

        $this->table(
            ['Artículo', 'Stock almacén', 'Saldo kardex', 'Delta a registrar'],
            [[$article->title, $stock, $kardex, sprintf('%+d', $delta)]]
        );

        if ($delta === 0) {
            $this->info('El kardex ya coincide con el almacén. No hay nada que regularizar.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry-run: no se escribió nada.');
            return self::SUCCESS;
        }

        $reason = $this->option('reason') ?: 'Regularización kardex vs stock de almacén';

        if (!$this->option('force') && !$this->confirm("Se registrará un ajuste de {$delta} en el kardex de \"{$article->title}\" (motivo: {$reason}). ¿Continuar?")) {
            $this->warn('Cancelado.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($articleId, $stock, $delta, $reason) {
            InventoryAdjustment::create([
                'article_id' => $articleId,
                'old_stock'  => $stock,   // el almacén no cambia: el ajuste es solo documental
                'new_stock'  => $stock,
                'delta'      => $delta,
                'reason'     => $reason,
                'source'     => 'kardex:regularize',
                'created_by' => $this->option('user') ?: null,
            ]);
        });

        $kardexAfter = (int) DB::table('v_kardex_stock')
            ->where('article_id', $articleId)
            ->value('kardex_stock');

        $this->info("Ajuste registrado. Kardex: {$kardex} -> {$kardexAfter} (almacén: {$stock}).");

        return $kardexAfter === $stock ? self::SUCCESS : self::FAILURE;
    }

    /** Unidades en compras abiertas (status 2/3) por artículo: el kardex ya las cuenta, el almacén todavía no. */
    private function transitQtys()
    {
        return DB::table('purchase_details as pd')
            ->join('purchases as p', 'p.id', '=', 'pd.purchase_id')
            ->whereIn('p.status', [2, 3])
            ->whereNull('pd.deleted_at')
            ->whereNull('p.deleted_at')
            ->groupBy('pd.article_id')
            ->selectRaw('pd.article_id, SUM(pd.quantity) as qty')
            ->pluck('qty', 'article_id');
    }

    private function mismatches()
    {
        return DB::table('articles as a')
            ->join('v_kardex_stock as k', 'k.article_id', '=', 'a.id')
            ->where('a.status', 'active')
            ->whereNot('a.id', 1)
            ->whereRaw('a.stock <> k.kardex_stock')
            ->orderByRaw('ABS(a.stock - k.kardex_stock) DESC')
            ->get([
                'a.id',
                'a.sku',
                'a.title',
                'a.stock',
                'k.kardex_stock',
                DB::raw('(a.stock - k.kardex_stock) as delta'),
            ]);
    }

    private function renderMismatchTable($rows, $transit): void
    {
        $this->table(
            ['ID', 'SKU', 'Artículo', 'Stock almacén', 'Saldo kardex', 'Delta', 'En tránsito'],
            $rows->map(fn ($r) => [
                $r->id,
                $r->sku,
                mb_strimwidth($r->title, 0, 45, '…'),
                $r->stock,
                $r->kardex_stock,
                sprintf('%+d', $r->delta),
                ($transit[$r->id] ?? 0) ?: '-',
            ])
        );
    }

    private function listMismatches(): int
    {
        $rows = $this->mismatches();

        if ($rows->isEmpty()) {
            $this->info('Todos los artículos activos tienen el kardex cuadrado con el almacén.');
            return self::SUCCESS;
        }

        $transit = $this->transitQtys();
        $this->renderMismatchTable($rows, $transit);

        $conTransito = $rows->filter(fn ($r) => ($transit[$r->id] ?? 0) > 0)->count();
        $this->warn("{$rows->count()} artículos descuadrados ({$conTransito} con compras abiertas, excluidos de --all). Regulariza solo tras verificar con conteo físico: kardex:regularize {id}");

        return self::SUCCESS;
    }

    private function regularizeAll(): int
    {
        $all = $this->mismatches();

        if ($all->isEmpty()) {
            $this->info('Todos los artículos activos tienen el kardex cuadrado con el almacén.');
            return self::SUCCESS;
        }

        $transit = $this->transitQtys();
        [$skipped, $rows] = $all->partition(fn ($r) => ($transit[$r->id] ?? 0) > 0);

        if ($skipped->isNotEmpty()) {
            $this->warn("EXCLUIDOS {$skipped->count()} artículos con compras abiertas (status 2/3): su desfase puede ser tránsito legítimo. Regularízalos tras recibir la mercadería:");
            $this->renderMismatchTable($skipped, $transit);
        }

        if ($rows->isEmpty()) {
            $this->info('No queda ningún artículo regularizable sin compras abiertas.');
            return self::SUCCESS;
        }

        $this->line('A regularizar:');
        $this->renderMismatchTable($rows, $transit);

        $totalDelta = $rows->sum('delta');
        $this->line("{$rows->count()} artículos a regularizar (suma de deltas: " . sprintf('%+d', $totalDelta) . ').');

        if ($this->option('dry-run')) {
            $this->warn('Dry-run: no se escribió nada.');
            return self::SUCCESS;
        }

        $reason = $this->option('reason') ?: 'Regularización kardex vs stock de almacén';

        $this->warn('Esto asume que articles.stock es el valor correcto para TODOS los listados.');
        if (!$this->option('force') && !$this->confirm("Se registrarán {$rows->count()} ajustes con motivo \"{$reason}\". ¿Continuar?")) {
            $this->warn('Cancelado.');
            return self::SUCCESS;
        }

        $userId = $this->option('user') ?: null;

        DB::transaction(function () use ($rows, $reason, $userId) {
            foreach ($rows as $r) {
                InventoryAdjustment::create([
                    'article_id' => $r->id,
                    'old_stock'  => $r->stock,
                    'new_stock'  => $r->stock,
                    'delta'      => $r->delta,
                    'reason'     => $reason,
                    'source'     => 'kardex:regularize',
                    'created_by' => $userId,
                ]);
            }
        });

        $transitNow = $this->transitQtys();
        $remaining  = $this->mismatches()->filter(fn ($r) => ($transitNow[$r->id] ?? 0) == 0)->count();

        if ($remaining === 0) {
            $msg = "Listo: {$rows->count()} ajustes registrados.";
            if ($skipped->isNotEmpty()) {
                $msg .= " Quedan {$skipped->count()} artículos con compras abiertas sin regularizar (a propósito).";
            }
            $this->info($msg);
            return self::SUCCESS;
        }

        $this->error("Se registraron {$rows->count()} ajustes pero quedan {$remaining} artículos descuadrados sin compras abiertas (¿movimientos concurrentes?). Corre --list para revisarlos.");
        return self::FAILURE;
    }
}
