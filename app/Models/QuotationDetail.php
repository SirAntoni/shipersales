<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationDetail extends Model
{
    protected $fillable = [
        'quotation_id',
        'article_id',
        'price',
        'quantity',
        'subtotal',
        'tax',
        'total',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
