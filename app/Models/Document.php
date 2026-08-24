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

    /**
     * Prefijos de serie de las notas de credito: BC01 anula boletas y FC01
     * facturas. Es la serie la que manda, no document_type: hay comprobantes
     * historicos con serie de boleta y document_type de factura.
     */
    const SERIES_NOTA_CREDITO = ['FC', 'BC'];

    public function esNotaCredito(): bool
    {
        return self::serieEsNotaCredito($this->serie);
    }

    /**
     * Boleta de venta (serie B001, B002...). Se decide por SERIE y no por
     * document_type (que miente en historicos); las notas de credito BC
     * tambien empiezan con B, por eso se excluyen primero.
     */
    public function esBoleta(): bool
    {
        return ! $this->esNotaCredito() && str_starts_with((string) $this->serie, 'B');
    }

    /**
     * Etiquetas del tipo de comprobante, en singular. Las claves son las mismas
     * que usa el filtro de /documents (TableDocuments::TIPOS) para que columna y
     * filtro no puedan discrepar.
     */
    const TIPOS_ETIQUETA = [
        'factura'      => 'Factura',
        'boleta'       => 'Boleta',
        'nota_credito' => 'Nota de crédito',
    ];

    /**
     * Clave del tipo de comprobante. Se decide por PREFIJO DE SERIE, nunca por
     * document_type: hay historicos con serie de boleta y document_type de
     * factura, y ese campo los clasificaria al reves. Null si la serie no encaja.
     */
    public function tipoClave(): ?string
    {
        if ($this->esNotaCredito()) {
            return 'nota_credito';
        }

        if ($this->esBoleta()) {
            return 'boleta';
        }

        return str_starts_with((string) $this->serie, 'F') ? 'factura' : null;
    }

    /** Etiqueta lista para mostrar; guion si la serie no corresponde a ningun tipo conocido. */
    public function tipoEtiqueta(): string
    {
        return self::TIPOS_ETIQUETA[$this->tipoClave()] ?? '—';
    }

    /**
     * Datos de la baja comunicada a SUNAT, leidos del nombre del XML de
     * anulacion (formato RUC-RA|RC-AAAAMMDD-n.xml): RA es comunicacion de baja
     * de facturas y RC resumen diario de boletas.
     *
     * Devuelve null si el documento no esta anulado o si la anulacion es
     * antigua y no guardo constancia (7 documentos de junio 2025).
     */
    public function bajaSunat(): ?array
    {
        if ($this->status !== 'anulado' || empty($this->xml_path_anulled)) {
            return null;
        }

        if (! preg_match('/-(R[AC])-(\d{4})(\d{2})(\d{2})-(\d+)\.xml$/', $this->xml_path_anulled, $m)) {
            return null;
        }

        return [
            'tipo'   => $m[1],
            'numero' => "{$m[1]}-{$m[2]}{$m[3]}{$m[4]}-{$m[5]}",
            'fecha'  => "{$m[4]}-{$m[3]}-{$m[2]}",
        ];
    }

    public static function serieEsNotaCredito(?string $serie): bool
    {
        foreach (self::SERIES_NOTA_CREDITO as $prefijo) {
            if (str_starts_with((string) $serie, $prefijo)) {
                return true;
            }
        }

        return false;
    }

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

    /**
     * Notas de credito que anulan este comprobante.
     *
     * hasMany y no hasOne aunque hoy solo pueda haber una: si algun dia se
     * emiten notas parciales, hasOne mostraria solo la ultima en silencio.
     * Se excluyen las que no anulan nada (anuladas o no aceptadas por SUNAT):
     * dejarlas marcaria como anulado un comprobante que sigue vigente.
     */
    public function creditNotes()
    {
        return $this->hasMany(Document::class, 'affected_document_id')
            ->where('status', '!=', 'anulado')
            ->where('status_sunat', 'aceptado')
            ->orderBy('id');
    }
}
