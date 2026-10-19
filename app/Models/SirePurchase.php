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

    /** Tipo de documento del emisor (catalogo 06 de SUNAT). */
    public const DOCUMENTOS_IDENTIDAD = [
        '0' => 'Sin documento',
        '1' => 'DNI',
        '4' => 'Carné de extranjería',
        '6' => 'RUC',
        '7' => 'Pasaporte',
    ];

    /** Motivo de las notas de credito (catalogo 09 de SUNAT). */
    public const MOTIVOS_NOTA_CREDITO = [
        '01' => 'Anulación de la operación',
        '02' => 'Anulación por error en el RUC',
        '03' => 'Corrección por error en la descripción',
        '04' => 'Descuento global',
        '05' => 'Descuento por ítem',
        '06' => 'Devolución total',
        '07' => 'Devolución por ítem',
        '08' => 'Bonificación',
        '09' => 'Disminución en el valor',
        '10' => 'Otros conceptos',
        '11' => 'Ajustes de operaciones de exportación',
        '12' => 'Ajustes afectos al IVAP',
        '13' => 'Ajuste de montos o fechas de pago',
    ];

    /** Motivo de las notas de debito (catalogo 10 de SUNAT). */
    public const MOTIVOS_NOTA_DEBITO = [
        '01' => 'Intereses por mora',
        '02' => 'Aumento en el valor',
        '03' => 'Penalidades u otros conceptos',
        '11' => 'Ajustes de operaciones de exportación',
        '12' => 'Ajustes afectos al IVAP',
    ];

    protected $fillable = [
        'period',
        'car',
        'issue_date',
        'due_date',
        'document_type',
        'serie',
        'number',
        'dam_year',
        'issuer_document_type',
        'issuer_document_number',
        'issuer_name',
        'taxable_base',
        'igv',
        'non_taxable',
        'other_taxes',
        'total',
        'taxable_base_dg',
        'igv_dg',
        'taxable_base_dgng',
        'igv_dgng',
        'taxable_base_dng',
        'igv_dng',
        'isc',
        'icbper',
        'other_charges',
        'currency',
        'exchange_rate',
        'affected_document',
        'affected_issue_date',
        'note_type',
        'detraction',
        'sire_download_id',
    ];

    protected $casts = [
        'issue_date'          => 'date',
        'due_date'            => 'date',
        'affected_issue_date' => 'date',
        'taxable_base'        => 'decimal:2',
        'igv'                 => 'decimal:2',
        'non_taxable'         => 'decimal:2',
        'other_taxes'         => 'decimal:2',
        'total'               => 'decimal:2',
        'taxable_base_dg'     => 'decimal:2',
        'igv_dg'              => 'decimal:2',
        'taxable_base_dgng'   => 'decimal:2',
        'igv_dgng'            => 'decimal:2',
        'taxable_base_dng'    => 'decimal:2',
        'igv_dng'             => 'decimal:2',
        'isc'                 => 'decimal:2',
        'icbper'              => 'decimal:2',
        'other_charges'       => 'decimal:2',
        'exchange_rate'       => 'decimal:3',
        'detraction'          => 'boolean',
    ];

    public function download()
    {
        return $this->belongsTo(SireDownload::class, 'sire_download_id');
    }

    /**
     * Nombre del emisor de un RUC segun su comprobante mas reciente, de
     * cualquier periodo: el filtro por proveedor se mantiene al cambiar de mes.
     */
    public static function nombreDeProveedor(string $ruc): string
    {
        $compra = self::where('issuer_document_number', $ruc)
            ->orderByDesc('period')
            ->first(['issuer_name', 'document_type', 'issuer_document_number']);

        return $compra?->proveedor() ?? $ruc;
    }

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

    public function documentoIdentidad(): ?string
    {
        return self::DOCUMENTOS_IDENTIDAD[(string) $this->issuer_document_type] ?? null;
    }

    public function motivoNota(): ?string
    {
        $motivos = match ($this->document_type) {
            '07'    => self::MOTIVOS_NOTA_CREDITO,
            '08'    => self::MOTIVOS_NOTA_DEBITO,
            default => [],
        };

        return $this->note_type ? ($motivos[$this->note_type] ?? "Código {$this->note_type}") : null;
    }

    /**
     * Base e IGV por destino de la compra, solo las lineas con importe. Vacio
     * si la fila se descargo antes de guardar el desglose: no suma lo mismo
     * que la base total.
     *
     * @return array<int, array{destino: string, base: float, igv: float}>
     */
    public function desglose(): array
    {
        $lineas = array_filter([
            ['destino' => 'Operaciones gravadas', 'base' => (float) $this->taxable_base_dg, 'igv' => (float) $this->igv_dg],
            ['destino' => 'Operaciones gravadas y no gravadas', 'base' => (float) $this->taxable_base_dgng, 'igv' => (float) $this->igv_dgng],
            ['destino' => 'Operaciones no gravadas', 'base' => (float) $this->taxable_base_dng, 'igv' => (float) $this->igv_dng],
        ], fn ($l) => $l['base'] != 0 || $l['igv'] != 0);

        $cuadra = abs(array_sum(array_column($lineas, 'base')) - (float) $this->taxable_base) < 0.01
            && abs(array_sum(array_column($lineas, 'igv')) - (float) $this->igv) < 0.01;

        return $cuadra ? array_values($lineas) : [];
    }

    /** Total en la moneda original, deshaciendo la conversion de SUNAT (aproximado). */
    public function totalEnMonedaOriginal(): ?float
    {
        if ($this->currency === 'PEN' || (float) $this->exchange_rate <= 0) {
            return null;
        }

        return round((float) $this->total / (float) $this->exchange_rate, 2);
    }
}
