<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    const STATUS_PENDING  = 'pendiente';
    const STATUS_ACCEPTED = 'aceptada';
    const STATUS_REJECTED = 'rechazada';

    /** Plazo de entrega que se muestra en el PDF cuando la cotización no trae uno propio. */
    const DEFAULT_DELIVERY_TIME = '12 a 20 días hábiles desde el abono.';

    protected $fillable = [
        'number',
        'date',
        'valid_until',
        'delivery_time',
        'status',
        'notes',
        'subtotal',
        'tax',
        'total',
        'client_id',
        'user_id',
        'sale_id',
    ];

    /** Plazo de entrega a mostrar: el de la cotización o el texto por defecto. */
    public function getDeliveryTimeTextAttribute(): string
    {
        return trim((string) $this->delivery_time) ?: self::DEFAULT_DELIVERY_TIME;
    }

    public function quotationDetails()
    {
        return $this->hasMany(QuotationDetail::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Venta generada al aceptar la cotización */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /** Correlativo COT-000001 basado en el último número emitido. */
    public static function nextNumber(): string
    {
        $last = static::orderByDesc('id')->value('number');
        $seq  = $last ? ((int) str_replace('COT-', '', $last)) + 1 : 1;

        return 'COT-' . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }
}
