<?php

namespace App\Services;

/**
 * Formato del txt de la propuesta del Registro de Compras (RCE) de SIRE:
 * separado por "|" y con una primera fila de encabezados. El orden oficial es
 * el del Anexo 8 (8.4) de la RS 040-2022: 41 campos y luego los de libre uso.
 *
 * Las columnas se buscan por su encabezado y no por posicion: asi un cambio de
 * orden o una columna nueva no corre los importes en silencio. Solo si el
 * archivo viene sin encabezados se usan las posiciones del anexo. Si falta una
 * columna obligatoria, la lectura falla en vez de guardar datos a medias.
 */
class SireRceFormato
{
    /** Campo => encabezados posibles, ya normalizados (minusculas, sin tildes ni signos). */
    private const ENCABEZADOS = [
        'periodo'                => ['periodo'],
        'car'                    => ['carsunat', 'car'],
        'issue_date'             => ['fechadeemision', 'fechaemision'],
        'due_date'               => ['fechavctopago', 'fechavencimientopago', 'fechavencimiento'],
        'document_type'          => ['tipocpdoc', 'tipocp', 'tipodecp', 'tipocomprobante'],
        'serie'                  => ['seriedelcdp', 'seriecdp', 'seriecp', 'serie'],
        'number'                 => ['nrocpodocnroinicialrango', 'nrocpodoc', 'nrocp', 'numerocp'],
        'issuer_document_type'   => ['tipodocidentidad'],
        'issuer_document_number' => ['nrodocidentidad'],
        'base_dg'                => ['bigravadodg'],
        'igv_dg'                 => ['igvipmdg'],
        'base_dgng'              => ['bigravadodgng'],
        'igv_dgng'               => ['igvipmdgng'],
        'base_dng'               => ['bigravadodng'],
        'igv_dng'                => ['igvipmdng'],
        'non_taxable'            => ['valoradqng'],
        'isc'                    => ['isc'],
        'icbper'                 => ['icbper'],
        'otros'                  => ['otrostribcargos', 'otrostributoscargos', 'otrostributos'],
        'total'                  => ['totalcp'],
        'currency'               => ['moneda'],
        'exchange_rate'          => ['tipodecambio', 'tipocambio'],
        'mod_tipo'               => ['tipocpmodificado'],
        'mod_serie'              => ['seriecpmodificado'],
        'mod_numero'             => ['nrocpmodificado'],
    ];

    /** Posicion (desde 0) de cada campo en el Anexo 8, para archivos sin encabezados. */
    private const POSICIONES = [
        'periodo'                => 2,
        'car'                    => 3,
        'issue_date'             => 4,
        'due_date'               => 5,
        'document_type'          => 6,
        'serie'                  => 7,
        'number'                 => 9,
        'issuer_document_type'   => 11,
        'issuer_document_number' => 12,
        'issuer_name'            => 13,
        'base_dg'                => 14,
        'igv_dg'                 => 15,
        'base_dgng'              => 16,
        'igv_dgng'               => 17,
        'base_dng'               => 18,
        'igv_dng'                => 19,
        'non_taxable'            => 20,
        'isc'                    => 21,
        'icbper'                 => 22,
        'otros'                  => 23,
        'total'                  => 24,
        'currency'               => 25,
        'exchange_rate'          => 26,
        'mod_tipo'               => 28,
        'mod_serie'              => 29,
        'mod_numero'             => 31,
    ];

    /** Sin estas no se guarda nada: son las que identifican el comprobante y su importe. */
    private const OBLIGATORIAS = ['issue_date', 'document_type', 'serie', 'number', 'issuer_document_number', 'total'];

    /** Todas las lineas de un txt de la propuesta, como filas de sire_purchases (mas 'periodo'). */
    public static function leer(string $texto): array
    {
        $lineas = array_values(array_filter(
            preg_split('/\r\n|\n|\r/', $texto),
            fn ($l) => trim($l) !== ''
        ));

        if ($lineas === []) {
            return [];
        }

        // El txt va separado por "|"; el csv que exporta SIRE web, por comas
        // (con comillas si un nombre lleva coma).
        $partir = str_contains($lineas[0], '|')
            ? fn ($linea) => explode('|', $linea)
            : fn ($linea) => str_getcsv($linea, ',', '"', '');

        // La exportacion trae encabezados (la primera celda dice "RUC"); el
        // formato de reemplazo de SUNAT no.
        $primera = $partir($lineas[0]);
        $conEncabezados = self::normalizar($primera[0] ?? '') === 'ruc';
        $columnas = $conEncabezados ? self::columnas($primera) : self::POSICIONES;
        $desde = $conEncabezados ? 1 : 0;

        $filas = [];

        foreach (array_slice($lineas, $desde) as $n => $linea) {
            $filas[] = self::fila($partir($linea), $columnas, $n + $desde + 1);
        }

        return $filas;
    }

