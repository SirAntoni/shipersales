<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsaPurchase extends Model
{
    const STATUS_SHIPPED = 'EMBARCADO';
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
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
