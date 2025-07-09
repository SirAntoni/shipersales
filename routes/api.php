<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aquí puedes registrar las rutas de tu API. Estas rutas
| son cargadas por el RouteServiceProvider y todas
| estarán en el grupo de middleware "api".
|
*/

Route::post('webhook/handler', [WebhookController::class, 'handle'])
    ->name('api.webhook.handler');
