<?php

namespace App\Observers;

use App\Models\Article;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ArticleObserver
{
    /**
     * Audita todo cambio de articles.stock, venga del flujo que venga
     * (ventas, compras, devoluciones, webhook ML, inventario, etc.).
     */
    public function updated(Article $article): void
    {
        if (! $article->wasChanged('stock')) {
            return;
        }

        $old = (int) $article->getOriginal('stock');
        $new = (int) $article->stock;

        DB::table('stock_change_logs')->insert([
            'article_id' => $article->id,
            'old_stock'  => $old,
            'new_stock'  => $new,
            'delta'      => $new - $old,
            'context'    => $this->callerContext(),
            'via'        => mb_substr(preg_replace('/\s+/', ' ', app()->runningInConsole()
                ? 'console:' . (implode(' ', array_slice($_SERVER['argv'] ?? [], 1, 2)) ?: 'unknown')
                : 'http:' . request()->path()), 0, 60),
            'user_id'    => Auth::id(),
            'created_at' => now(),
        ]);
    }

    /** Primer frame del backtrace dentro de app/ que no sea este observer: "Archivo.php:linea". */
    private function callerContext(): ?string
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 40) as $frame) {
            $file = $frame['file'] ?? '';
            if ($file && str_starts_with($file, app_path()) && ! str_contains($file, 'Observers')) {
                return str_replace(app_path() . DIRECTORY_SEPARATOR, '', $file) . ':' . ($frame['line'] ?? '?');
            }
        }

        return null;
    }
}
