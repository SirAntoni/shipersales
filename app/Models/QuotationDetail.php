<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationDetail extends Model
{
    protected $fillable = [
        'quotation_id',
        'article_id',
        'custom_product',
        'price',
        'quantity',
        'subtotal',
        'tax',
        'total',
    ];

    protected $casts = [
        'custom_product' => 'array',
    ];

    public function getIsCustomAttribute(): bool
    {
        return $this->article_id === null;
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->article->title ?? ($this->custom_product['title'] ?? '—');
    }

    public function getDisplaySkuAttribute(): ?string
    {
        return $this->article->sku ?? null;
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
