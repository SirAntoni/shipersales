<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAdjustment extends Model
{
    protected $fillable = [
        'article_id',
        'old_stock',
        'new_stock',
        'delta',
        'reason',
        'source',
        'created_by',
    ];

    /**
     * Registra en el kardex la salida de una venta que se anula SIN reponer
     * stock: al anularse, la salida de la venta deja de contar en el kardex y
     * su saldo subiria solo, mientras articles.stock no cambia. Este ajuste
     * negativo compensa esa subida (kardex sigue cuadrando con el almacen) y
     * deja constancia de que la mercaderia no volvio.
     */
    public static function registrarSalidaSinReposicion(Sale $sale, string $reason): void
    {
        // Mismo filtro que v_kardex_stock (sd.deleted_at IS NULL): un detalle
        // soft-deleted nunca contó como salida en el kardex, así que tampoco
        // debe compensarse (SaleDetail no usa el trait SoftDeletes).
        foreach ($sale->saleDetails()->whereNull('deleted_at')->get() as $item) {
            $stock = (int) (Article::withTrashed()->find($item->article_id)?->stock ?? 0);

            self::create([
                'article_id' => $item->article_id,
                'old_stock'  => $stock, // el almacen no cambia: el ajuste es solo documental
                'new_stock'  => $stock,
                'delta'      => -(int) $item->quantity,
                'reason'     => $reason,
                'source'     => 'anulacion:sin_reposicion',
                'created_by' => auth()->id(),
            ]);
        }
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
