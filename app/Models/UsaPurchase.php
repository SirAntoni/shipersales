<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsaPurchase extends Model
{
    const STATUS_PURCHASED = 'COMPRADO';
    const STATUS_DELIVERED = 'ENTREGADO';
    const STATUS_PARTIAL = 'PARCIAL';
    const STATUS_NOT_ARRIVED = 'NO LLEGO';

    const TYPE_CARGO = 'CARGO';
    const TYPE_TRAVELER = 'VIAJERO';

    protected $fillable = [
        'date',
        'carrier',
        'store',
        'order_number',
        'tracking',
        'quantity',
        'imported_quantity',
        'description',
        'sku',
        'article_id',
        'status',
        'arrival_date',
        'comments',
        'type',
        'year',
        'processed',
        'purchase_id',
    ];

    protected $casts = [
        'date' => 'date',
        'arrival_date' => 'date',
        'processed' => 'boolean',
        'quantity' => 'integer',
        'imported_quantity' => 'integer',
    ];

    /** Unidades que aun no entraron al stock. */
    public function pendingQuantity(): int
    {
        return max(0, (int) $this->quantity - (int) $this->imported_quantity);
    }

    /** Se puede importar: tiene saldo, articulo vinculado y ya llego (total o parcialmente). */
    public function esImportable(): bool
    {
        return $this->pendingQuantity() > 0
            && $this->article_id
            && in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_PARTIAL], true);
    }

    public function scopeConSaldoPorImportar($query)
    {
        return $query->whereColumn('imported_quantity', '<', 'quantity')
            ->whereNotNull('article_id')
            ->whereIn('status', [self::STATUS_DELIVERED, self::STATUS_PARTIAL]);
    }

    /**
     * Acumula una importacion al stock. processed significa "importado
     * completo": una llegada parcial deja la fila abierta con su saldo.
     * Al completarse, una fila en PARCIAL pasa a ENTREGADO: ya llego todo.
     */
    public function registrarImportacion(int $cantidad, int $purchaseId): void
    {
        $this->imported_quantity = (int) $this->imported_quantity + $cantidad;
        $this->processed = $this->imported_quantity >= (int) $this->quantity;
        $this->purchase_id = $purchaseId;

        if ($this->processed && $this->status === self::STATUS_PARTIAL) {
            $this->status = self::STATUS_DELIVERED;
        }

        $this->save();
    }

    /** Importadas completas que siguen en PARCIAL (anteriores a la regla de registrarImportacion). */
    public function scopeParcialesCompletas($query)
    {
        return $query->where('status', self::STATUS_PARTIAL)
            ->where('processed', true)
            ->where('imported_quantity', '>', 0)
            ->whereColumn('imported_quantity', '>=', 'quantity');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
