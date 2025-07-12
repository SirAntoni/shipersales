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
        'stock',
        'purchase_price',
        'sale_price',
        'provider_id',
        'category_id',
        'brand_id'
    ];

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
}
