<?php

namespace App\Services;

use App\Models\SireDownload;
use App\Models\SirePurchase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Lectura de la propuesta del Registro de Compras (RCE) de SIRE. Solo lee: no
 * acepta ni reemplaza la propuesta ni registra nada ante SUNAT.
 *
 * SUNAT responde por ticket: se pide el periodo, se consulta el ticket hasta
 * que termina y recien ahi se descargan uno o mas ZIP con un txt. En cPanel no
 * hay worker de colas (y no se deben usar mientras queden los jobs maliciosos),
 * asi que cada paso es una peticion corta: solicitar() y avanzar(), que la
 * pantalla va llamando con wire:poll.
 *
 * Referencia: "Manual de servicios Web Api - SIRE Compras" v28, secciones 5.1,
 * 5.31, 5.32 y 5.34. Los codigos de estado del ticket estan en el Anexo III del
 * manual del RVIE (el de Compras lo cita pero no lo trae).
 */
class SireService
{
    private const URL_TOKEN = 'https://api-seguridad.sunat.gob.pe/v1/clientessol/%s/oauth2/token/';

    private const URL_API = 'https://api-sire.sunat.gob.pe/v1/contribuyente/migeigv/libros/';

    private const LIBRO_COMPRAS = '080000';

    /** Origen "servicio web"; sin el, SUNAT responde 422. */
    private const ORIGEN_API = '2';

    /** 0 = txt (en Compras; el RVIE usa otros codigos). */
    private const ARCHIVO_TXT = '0';

    /** 04 procesado sin errores, 06 terminado. El resto (01, 02, 05) sigue en curso. */
    private const ESTADOS_TERMINADO = ['04', '06'];

    /** 03 procesado con errores; 07, 08 y 10 se han visto en produccion como fallos. */
    private const ESTADOS_ERROR = ['03', '07', '08', '10'];

    /** "No existen documentos para exportar" / "No se ha encontrado informacion": periodo sin compras. */
    private const ERRORES_SIN_DOCUMENTOS = ['1518', '1070'];

    /** Un ticket que no termina en este tiempo se da por perdido para poder pedir otro. */
    private const MINUTOS_MAX_TICKET = 30;

    private const CACHE_TOKEN = 'sire_access_token';

    /**
     * SUNAT entrega el token (usuario y credencial correctos) pero SIRE lo
     * rechaza con un 401 de nginx: al usuario SOL o a la credencial les falta
     * el acceso a SIRE. No mejora reintentando.
     */
    /** Codigo de las excepciones por credenciales o accesos: cortan la descarga sin reintentar. */
    private const CODIGO_SIN_ACCESO = 401;

    private const SIN_ACCESO = 'SUNAT aceptó el usuario SOL pero no le da acceso a SIRE (HTTP 401). Revise que el usuario tenga la opción del Registro de Compras de SIRE y que la credencial de API tenga marcada la URI "MIGE RCE y RVIE - SIRE".';

    public static function configurado(): bool
    {
        return filled(config('sire.client_id'))
            && filled(config('sire.client_secret'))
            && filled(config('sire.ruc'))
            && filled(config('sire.usuario_sol'))
            && filled(config('sire.clave_sol'));
    }

    /** La propuesta de un mes se publica desde el dia 2 del mes siguiente. */
    public static function ultimoPeriodoDisponible(?Carbon $hoy = null): string
    {
        $hoy = ($hoy ?? now())->copy()->startOfDay();

        return $hoy->copy()->startOfMonth()->subMonthsNoOverflow($hoy->day >= 2 ? 1 : 2)->format('Ym');
    }

    /**
     * Pide el periodo a SUNAT y deja el ticket registrado para avanzar(). Si
     * SUNAT dice que no hay comprobantes, el periodo queda listo y vacio.
     */
    public function solicitar(string $periodo, ?int $userId = null): SireDownload
    {
        $respuesta = $this->get("rce/propuesta/web/propuesta/{$periodo}/exportacioncomprobantepropuesta", [
            'codTipoArchivo' => self::ARCHIVO_TXT,
            'codOrigenEnvio' => self::ORIGEN_API,
        ], lanzar: false);

        if (self::sinDocumentos($respuesta)) {
            $descarga = SireDownload::create([
                'period'      => $periodo,
                'status'      => SireDownload::STATUS_DONE,
                'user_id'     => $userId,
                'finished_at' => now(),
            ]);
            $descarga->update(['total_rows' => $this->importar($periodo, [], $descarga)]);

            return $descarga;
        }

        if ($respuesta->failed()) {
            throw new \RuntimeException(self::mensajeDeError($respuesta));
        }

        $ticket = $respuesta->json('numTicket');

        if (! $ticket) {
            throw new \RuntimeException('SUNAT no devolvió número de ticket: ' . mb_substr($respuesta->body(), 0, 200));
        }

        return SireDownload::create([
            'period'  => $periodo,
            'ticket'  => (string) $ticket,
            'status'  => SireDownload::STATUS_REQUESTED,
            'user_id' => $userId,
        ]);
    }

