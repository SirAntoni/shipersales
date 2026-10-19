<?php

/**
 * API SIRE de SUNAT, solo para LEER la propuesta del Registro de Compras.
 *
 * El client_id/client_secret se generan en SOL > Credenciales de API SUNAT.
 * El usuario SOL deberia ser uno secundario con perfil SIRE unicamente: si se
 * filtra no sirve para emitir comprobantes. No es el mismo de la facturacion
 * (config/sunat.php).
 */
return [
    'client_id'     => env('SIRE_CLIENT_ID'),
    'client_secret' => env('SIRE_CLIENT_SECRET'),
    'ruc'           => env('SIRE_RUC', env('RUC')),
    'usuario_sol'   => env('SIRE_USUARIO_SOL'),
    'clave_sol'     => env('SIRE_CLAVE_SOL'),
];
