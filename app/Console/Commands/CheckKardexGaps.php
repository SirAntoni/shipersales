<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckKardexGaps extends Command
{
    protected $signature = 'kardex:check';

    protected $description = 'Chequeo diario: detecta artículos cuyo desfase stock vs kardex (neto de tránsito) cambió desde el último chequeo';

    public function handle(): int
    {
        $today = now()->toDateString();

        $transit = DB::table('purchase_details as pd')
            ->join('purchases as p', 'p.id', '=', 'pd.purchase_id')
            ->whereIn('p.status', [2, 3])
            ->whereNull('pd.deleted_at')
            ->whereNull('p.deleted_at')
            ->groupBy('pd.article_id')
            ->selectRaw('pd.article_id, SUM(pd.quantity) as qty')
            ->pluck('qty', 'article_id');

        $articles = DB::table('articles as a')
            ->join('v_kardex_stock as k', 'k.article_id', '=', 'a.id')
            ->where('a.status', 'active')
            ->whereNot('a.id', 1)
            ->get(['a.id', 'a.stock', 'k.kardex_stock']);

        // Ultimo gap neto conocido por articulo (el snapshot mas reciente, incluso del mismo dia)
        $previous = DB::table('stock_gap_snapshots as s')
            ->whereRaw('s.id = (SELECT MAX(s2.id) FROM stock_gap_snapshots s2 WHERE s2.article_id = s.article_id)')
            ->get(['s.article_id', 's.gap', 's.transit', 's.changed', 's.checked_date'])
            ->keyBy('article_id');

        // Primera corrida = linea base: se registra el estado actual sin alarmar
        $baseline = DB::table('stock_gap_snapshots')->count() === 0;

        $changed = [];
        foreach ($articles as $a) {
            $tr  = (int) ($transit[$a->id] ?? 0);
            $gap = (int) $a->stock - (int) $a->kardex_stock;
            $net = $gap + $tr; // el gap esperado por transito es -transit; neto 0 = sano

            $prev    = $previous[$a->id] ?? null;
            $prevNet = $prev ? ((int) $prev->gap + (int) $prev->transit) : 0;
            $isNew   = ! $baseline && $net !== $prevNet;

            // No pisar la marca de alerta si el cron corre varias veces el mismo dia
            $flag = $isNew || ($prev && $prev->checked_date === $today && $prev->changed);

            if ($gap !== 0 || $tr !== 0 || $flag) {
                DB::table('stock_gap_snapshots')->updateOrInsert(
                    ['article_id' => $a->id, 'checked_date' => $today],
                    ['gap' => $gap, 'transit' => $tr, 'changed' => $flag, 'created_at' => now()]
                );
            }

            if ($isNew) {
                $changed[] = ['article_id' => $a->id, 'gap' => $gap, 'transit' => $tr, 'net' => $net, 'prev_net' => $prevNet];
            }
        }

        if ($baseline) {
            $this->info('Línea base registrada (' . $articles->count() . ' artículos). Los próximos chequeos alertarán cambios.');
            return self::SUCCESS;
        }

        if (empty($changed)) {
            $this->info('Sin cambios de desfase desde el último chequeo.');
            return self::SUCCESS;
        }

        $this->warn(count($changed) . ' artículo(s) con desfase NUEVO o cambiado:');
        foreach ($changed as $c) {
            $title = DB::table('articles')->where('id', $c['article_id'])->value('title');
            $this->line("  art {$c['article_id']} [{$title}]: gap neto {$c['prev_net']} -> {$c['net']} (gap {$c['gap']}, tránsito {$c['transit']})");
        }
        $this->line('Revisa stock_change_logs del artículo para ver qué operación lo movió.');

        return self::FAILURE; // exit != 0 para que un cron pueda alertar
    }
}
