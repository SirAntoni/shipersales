<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Comprobante de la propuesta del Registro de Compras de SIRE. Es una copia de
 * lo que tiene SUNAT y no se edita: SireService reemplaza el periodo entero en
 * cada descarga.
 */
class SirePurchase extends Model
{
    /** Tipos de comprobante (tabla 10 de SUNAT) que suelen llegar al RCE. */
    public const TIPOS = [
        '00' => 'Otros',
        '01' => 'Factura',
        '02' => 'Recibo por honorarios',
        '03' => 'Boleta',
        '04' => 'Liquidación de compra',
        '07' => 'Nota de crédito',
        '08' => 'Nota de débito',
        '12' => 'Ticket',
        '13' => 'Documento bancario',
        '14' => 'Recibo de servicios públicos',
        '30' => 'Operador de tarjetas',
        '50' => 'DAM de importación',
        '52' => 'Despacho simplificado',
        '53' => 'Declaración de courier',
    ];

    /** Declaraciones de aduanas: SUNAT no informa emisor (llega RUC "0"). */
    public const ADUANAS = ['50', '52', '53'];

    protected $fillable = [
        'period',
        'car',
        'issue_date',
        'due_date',
        'document_type',
        'serie',
        'number',
        'issuer_document_type',
        'issuer_document_number',
        'issuer_name',
        'taxable_base',
        'igv',
        'non_taxable',
        'other_taxes',
        'total',
        'currency',
        'exchange_rate',
        'affected_document',
        'sire_download_id',
    ];

    protected $casts = [
        'issue_date'    => 'date',
        'due_date'      => 'date',
        'taxable_base'  => 'decimal:2',
        'igv'           => 'decimal:2',
        'non_taxable'   => 'decimal:2',
        'other_taxes'   => 'decimal:2',
        'total'         => 'decimal:2',
        'exchange_rate' => 'decimal:3',
    ];

    public static function nombreTipo(string $tipo): string
    {
        return self::TIPOS[$tipo] ?? "Tipo {$tipo}";
    }

    public function tipoNombre(): string
    {
        return self::nombreTipo($this->document_type);
    }

    public function proveedor(): ?string
    {
        return $this->issuer_name ?: (in_array($this->document_type, self::ADUANAS, true) ? 'Aduanas' : null);
    }

    /** Null cuando SUNAT no informa el documento del emisor ("0"). */
    public function rucProveedor(): ?string
    {
        return trim((string) $this->issuer_document_number, '0') === '' ? null : $this->issuer_document_number;
    }

    public function comprobante(): string
    {
        return "{$this->serie}-{$this->number}";
    }
}
