<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name',
        'document_number',
        'document_type',
        'address',
        'phone',
        'email',
        'department_id',
        'province_id',
        'district_id'
    ];

    /** Prefijos de RUC validos: persona natural (10), no domiciliados (15, 16, 17) y persona juridica (20). */
    const PREFIJOS_RUC = ['10', '15', '16', '17', '20'];

    /** Espacios en blanco a limpiar: incluye tabulador y espacio duro (NBSP), que llegan al pegar. */
    private const ESPACIOS = '/[\s\x{00A0}]+/u';

    /** Numero de documento sin ningun espacio: SUNAT valida el valor tal cual (error 2017 en facturas). */
    public static function normalizarNumeroDocumento(?string $valor): ?string
    {
        return $valor === null ? null : preg_replace(self::ESPACIOS, '', $valor);
    }

    /** Nombre sin espacios sobrantes al inicio, al final ni repetidos. */
    public static function normalizarNombre(?string $valor): ?string
    {
        return $valor === null ? null : trim(preg_replace(self::ESPACIOS, ' ', $valor));
    }

    /**
     * RUC valido: 11 digitos, prefijo conocido y digito verificador correcto
     * (modulo 11 con pesos 5,4,3,2,7,6,5,4,3,2). Es lo que SUNAT exige al
     * receptor de una factura; si no se cumple responde el error 2017.
     */
    public static function rucValido(?string $numero): bool
    {
        $numero = (string) self::normalizarNumeroDocumento($numero);

        if (! preg_match('/^\d{11}$/', $numero) || ! in_array(substr($numero, 0, 2), self::PREFIJOS_RUC, true)) {
            return false;
        }

        $pesos = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma  = 0;

        for ($i = 0; $i < 10; $i++) {
            $suma += (int) $numero[$i] * $pesos[$i];
        }

        $digito = 11 - ($suma % 11);

        if ($digito >= 10) {
            $digito -= 10; // 10 -> 0, 11 -> 1
        }

        return $digito === (int) $numero[10];
    }

    /** Por que este cliente no puede recibir una factura (ni su nota de credito), o null si puede. */
    public function motivoNoFacturable(): ?string
    {
        if ($this->document_type !== 'RUC') {
            return "No puede emitir una factura a un cliente con un documento diferente a RUC ({$this->name}: {$this->document_type}).";
        }

        if (! self::rucValido($this->document_number)) {
            return "El RUC del cliente {$this->name} (\"{$this->document_number}\") no es válido: debe tener 11 dígitos sin espacios, empezar con 10, 15, 16, 17 o 20 y tener el dígito verificador correcto. Corríjalo en Clientes antes de emitir la factura.";
        }

        return null;
    }

    /** Todo alta o edicion (formulario, venta rapida, cotizacion) guarda el numero limpio. */
    protected function documentNumber(): Attribute
    {
        return Attribute::make(set: fn ($valor) => self::normalizarNumeroDocumento($valor));
    }

    protected function name(): Attribute
    {
        return Attribute::make(set: fn ($valor) => self::normalizarNombre($valor));
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function sales(){
        return $this->hasMany(Sale::class);
    }

    public function documents(){
        return $this->hasMany(Document::class);
    }
}
