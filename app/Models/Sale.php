<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{

    const SALE_CANCELED = 0;
    const SALE_PENDING = 1;
    const SALE_APPROVED = 2;
    const SALE_OBSERVATION = 3;
    const SALE_SQUARE = 4;
    const SALE_RETURN = 5;

    /**
     * Prefijos con los que la anulacion de un comprobante (baja o nota de
     * credito) deja su rastro en deletion_reason. Distinguen esas ventas de
     * las anuladas a mano desde /sales.
     */
    const MOTIVOS_ANULACION_POR_COMPROBANTE = [
        'Baja del comprobante ',
        'Nota de crédito ',
        'Devolución con nota de crédito ',
    ];

    protected $fillable = [
        'number',
        'client_id',
        'user_id',
        'voucher_type',
        'voucher_serie',
        'voucher_number',
        'date',
        'subtotal',
        'tax',
        'total',
        'contact_id',
        'payment_method_id',
        'status',
        'webhook_imported',
        'delivery',
        'delivery_fee',
        'observations',
        'deletion_reason',
        'deletion_date'
    ];


    /** Ventas anuladas porque se anulo su comprobante (no las anuladas a mano). */
    public function scopeAnuladaPorComprobante($query)
    {
        return $query->where('status', self::SALE_CANCELED)
            ->where(function ($q) {
                foreach (self::MOTIVOS_ANULACION_POR_COMPROBANTE as $prefijo) {
                    $q->orWhere('deletion_reason', 'LIKE', $prefijo . '%');
                }
            });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class);
    }

    /**
     * Comprobante vigente de la venta: el mas reciente que no este anulado,
     * rechazado ni cubierto por una nota de credito. Una venta acumula varios
     * comprobantes cuando el anterior se anulo sin reponer stock y se emitio
     * otro, asi que no vale "el primero que aparezca".
     */
    public function document()
    {
        return $this->hasOne(Document::class)->ofMany(['id' => 'max'], function ($query) {
            $query->vigente();
        });
    }

    /** Todos los comprobantes de la venta, vigentes o no, incluidas notas de credito. */
    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Una nota de credito que SI repuso stock ya devolvio las unidades de esta
     * venta: quien la anule despues no debe reponerlas otra vez. Una NC sin
     * reposicion no cuenta (la mercaderia sigue afuera), por eso se mira
     * stock_restored y no el status 'nota_credito' del comprobante afectado.
     * Es el unico criterio: lo usan ventas, devoluciones, comisiones y la baja.
     */
    public function stockRepuestoPorNotaCredito(): bool
    {
        return $this->documents()
            ->whereNotNull('affected_document_id')
            ->where('stock_restored', true)
            ->exists();
    }

}
