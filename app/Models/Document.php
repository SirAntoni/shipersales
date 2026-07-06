<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{

    const NO_DOMICIALIADO = 0;
    const DNI = 1;
    const CE = 4;
    const RUC = 6;
    const PASAPORTE = 7;

    const DOCUMENT_ACCEPTED = 1;
    const DOCUMENT_REJECTED = 2;

    protected $fillable = [
        'status',
        'document_type',
        'serie',
        'correlative',
        'date',
        'expiration_date',
        'currency',
        'payment_method',
        'subtotal',
        'tax',
        'total',
        'xml_path',
        'xml_path_anulled',
        'cdr_path',
        'cdr_path_anulled',
        'pdf_path',
        'pdf_path_anulled',
        'status_sunat',
        'code',
        'notes',
        'sale_id',
        'affected_document_id',
        'stock_restored',
        'client_id',
        'user_id'
    ];

    protected $casts = [
        'notes' => 'array',
        'stock_restored' => 'boolean',
    ];

    public function documentDetails()
    {
        return $this->hasMany(DocumentDetail::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function affectedDocument()
    {
        return $this->belongsTo(Document::class, 'affected_document_id');
    }
}
