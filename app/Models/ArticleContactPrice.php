<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleContactPrice extends Model
{
    protected $fillable = ['article_id','contact_id','price','active'];

    protected $casts = [
        'active' => 'boolean',
        'price'  => 'decimal:2',
    ];

    public function article() { return $this->belongsTo(Article::class); }
    public function contact() { return $this->belongsTo(Contact::class); }
}


