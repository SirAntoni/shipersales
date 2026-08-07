<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseDetail extends Model
{
    /**
     * La tabla ya tenia deleted_at desde su migracion original. Al activar el
     * trait, un detalle quitado desde /purchases/{id} deja de contar para el
     * stock y el kardex, pero la fila se conserva para mostrarla tachada
     * (withTrashed).
     */
    use SoftDeletes;

    protected $table = 'purchase_details';
    protected $fillable = [
        'purchase_id',
        'article_id',
        'category_id',
        'brand_id',
        'price',
        'quantity',
        'subtotal',
        'tax',
        'total',
        'deleted_by'
    ];

    public function purchase(){
        return $this->belongsTo(Purchase::class);
    }

    public function deletedBy(){
        return $this->belongsTo(User::class, 'deleted_by');
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
