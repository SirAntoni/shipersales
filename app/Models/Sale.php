<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{

    protected $fillable = [
        'number',
        'client_id',
        'user_id',
        'voucher_type',
        'voucher_serie',
        'voucher_number',
        'date',
        'subtotal',
        'tax',
        'total',
        'contact_id',
        'payment_method_id',
        'status',
        'delivery',
        'delivery_fee',
        'observations'
    ];


    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class);
    }

}
