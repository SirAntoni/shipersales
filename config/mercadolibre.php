<?php

return [
    // Desactivada por defecto: el refresh token de prod está vencido (invalid_grant)
    // y cada notificación fallaba. Para reactivarla: MERCADOLIBRE_ENABLED=true
    'enabled' => (bool) env('MERCADOLIBRE_ENABLED', false),
    'mercadolibre_api' => env('MERCADOLIBRE_API', 'https://api.mercadolibre.com/'),
    'client_id' => env('MERCADOLIBRE_CLIENT_ID', ''),
    'client_secret' => env('MERCADOLIBRE_CLIENT_SECRET', ''),
    'refresh_token' => env('MERCADOLIBRE_REFRESH_TOKEN', '')
];
