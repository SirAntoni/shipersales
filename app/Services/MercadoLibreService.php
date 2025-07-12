<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class MercadoLibreService
{
    protected string $baseUrl;
    protected string $client_id;
    protected string $client_secret;
    protected string $refresh_token;

    public function __construct()
    {
        $this->baseUrl = config('mercadolibre.mercadolibre_api');
        $this->client_id   = config('mercadolibre.client_id');
        $this->client_secret   = config('mercadolibre.client_secret');
        $this->refresh_token   = config('mercadolibre.refresh_token');
    }


    private function getAccessToken():string{

        $cacheKey = 'ml_access_token';

        if (Cache::has($cacheKey)) {
            Log::info('Token en cache');
            return Cache::get($cacheKey);
        }

        $response = Http::asForm()->post($this->baseUrl . '/oauth/token', [
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'refresh_token' => $this->refresh_token
        ]);

        if ($response->failed()) {
            \Log::error('Error refrescando token ML', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('No se pudo refrescar el token de MercadoLibre');
        }


        $data = $response->json();

        Cache::put($cacheKey, $data['access_token'], now()->addMinutes(300));

        if (isset($data['refresh_token'])) {

            Cache::put('ml_refresh_token', $data['refresh_token'], now()->addDays(30));

        }

        Log::info('Token refrescado', $data);
        return $data['access_token'];


    }

    public function getOrder(string $resource): array
    {
        $endpoint = $this->baseUrl . trim($resource);
        $token = $this->getAccessToken();

        try {
            $response = Http::withToken($token)
            ->baseUrl($this->baseUrl)
                ->acceptJson()
                ->get($endpoint);

            if ($response->status() === 404) {
                return [];
            }

            if ($response->clientError()) {
                throw new \RuntimeException("Error de cliente en Migo ({$response->status()})");
            }

            if ($response->serverError()) {
                throw new \RuntimeException("Error de servidor en Migo ({$response->status()})");
            }

            $contentType = $response->header('Content-Type', '');
            if (! Str::contains($contentType, 'application/json')) {
                throw new \RuntimeException(
                    "Respuesta inesperada de Migo (status {$response->status()}): "
                    . substr($response->body(), 0, 200)
                );
            }

            return $response->json();

        } catch (RequestException $e) {
            Log::error("Error POST a {$endpoint}", [
                'url'     => $e->request->url(),
                'payload' => $payload,
                'status'  => $e->response?->status(),
                'body'    => $e->response?->body(),
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

