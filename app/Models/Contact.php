<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = ['name'];

    public function sales(){
        return $this->hasMany(Sale::class);
    }
    public function marketplaceArticles()
    {
        return $this->hasMany(ArticleMarketplace::class, 'contact_id');
    }
}
