<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Un pedido de periodo a SIRE: ticket, en que quedo y cuantos comprobantes trajo. */
class SireDownload extends Model
{
    const STATUS_REQUESTED = 'solicitado';
    const STATUS_DONE = 'listo';
    const STATUS_ERROR = 'error';

    protected $fillable = [
        'period',
        'ticket',
        'status',
        'message',
        'total_rows',
        'user_id',
        'finished_at',
    ];

    protected $casts = [
        'total_rows'  => 'integer',
        'finished_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePendientes($query)
    {
        return $query->where('status', self::STATUS_REQUESTED);
    }
}