    /**
     * Indice de cada campo segun la fila de encabezados.
     *
     * @return array<string,int>
     */
    private static function columnas(array $encabezados): array
    {
        $normalizados = array_map([self::class, 'normalizar'], $encabezados);
        $columnas = [];

        foreach (self::ENCABEZADOS as $campo => $alias) {
            foreach ($alias as $nombre) {
                $indice = array_search($nombre, $normalizados, true);

                if ($indice !== false) {
                    $columnas[$campo] = $indice;
                    break;
                }
            }
        }

        // Hay dos "razon social": la del propio RUC (2da columna) y la del
        // proveedor, que va justo despues de su numero de documento.
        if (isset($columnas['issuer_document_number'])) {
            $columnas['issuer_name'] = $columnas['issuer_document_number'] + 1;
        }

        $faltan = array_diff(self::OBLIGATORIAS, array_keys($columnas));

        if ($faltan) {
            throw new \UnexpectedValueException('El archivo de SUNAT no tiene el formato esperado: faltan las columnas '
                . implode(', ', $faltan) . '. Encabezados recibidos: ' . mb_substr(implode(' | ', $encabezados), 0, 300));
        }

        return $columnas;
    }

    /** Una linea del txt convertida a columnas de sire_purchases (mas 'periodo' para validar). */
    private static function fila(array $valores, array $columnas, int $linea): array
    {
        $v = fn (string $campo) => isset($columnas[$campo]) ? trim((string) ($valores[$columnas[$campo]] ?? '')) : '';
        $monto = fn (string $campo) => self::monto($v($campo));

        $fila = [
            'periodo'                => preg_replace('/\D/', '', $v('periodo')) ?: null,
            'car'                    => $v('car') ?: null,
            'issue_date'             => self::fecha($v('issue_date')),
            'due_date'               => self::fecha($v('due_date')),
            'document_type'          => str_pad($v('document_type'), 2, '0', STR_PAD_LEFT),
            'serie'                  => $v('serie'),
            'number'                 => $v('number'),
            'issuer_document_type'   => $v('issuer_document_type') ?: null,
            'issuer_document_number' => $v('issuer_document_number'),
            'issuer_name'            => mb_substr($v('issuer_name'), 0, 255) ?: null,
            'taxable_base'           => round($monto('base_dg') + $monto('base_dgng') + $monto('base_dng'), 2),
            'igv'                    => round($monto('igv_dg') + $monto('igv_dgng') + $monto('igv_dng'), 2),
            'non_taxable'            => round($monto('non_taxable'), 2),
            'other_taxes'            => round($monto('isc') + $monto('icbper') + $monto('otros'), 2),
            'total'                  => round($monto('total'), 2),
            'currency'               => strtoupper($v('currency')) ?: 'PEN',
            'exchange_rate'          => $v('exchange_rate') !== '' ? (self::monto($v('exchange_rate')) ?: null) : null,
            'affected_document'      => self::documentoModificado($v('mod_tipo'), $v('mod_serie'), $v('mod_numero')),
        ];

        if (! $fila['issue_date'] || $fila['serie'] === '' || $fila['number'] === '' || $fila['issuer_document_number'] === '') {
            throw new \UnexpectedValueException("Línea {$linea} del archivo de SUNAT incompleta: " . mb_substr(implode('|', $valores), 0, 200));
        }

        // Las notas de credito restan: SUNAT ya las manda en negativo. Si
        // alguna llegara en positivo se invierte para que el total no la sume.
        if ($fila['document_type'] === '07' && $fila['total'] > 0) {
            foreach (['taxable_base', 'igv', 'non_taxable', 'other_taxes', 'total'] as $campo) {
                $fila[$campo] = $fila[$campo] == 0 ? 0.0 : -$fila[$campo];
            }
        }

        return $fila;
    }

    public static function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto), 'UTF-8');
        $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);

        return preg_replace('/[^a-z0-9]/', '', $texto);
    }

    /** "1,234.50", "1234.50" o "1234,50" -> 1234.5. Vacio -> 0. */
    private static function monto(string $valor): float
    {
        $valor = str_replace([' ', "\u{00A0}"], '', $valor);

        if ($valor === '') {
            return 0.0;
        }

        if (str_contains($valor, ',') && str_contains($valor, '.')) {
            $valor = str_replace(',', '', $valor);
        } elseif (str_contains($valor, ',')) {
            $valor = str_replace(',', '.', $valor);
        }

        if (! is_numeric($valor)) {
            throw new \UnexpectedValueException("Importe no numérico en el archivo de SUNAT: {$valor}");
        }

        return (float) $valor;
    }

    /** dd/mm/aaaa (formato de SUNAT) o aaaa-mm-dd -> aaaa-mm-dd. */
    private static function fecha(string $valor): ?string
    {
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $valor, $m)) {
            return checkdate((int) $m[2], (int) $m[1], (int) $m[3]) ? "{$m[3]}-{$m[2]}-{$m[1]}" : null;
        }

        if (preg_match('#^(\d{4})-(\d{2})-(\d{2})$#', $valor, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $valor : null;
        }

        return null;
    }

    private static function documentoModificado(string $tipo, string $serie, string $numero): ?string
    {
        if ($serie === '' && $numero === '') {
            return null;
        }

        return mb_substr(trim(($tipo !== '' ? str_pad($tipo, 2, '0', STR_PAD_LEFT) . ' ' : '') . "{$serie}-{$numero}"), 0, 40);
    }
}