    /**
     * Un paso del ticket: si SUNAT ya termino, descarga los ZIP e importa el
     * periodo; si no, lo deja como esta para la proxima consulta. Los errores
     * de red, los 429 y los 500 de SUNAT (frecuentes) no matan el ticket: se
     * reintenta en la siguiente vuelta hasta MINUTOS_MAX_TICKET.
     */
    public function avanzar(SireDownload $descarga): SireDownload
    {
        if ($descarga->status !== SireDownload::STATUS_REQUESTED) {
            return $descarga;
        }

        // Dos pestanas con la misma pantalla abierta harian la misma descarga a la vez.
        $lock = Cache::lock("sire_descarga_{$descarga->id}", 120);

        if (! $lock->get()) {
            return $descarga;
        }

        try {
            $descarga->refresh();

            if ($descarga->status !== SireDownload::STATUS_REQUESTED) {
                return $descarga;
            }

            $proceso = $this->estadoTicket($descarga);
            $estado = (string) ($proceso['codEstadoProceso'] ?? '');

            if (in_array($estado, self::ESTADOS_ERROR, true)) {
                return $this->fallar($descarga, 'SUNAT no pudo generar el archivo: ' . ($proceso['desEstadoProceso'] ?? "estado {$estado}") . '.');
            }

            if (in_array($estado, self::ESTADOS_TERMINADO, true)) {
                // Terminado sin archivos = el periodo no tiene comprobantes.
                $contenidos = array_map(
                    fn (array $archivo) => $this->descargarArchivo($descarga, $proceso, $archivo),
                    self::archivosDelProceso($proceso)
                );

                $descarga->update([
                    'status'      => SireDownload::STATUS_DONE,
                    'total_rows'  => $this->importar($descarga->period, $contenidos, $descarga),
                    'message'     => null,
                    'finished_at' => now(),
                ]);

                return $descarga;
            }
        } catch (\Throwable $e) {
            Log::warning('SIRE: fallo al consultar o descargar el ticket', [
                'descarga' => $descarga->id,
                'ticket'   => $descarga->ticket,
                'error'    => $e->getMessage(),
            ]);

            // Un archivo que no se puede leer o credenciales sin acceso a SIRE
            // no mejoran reintentando.
            if ($e instanceof \UnexpectedValueException || $e->getCode() === self::CODIGO_SIN_ACCESO) {
                return $this->fallar($descarga, $e->getMessage());
            }

            $descarga->message = mb_substr('Último intento: ' . $e->getMessage(), 0, 500);
            $descarga->save();
        } finally {
            $lock->release();
        }

        if ($descarga->created_at->lt(now()->subMinutes(self::MINUTOS_MAX_TICKET))) {
            return $this->fallar($descarga, 'SUNAT no terminó el ticket en ' . self::MINUTOS_MAX_TICKET . ' minutos. Vuelva a descargar el periodo.');
        }

        return $descarga;
    }

    /**
     * Reemplaza el periodo entero con lo que traen los archivos (ZIP o txt).
     * Es una copia de SUNAT, no datos del sistema: por eso va en bloque y no
     * pasa por la auditoria fila por fila; la descarga queda en sire_downloads.
     *
     * @param  string|string[]  $contenidos
     */
    public function importar(string $periodo, string|array $contenidos, ?SireDownload $descarga = null): int
    {
        $filas = self::leer($contenidos, $periodo);
        $ahora = now();

        DB::transaction(function () use ($periodo, $filas, $descarga, $ahora) {
            SirePurchase::where('period', $periodo)->delete();

            foreach (array_chunk($filas, 500) as $lote) {
                SirePurchase::insert(array_map(fn ($f) => $f + [
                    'period'           => $periodo,
                    'sire_download_id' => $descarga?->id,
                    'created_at'       => $ahora,
                    'updated_at'       => $ahora,
                ], $lote));
            }
        });

        return count($filas);
    }

