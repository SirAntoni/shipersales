<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{

    const SALE_CANCELED = 0;
    const SALE_PENDING = 1;
    const SALE_APPROVED = 2;
    const SALE_OBSERVATION = 3;

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
        'webhook_imported',
        'delivery',
        'delivery_fee',
        'observations',
        'deletion_reason',
        'deletion_date'
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

    public function document(){
        return $this->hasOne(Document::class);
    }

}
