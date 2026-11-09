<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Consulta de DNI, RUC y carné de extranjería en api.factiliza.com.
 * Reemplaza a MIGO, que dejó de funcionar en nov-2026. Es la misma integración que tiene Prestamoh.
 *
 * GET {base}/{dni|ruc|cee}/info/{numero} con token Bearer (config services.factiliza.*).
 * La API responde {status, success, message, data}: 400 si el formato no es válido, 404 si el RUC
 * no existe y 401 si el token no es válido. Para un DNI inexistente puede devolver 200 con datos
 * vacíos, por eso se exige un nombre no vacío.
 *
 * El nombre se arma igual que lo entregaba MIGO y como ya está guardado en clients:
 * "APELLIDO_PATERNO APELLIDO_MATERNO NOMBRES" en mayúsculas, y la razón social tal cual para un RUC.
 */
class FactilizaService
{
    /** Tipo de documento del cliente => recurso de la API y cantidad de dígitos (null: la valida la API). */
    public const TIPOS = [
        'DNI' => ['recurso' => 'dni', 'digitos' => 8],
        'RUC' => ['recurso' => 'ruc', 'digitos' => 11],
        'CE'  => ['recurso' => 'cee', 'digitos' => null],
    ];

    /** Cada consulta gasta un crédito: el mismo número no se vuelve a consultar en 24 h. */
    private const CACHE_TTL = 86400;

    public static function soporta(?string $tipo): bool
    {
        return isset(self::TIPOS[(string) $tipo]);
    }

    /**
     * Nombre o razón social oficial del documento.
     *
     * @param  bool  $esperar  false al emitir un comprobante: un intento corto sin reintento, para que
     *                         una caída de Factiliza no deje esperando la emisión.
     * @return array{ok: bool, nombre: ?string, error: ?string}
     */
    public function nombre(?string $tipo, ?string $numero, bool $esperar = true): array
    {
        $tipo   = strtoupper(trim((string) $tipo));
        $numero = trim((string) $numero);

        if (! self::soporta($tipo)) {
            return $this->error('La consulta solo está disponible para DNI, RUC y carné de extranjería (CE). Ingrese el nombre a mano.');
        }

        $config = self::TIPOS[$tipo];

        if ($numero === '') {
            return $this->error("Ingrese el número de {$tipo} a consultar.");
        }

        if ($config['digitos'] !== null && ! preg_match('/^\d{' . $config['digitos'] . '}$/', $numero)) {
            return $this->error("El {$tipo} debe tener exactamente {$config['digitos']} dígitos.");
        }

        $token = (string) config('services.factiliza.token');
        if ($token === '') {
            Log::warning('Factiliza: falta FACTILIZA_TOKEN en el .env');

            return $this->error('La consulta de documentos no está configurada (falta FACTILIZA_TOKEN). Ingrese el nombre a mano.');
        }

        $cacheKey = "factiliza:{$config['recurso']}:{$numero}";
        if (is_array($data = Cache::get($cacheKey)) && ($nombre = $this->armarNombre($tipo, $data)) !== '') {
            return ['ok' => true, 'nombre' => $nombre, 'error' => null];
        }

        try {
            $base    = rtrim((string) config('services.factiliza.base'), '/');
            $request = Http::withToken($token)
                ->acceptJson()
                ->connectTimeout($esperar ? 5 : 3)
                ->timeout($esperar ? 10 : 5);

            if ($esperar) {
                // Prestamoh (06-nov-2026): Factiliza se cuelga a ratos (cURL 28); casi siempre responde al segundo intento.
                $request = $request->retry(2, 300, fn ($e) => $e instanceof ConnectionException, throw: false);
            }

            $response = $request->get("{$base}/{$config['recurso']}/info/" . rawurlencode($numero));
        } catch (\Throwable $e) {
            Log::warning('Factiliza: excepción de red', ['recurso' => $config['recurso'], 'error' => $e->getMessage()]);

            return $this->error('Factiliza (el servicio de consulta de DNI/RUC) no respondió. Vuelva a intentar en unos minutos o ingrese el nombre a mano.');
        }

        $json   = $response->json();
        $data   = is_array($json) && is_array($json['data'] ?? null) ? $json['data'] : null;
        $nombre = ($response->successful() && ($json['success'] ?? false) === true && $data !== null)
            ? $this->armarNombre($tipo, $data)
            : '';

        if ($nombre === '') {
            $mensaje = match (true) {
                $response->status() === 401 => 'El token de Factiliza no es válido o expiró: avise al administrador.',
                $response->status() === 402,
                $response->status() === 429 => 'Se agotaron las consultas de Factiliza: avise al administrador.',
                default                     => "No se encontraron datos para el {$tipo} {$numero}.",
            };

            Log::warning('Factiliza: consulta sin datos', [
                'recurso' => $config['recurso'],
                'http'    => $response->status(),
                'mensaje' => is_array($json) ? ($json['message'] ?? null) : substr($response->body(), 0, 200),
            ]);

            return $this->error($mensaje);
        }

        Cache::put($cacheKey, $data, self::CACHE_TTL);

        return ['ok' => true, 'nombre' => $nombre, 'error' => null];
    }

    /**
     * Nombre que va en el comprobante: el oficial si Factiliza responde (solo DNI y RUC, como antes
     * con MIGO); si no, el registrado en el cliente. Nunca frena la emisión.
     */
    public function nombreParaComprobante(Client $client): string
    {
        if (! in_array($client->document_type, ['DNI', 'RUC'], true)) {
            return (string) $client->name;
        }

        $consulta = $this->nombre($client->document_type, $client->document_number, esperar: false);

        if (! $consulta['ok']) {
            Log::warning("Factiliza no disponible al emitir, se usa el nombre registrado del cliente {$client->id}: {$consulta['error']}");

            return (string) $client->name;
        }

        return $consulta['nombre'];
    }

    private function armarNombre(string $tipo, array $data): string
    {
        if ($tipo === 'RUC') {
            return $this->limpiar($data['nombre_o_razon_social'] ?? '');
        }

        $nombre = $this->limpiar(implode(' ', [
            $data['apellido_paterno'] ?? '',
            $data['apellido_materno'] ?? '',
            $data['nombres'] ?? '',
        ]));

        // Por si algún plan solo trae el nombre completo ("APELLIDOS, NOMBRES").
        return $nombre !== '' ? $nombre : $this->limpiar(str_replace(',', ' ', (string) ($data['nombre_completo'] ?? '')));
    }

    /** Mayúsculas, un solo espacio entre palabras y doble codificación reparada ("PUÃ‘A" → "PUÑA"). */
    private function limpiar(mixed $valor): string
    {
        $valor = $this->reparar(trim((string) $valor));

        return mb_strtoupper(trim(preg_replace('/\s+/u', ' ', $valor)), 'UTF-8');
    }

    /** Revierte UTF-8 leído como latin1 solo cuando detecta el patrón, para no dañar texto correcto. */
    private function reparar(string $valor): string
    {
        if ($valor === '' || ! preg_match('/Ã[\x{0080}-\x{00BF}]/u', $valor)) {
            return $valor;
        }

        $reparado = @mb_convert_encoding($valor, 'ISO-8859-1', 'UTF-8');

        return ($reparado !== false && $reparado !== '' && mb_check_encoding($reparado, 'UTF-8')) ? $reparado : $valor;
    }

    private function error(string $mensaje): array
    {
        return ['ok' => false, 'nombre' => null, 'error' => $mensaje];
    }
}
