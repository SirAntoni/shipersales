<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{

    use SoftDeletes;

    protected $fillable=[
        'title',
        'detail',
        'description',
        'sku',
        'barcode',
        'stock',
        'purchase_price',
        'sale_price',
        'provider_id',
        'category_id',
        'brand_id',
        'status',
        'on_demand'
    ];

    protected $casts = [
        'on_demand' => 'boolean',
    ];

    public static function generateSku(): string
    {
        return 'A' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT) . now()->year . now()->format('m');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeRegular($query)
    {
        return $query->where('on_demand', false);
    }

    public function scopeOnDemand($query)
    {
        return $query->where('on_demand', true);
    }

    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function brand(){
        return $this->belongsTo(Brand::class);
    }

    public function provider(){
        return $this->belongsTo(Provider::class);
    }

    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class, 'article_id');
    }

    public function marketplaceCodes()
    {
        return $this->hasMany(ArticleMarketplace::class);
    }

    public function contactPrices()
    {
        return $this->hasMany(\App\Models\ArticleContactPrice::class);
    }
}
