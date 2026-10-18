<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

/**
 * Auditoria interna: deja en audit_logs quien cambio que, cuando y desde
 * donde. Los modelos llegan por AuditObserver; las escrituras que no pasan por
 * un modelo (sync de permisos, inicios de sesion) llaman a registrar().
 *
 * Igual que ArticleObserver, la auditoria NUNCA debe romper la operacion que
 * audita: si falla (p.ej. codigo subido antes de correr la migracion), se
 * reporta al log del sistema y la operacion continua.
 */
class AuditLogger
{
    /** Se registra que cambiaron, nunca su valor. */
    private const OCULTOS = ['password'];

    /** Ruido: la auditoria tiene su propia fecha y remember_token rota en cada logout. */
    private const IGNORADOS = ['created_at', 'updated_at', 'remember_token'];

    /** Textos largos se recortan: la auditoria es para rastrear, no para respaldar. */
    private const MAX_TEXTO = 1000;

    /** Tras el primer fallo del request no se reintenta (evita llenar el log). */
    private static bool $desactivada = false;

    public static function modelo(string $evento, Model $model): void
    {
        $ocultos = array_merge(self::OCULTOS, $model->getHidden());
        $ignorar = array_merge(self::IGNORADOS, [$model->getKeyName()]);

        if ($evento === 'updated') {
            $campos = array_filter(
                array_diff(array_keys($model->getChanges()), $ignorar),
                fn ($campo) => ! self::mismoNumero($model->getRawOriginal($campo), $model->getAttributes()[$campo] ?? null)
            );

            if ($campos === []) {
                return;
            }

            $antes   = [];
            $despues = [];

            foreach ($campos as $campo) {
                $antes[$campo]   = self::valor($campo, $model->getRawOriginal($campo), $ocultos);
                $despues[$campo] = self::valor($campo, $model->getAttributes()[$campo] ?? null, $ocultos);
            }
        } else {
            $valores = [];

            foreach (array_diff_key($model->getAttributes(), array_flip($ignorar)) as $campo => $v) {
                $valores[$campo] = self::valor($campo, $v, $ocultos);
            }

            [$antes, $despues] = $evento === 'deleted' ? [$valores, null] : [null, $valores];
        }

        self::registrar($evento, $model->getTable(), $model->getKey(), $antes, $despues);
    }

    /** sync() de permisos no dispara eventos de modelo: se registra lo que quito y agrego. */
    public static function permisos(Model $user, array $cambios): void
    {
        if (empty($cambios['attached']) && empty($cambios['detached'])) {
            return;
        }

        $nombres = fn (array $ids) => Permission::whereIn('id', $ids)->orderBy('name')->pluck('name')->all();

        self::registrar(
            'permissions',
            $user->getTable(),
            $user->getKey(),
            $cambios['detached'] ? ['quitados' => $nombres($cambios['detached'])] : null,
            $cambios['attached'] ? ['agregados' => $nombres($cambios['attached'])] : null
        );
    }

    public static function registrar(
        string $evento,
        ?string $tabla = null,
        $registroId = null,
        ?array $antes = null,
        ?array $despues = null,
        ?int $userId = null
    ): void {
        if (self::$desactivada) {
            return;
        }

        try {
            $http = ! app()->runningInConsole();

            DB::table('audit_logs')->insert([
                'event'      => $evento,
                'table_name' => $tabla,
                'record_id'  => is_numeric($registroId) ? (int) $registroId : null,
                'old_values' => self::json($antes),
                'new_values' => self::json($despues),
                'user_id'    => $userId ?? Auth::id(),
                'context'    => self::contexto(),
                'via'        => mb_substr(preg_replace('/\s+/', ' ', $http
                    ? 'http:' . request()->path()
                    : 'console:' . (implode(' ', array_slice($_SERVER['argv'] ?? [], 1, 2)) ?: 'unknown')), 0, 100),
                'url'        => $http ? self::pantalla() : null,
                'ip'         => $http ? request()->ip() : null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            self::$desactivada = true;
            report($e);
        }
    }

    /**
     * 2 y "2.00" no es un cambio: pasa al editar precios guardados en columnas
     * double. Si ambos lados son texto no se compara como numero ("0123" y
     * "123" son documentos distintos).
     */
    private static function mismoNumero($antes, $despues): bool
    {
        return (! is_string($antes) || ! is_string($despues))
            && is_numeric($antes) && is_numeric($despues)
            && (float) $antes === (float) $despues;
    }

    private static function valor(string $campo, $valor, array $ocultos)
    {
        if (in_array($campo, $ocultos, true)) {
            return '[oculto]';
        }

        if (is_string($valor) && mb_strlen($valor) > self::MAX_TEXTO) {
            return mb_substr($valor, 0, self::MAX_TEXTO) . '… [' . mb_strlen($valor) . ' caracteres]';
        }

        return $valor;
    }

    private static function json(?array $valores): ?string
    {
        return $valores === null
            ? null
            : json_encode($valores, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /** En Livewire la ruta es siempre livewire/update: la pantalla real viene en el Referer. */
    private static function pantalla(): ?string
    {
        $url = request()->is('livewire/*') ? request()->header('referer') : request()->fullUrl();

        if (! $url) {
            return null;
        }

        $partes = parse_url($url);
        $ruta   = ($partes['path'] ?? '/') . (isset($partes['query']) ? '?' . $partes['query'] : '');

        return mb_substr($ruta, 0, 255);
    }

    /**
     * Primer frame dentro de app/ que no sea la propia auditoria ni un modelo:
     * asi un cambio hecho por un metodo del modelo apunta al componente,
     * comando o servicio que lo pidio. "Archivo.php:linea".
     */
    private static function contexto(): ?string
    {
        $omitir = [
            app_path('Services' . DIRECTORY_SEPARATOR . 'AuditLogger.php'),
            app_path('Observers'),
            app_path('Models'),
            app_path('Providers'),
        ];

        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 60) as $frame) {
            $file = $frame['file'] ?? '';

            if (! $file || ! str_starts_with($file, app_path())) {
                continue;
            }

            foreach ($omitir as $prefijo) {
                if (str_starts_with($file, $prefijo)) {
                    continue 2;
                }
            }

            return mb_substr(str_replace(app_path() . DIRECTORY_SEPARATOR, '', $file) . ':' . ($frame['line'] ?? '?'), 0, 190);
        }

        return null;
    }
}
