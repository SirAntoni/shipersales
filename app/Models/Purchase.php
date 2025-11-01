<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Purchase extends Model
{

    const PURCHASE_CANCELED = 0;
    const PURCHASE_FINISHED = 1;
    const PURCHASE_NOT_FINISHED = 2;

    const PURCHASE_SQUARE = 3;

    protected $fillable = [
        'provider_id',
        'user_id',
        'voucher_type',
        'document',
        'passenger',
        'subtotal',
        'tax',
        'total',
        'status'
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public static function exchangeRate(): float
    {
        return (float) Setting::value('exchange_rate');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseDetails()
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    protected static function booted()
    {
        static::creating(function ($provider) {
            $lastNumber = self::max('number');
            $provider->number = $lastNumber ? $lastNumber + 1 : 100001;
        });
    }
}