    /**
     * Comprobantes de los archivos de la propuesta, listos para sire_purchases.
     * Acepta los ZIP tal como los entrega SUNAT (puede partir el resultado en
     * varios) o el txt ya extraido.
     *
     * @param  string|string[]  $contenidos
     */
    public static function leer(string|array $contenidos, ?string $periodo = null): array
    {
        $filas = [];

        foreach ((array) $contenidos as $contenido) {
            foreach (self::textosDelArchivo($contenido) as $texto) {
                foreach (SireRceFormato::leer($texto) as $fila) {
                    if ($periodo && $fila['periodo'] && $fila['periodo'] !== $periodo) {
                        throw new \UnexpectedValueException("El archivo es del periodo {$fila['periodo']} y se pidió {$periodo}.");
                    }

                    unset($fila['periodo']);

                    // Un comprobante figura una sola vez; si se repitiera, gana la ultima linea.
                    $filas[implode('|', [$fila['issuer_document_number'], $fila['document_type'], $fila['serie'], $fila['number']])] = $fila;
                }
            }
        }

        return array_values($filas);
    }

    /** @return string[] */
    private static function textosDelArchivo(string $contenido): array
    {
        // SUNAT manda el ZIP con la firma de archivo partido (PK\x07\x08) antes
        // de la normal (PK\x03\x04); ZipArchive lo abre igual.
        $esZip = preg_match('/^PK(\x03\x04|\x05\x06|\x07\x08)/', $contenido) === 1;
        $textos = $esZip ? self::extraerTxt($contenido) : [$contenido];

        return array_map(function (string $texto) {
            // Quita el BOM y pasa a UTF-8 si SUNAT lo mando en Latin-1.
            $texto = preg_replace('/^\xEF\xBB\xBF/', '', $texto);

            return mb_check_encoding($texto, 'UTF-8') ? $texto : mb_convert_encoding($texto, 'UTF-8', 'Windows-1252');
        }, $textos);
    }

