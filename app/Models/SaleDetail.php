<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleDetail extends Model
{
    /**
     * La tabla ya tenia deleted_at desde su migracion original. Al activar el
     * trait, un detalle eliminado desde /sales/{id} deja de contar para stock,
     * kardex, reportes, comisiones y comprobantes, pero la fila se conserva
     * para poder mostrarla deshabilitada en la venta (withTrashed).
     */
    use SoftDeletes;

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
        'shopper',
        'deleted_by'
    ];

    public function sale(){
        return $this->belongsTo(Sale::class);
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
