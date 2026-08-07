<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleDetail extends Model
{

    protected $table = 'sale_details';
    protected $fillable = [
        'sale_id',
        'article_id',
        'category_id',
        'brand_id',
        'price',
        'quantity',
        'tax',
        'total',
        'shopper'
    ];
    public function sale(){
        return $this->belongsTo(Sale::class);
    }

    public function article(){
        return $this->belongsTo(Article::class);
    }

    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function brand(){
        return $this->belongsTo(Brand::class);
    }
}