    /** @return string[] */
    private static function extraerTxt(string $zipBinario): array
    {
        $ruta = tempnam(sys_get_temp_dir(), 'sire');

        try {
            file_put_contents($ruta, $zipBinario);

            $zip = new \ZipArchive();

            if ($zip->open($ruta) !== true) {
                throw new \UnexpectedValueException('El ZIP de SUNAT está dañado.');
            }

            $textos = [];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                if (preg_match('/\.(txt|csv)$/i', (string) $zip->getNameIndex($i))) {
                    $textos[] = (string) $zip->getFromIndex($i);
                }
            }

            $zip->close();

            if ($textos === []) {
                throw new \UnexpectedValueException('El ZIP de SUNAT no trae ningún txt.');
            }

            return $textos;
        } finally {
            @unlink($ruta);
        }
    }

    /** El registro del ticket en la consulta de estados, o null si SUNAT aun no lo lista. */
    private function estadoTicket(SireDownload $descarga): ?array
    {
        $respuesta = $this->get('rvierce/gestionprocesosmasivos/web/masivo/consultaestadotickets', [
            'perIni'         => $descarga->period,
            'perFin'         => $descarga->period,
            'page'           => 1,
            'perPage'        => 20,
            'numTicket'      => $descarga->ticket,
            'codLibro'       => self::LIBRO_COMPRAS,
            'codOrigenEnvio' => self::ORIGEN_API,
        ]);

        // Se busca por ticket: la consulta puede traer otros del mismo periodo.
        foreach ((array) $respuesta->json('registros', []) as $registro) {
            if ((string) ($registro['numTicket'] ?? '') === $descarga->ticket) {
                return $registro;
            }
        }

        return null;
    }

    private static function archivosDelProceso(array $proceso): array
    {
        return array_values(array_filter(
            (array) ($proceso['archivoReporte'] ?? []),
            fn ($archivo) => is_array($archivo) && ! empty($archivo['nomArchivoReporte'])
        ));
    }

    private function descargarArchivo(SireDownload $descarga, array $proceso, array $archivo): string
    {
        $respuesta = $this->get('rvierce/gestionprocesosmasivos/web/masivo/archivoreporte', [
            'nomArchivoReporte'     => $archivo['nomArchivoReporte'],
            // SUNAT lo escribe sin la "r" en la respuesta; el manual pide mandar "null" si no viene.
            'codTipoArchivoReporte' => $archivo['codTipoAchivoReporte'] ?? $archivo['codTipoArchivoReporte'] ?? 'null',
            'perTributario'         => $proceso['perTributario'] ?? $descarga->period,
            'codProceso'            => $proceso['codProceso'] ?? null,
            'numTicket'             => $descarga->ticket,
            'codLibro'              => self::LIBRO_COMPRAS,
        ]);

        // Un JSON en vez del ZIP es un error con status 200.
        if (str_contains((string) $respuesta->header('Content-Type'), 'json')) {
            throw new \RuntimeException(self::mensajeDeError($respuesta));
        }

        return $respuesta->body();
    }

    private function fallar(SireDownload $descarga, string $mensaje): SireDownload
    {
        $descarga->update([
            'status'      => SireDownload::STATUS_ERROR,
            'message'     => mb_substr($mensaje, 0, 500),
            'finished_at' => now(),
        ]);

        return $descarga;
    }

    private static function sinDocumentos(Response $respuesta): bool
    {
        if ($respuesta->status() !== 422) {
            return false;
        }

        $codigos = collect((array) $respuesta->json('errors', []))->pluck('cod')->push($respuesta->json('cod'))->map(fn ($c) => (string) $c);

        return $codigos->intersect(self::ERRORES_SIN_DOCUMENTOS)->isNotEmpty();
    }

    /**
     * GET autenticado contra api-sire. Reintenta los 500, los 429 y las caidas
     * de red, que en SIRE son habituales, y renueva el token una vez si SUNAT
     * lo rechaza.
     */
    private function get(string $ruta, array $query, bool $lanzar = true): Response
    {
        $respuesta = $this->enviar($ruta, $query, $this->token());

        if ($respuesta->status() === 401) {
            Cache::forget(self::CACHE_TOKEN);
            $respuesta = $this->enviar($ruta, $query, $this->token());
        }

        if ($respuesta->status() === 401) {
            Cache::forget(self::CACHE_TOKEN);

            throw new \RuntimeException(self::SIN_ACCESO, self::CODIGO_SIN_ACCESO);
        }

        if ($lanzar && $respuesta->failed()) {
            throw new \RuntimeException(self::mensajeDeError($respuesta));
        }

        return $respuesta;
    }

    private function enviar(string $ruta, array $query, string $token): Response
    {
        return Http::withToken($token)
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(60)
            ->retry(3, 2000, fn ($e) => self::reintentable($e), throw: false)
            ->get(self::URL_API . $ruta, array_filter($query, fn ($v) => $v !== null && $v !== ''));
    }

    private static function reintentable(\Throwable $e): bool
    {
        if ($e instanceof ConnectionException) {
            return true;
        }

        $status = $e instanceof RequestException ? $e->response->status() : 0;

        return $status === 429 || $status >= 500;
    }

    private function token(): string
    {
        $token = Cache::get(self::CACHE_TOKEN);

        if ($token) {
            return $token;
        }

        if (! self::configurado()) {
            throw new \RuntimeException('Faltan las credenciales de SIRE en la configuración del sistema.', self::CODIGO_SIN_ACCESO);
        }

        $respuesta = Http::asForm()
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->retry(2, 2000, fn ($e) => self::reintentable($e), throw: false)
            ->post(sprintf(self::URL_TOKEN, config('sire.client_id')), [
                'grant_type'    => 'password',
                'scope'         => 'https://api-sire.sunat.gob.pe',
                'client_id'     => config('sire.client_id'),
                'client_secret' => config('sire.client_secret'),
                'username'      => config('sire.ruc') . config('sire.usuario_sol'),
                'password'      => config('sire.clave_sol'),
            ]);

        $token = $respuesta->json('access_token');

        // SUNAT caido o saturado: se puede volver a intentar.
        if ($respuesta->status() === 429 || $respuesta->serverError()) {
            throw new \RuntimeException(self::mensajeDeError($respuesta));
        }

        // Credenciales rechazadas: no se reintenta, cada intento cuenta como
        // un ingreso fallido y SOL podria bloquear al usuario.
        if ($respuesta->failed() || ! $token) {
            // Ni el cuerpo ni la peticion van al log: podrian llevar las credenciales.
            throw new \RuntimeException('SUNAT rechazó las credenciales de SIRE (HTTP ' . $respuesta->status() . '): '
                . rtrim($respuesta->json('error_description') ?? $respuesta->json('msg') ?? 'sin detalle', '.') . '.', self::CODIGO_SIN_ACCESO);
        }

        // Se renueva un minuto antes de que venza.
        Cache::put(self::CACHE_TOKEN, $token, now()->addSeconds(max(60, (int) $respuesta->json('expires_in', 3600) - 60)));

        return $token;
    }

    private static function mensajeDeError(Response $respuesta): string
    {
        if ($respuesta->status() === 429) {
            return 'SUNAT está recibiendo demasiadas consultas (HTTP 429). Espere unos minutos y vuelva a intentar.';
        }

        // Las paginas de error de nginx traen el motivo en el <title>.
        $html = preg_match('#<title>(.*?)</title>#is', $respuesta->body(), $m) ? trim($m[1]) : null;

        $detalle = collect((array) $respuesta->json('errors', []))->pluck('msg')->filter()->implode(' ')
            ?: $respuesta->json('msg')
            ?? $respuesta->json('message')
            ?? $html
            ?? mb_substr(trim(strip_tags($respuesta->body())), 0, 200);

        return "SUNAT respondió HTTP {$respuesta->status()}" . ($detalle ? ": {$detalle}" : '.');
    }
}
