<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleMarketplace extends Model
{
    protected $table = 'articles_marketplace';

    protected $fillable = [
        'article_id',
        'code',
        'contact_id'
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
