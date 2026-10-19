<?php

namespace App\Livewire\Purchases;

use App\Livewire\Documents\TableDocuments;
use App\Models\SireDownload;
use App\Models\SirePurchase;
use App\Services\SireService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Compras que SUNAT tiene registradas para el RUC (propuesta del RCE de SIRE).
 * Solo se ven y se exportan: no se cruzan con el modulo de compras, que
 * registra sobre todo pedidos de Amazon/USA que no pasan por SUNAT.
 */
class TableSirePurchases extends Component
{
    use WithPagination;

    public const POR_PAGINA_DEFECTO = 40;

    public const OPCIONES_POR_PAGINA = [15, 30, 40, 60, 100, 200];

    /** Periodos que ofrece el selector, ademas de los ya descargados. */
    private const MESES_EN_SELECTOR = 18;

    #[Url(as: 'periodo')]
    public $periodo = '';

    public $search = '';

    public $tipo = '';

    /** RUC exacto: lo pone el clic en una fila de "Por proveedor" ("0" = aduanas). */
    #[Url(as: 'proveedor', except: '')]
    public $proveedor = '';

    /**
     * "mes" (emitidos en el periodo) o "anteriores". SIRE propone en el periodo
     * abierto los comprobantes que aun no se anotaron y siguen dentro de los 12
     * meses en que se pueden anotar, asi que un periodo trae fechas de antes.
     */
    #[Url(as: 'emision', except: '')]
    public $emision = '';

    /** "comprobantes" o "proveedores" (resumen por RUC). */
    #[Url(as: 'vista', except: 'comprobantes')]
    public $vista = 'comprobantes';

    #[Url(as: 'porPagina', except: self::POR_PAGINA_DEFECTO)]
    public $perPage = self::POR_PAGINA_DEFECTO;

    /** Comprobante abierto en el modal de detalle. */
    public $detalleId = null;

    public function mount()
    {
        if (! self::periodoValido($this->periodo)) {
            $this->periodo = SireService::ultimoPeriodoDisponible();
        }
    }

    public function updatingPeriodo()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingTipo()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function updatingVista()
    {
        $this->resetPage();
    }

    public function updatingEmision()
    {
        $this->resetPage();
    }

    public function porProveedores(): bool
    {
        return $this->vista === 'proveedores';
    }

    public function porPagina(): int
    {
        return in_array((int) $this->perPage, self::OPCIONES_POR_PAGINA, true)
            ? (int) $this->perPage
            : self::POR_PAGINA_DEFECTO;
    }

    public function rendered()
    {
        $this->dispatch('reinit-tippy');
    }

    public static function periodoValido($periodo): bool
    {
        return is_string($periodo)
            && preg_match('/^\d{4}(0[1-9]|1[0-2])$/', $periodo) === 1;
    }

    /** "202609" -> "Setiembre 2026". */
    public static function etiquetaPeriodo(string $periodo): string
    {
        return TableDocuments::etiquetaPeriodo(substr($periodo, 0, 4), substr($periodo, 4, 2));
    }

    /** "202609" -> "2026-09-01": lo emitido antes es de meses anteriores. Null si el periodo no es valido. */
    public static function inicioPeriodo($periodo): ?string
    {
        return self::periodoValido($periodo) ? substr($periodo, 0, 4) . '-' . substr($periodo, 4, 2) . '-01' : null;
    }

    public static function filtrosPorDefecto(array $filtros = []): array
    {
        return array_merge([
            'periodo'   => null,
            'search'    => null,
            'tipo'      => null,
            'proveedor' => null,
            'emision'   => null,
        ], $filtros);
    }

    /** Filtros compartidos por la tabla, el pie de totales y el Excel. */
    public static function filtrar($query, array $filtros)
    {
        $f = self::filtrosPorDefecto($filtros);
        $inicio = self::inicioPeriodo($f['periodo']);

        return $query
            ->where('period', $f['periodo'])
            ->when($inicio && $f['emision'] === 'mes', fn ($q) => $q->where('issue_date', '>=', $inicio))
            ->when($inicio && $f['emision'] === 'anteriores', fn ($q) => $q->where('issue_date', '<', $inicio))
            ->when($f['tipo'], fn ($q) => $q->where('document_type', $f['tipo']))
            // Comparacion explicita: el RUC "0" de aduanas es falsy en PHP.
            ->when((string) $f['proveedor'] !== '', fn ($q) => $q->where('issuer_document_number', (string) $f['proveedor']))
            ->when($f['search'], function ($q) use ($f) {
                $search = trim($f['search']);
                $q->where(function ($q) use ($search) {
                    $q->where('issuer_document_number', 'LIKE', "%{$search}%")
                      ->orWhere('issuer_name', 'LIKE', "%{$search}%")
                      ->orWhereRaw("CONCAT(serie, '-', number) LIKE ?", ["%{$search}%"]);
                });
            });
    }

    /**
     * Totales de todo el filtro, en soles: SUNAT ya convierte a soles los
     * comprobantes en otra moneda (con el tipo de cambio que muestra), asi que
     * se suman todos. Las notas de credito vienen en negativo y restan solas.
     */
    public static function resumen(array $filtros): array
    {
        $inicio = self::inicioPeriodo(self::filtrosPorDefecto($filtros)['periodo']) ?? '0000-01-01';

        $r = self::filtrar(SirePurchase::query(), $filtros)
            ->selectRaw("COUNT(*) as cantidad, COUNT(DISTINCT issuer_document_number) as proveedores, SUM(currency <> 'PEN') as en_otra_moneda, SUM(taxable_base) as base, SUM(igv) as igv, SUM(non_taxable) as no_gravado, SUM(other_taxes) as otros_tributos, SUM(total) as total, SUM(issue_date < ?) as anteriores, SUM(CASE WHEN issue_date < ? THEN total ELSE 0 END) as total_anteriores", [$inicio, $inicio])
            ->first();

        return [
            'cantidad'         => (int) $r->cantidad,
            'proveedores'      => (int) $r->proveedores,
            'en_otra_moneda'   => (int) $r->en_otra_moneda,
            'base'             => round((float) $r->base, 2),
            'igv'              => round((float) $r->igv, 2),
            'no_gravado'       => round((float) $r->no_gravado, 2),
            'otros_tributos'   => round((float) $r->otros_tributos, 2),
            // En pantalla van en una sola columna; el Excel los separa.
            'otros'            => round((float) $r->no_gravado + (float) $r->otros_tributos, 2),
            'total'            => round((float) $r->total, 2),
            // Emitidos antes del periodo, que SIRE igual propone en el.
            'anteriores'       => (int) $r->anteriores,
            'total_anteriores' => round((float) $r->total_anteriores, 2),
            'total_mes'        => round((float) $r->total - (float) $r->total_anteriores, 2),
        ];
    }

    /**
     * Totales por RUC del emisor, del que mas compro al que menos. Todas las
     * declaraciones de aduanas comparten el RUC "0" y quedan en una sola fila;
     * MAX(document_type) solo sirve para que proveedor() las llame "Aduanas".
     */
    public static function porProveedor(array $filtros)
    {
        return self::filtrar(SirePurchase::query(), $filtros)
            ->selectRaw("issuer_document_number, MAX(issuer_name) as issuer_name, MAX(document_type) as document_type, COUNT(*) as cantidad, SUM(document_type = '07') as notas_credito, SUM(taxable_base) as taxable_base, SUM(igv) as igv, SUM(non_taxable + other_taxes) as otros, SUM(total) as total")
            ->groupBy('issuer_document_number')
            ->orderByDesc('total')
            ->orderBy('issuer_document_number');
    }

    /**
     * Los ultimos meses disponibles en SUNAT mas los que ya se descargaron,
     * del mas reciente al mas antiguo.
     */
    public function getPeriodosProperty(): array
    {
        $ultimo = Carbon::createFromFormat('Ym d', SireService::ultimoPeriodoDisponible() . ' 01');

        $periodos = collect(range(0, self::MESES_EN_SELECTOR - 1))
            ->map(fn ($i) => $ultimo->copy()->subMonthsNoOverflow($i)->format('Ym'))
            ->merge(SirePurchase::distinct()->pluck('period'))
            ->push($this->periodo)
            ->filter(fn ($p) => self::periodoValido($p))
            ->unique()
            ->sortDesc()
            ->values();

        return $periodos->mapWithKeys(fn ($p) => [$p => self::etiquetaPeriodo($p)])->all();
    }

    /** Los tipos que trae el periodo (mas el elegido, aunque ya no tenga filas). */
    private function tiposDelPeriodo(): array
    {
        return SirePurchase::where('period', $this->periodo)->distinct()->pluck('document_type')
            ->push((string) $this->tipo)
            ->filter(fn ($t) => preg_match('/^\d{2}$/', $t) === 1)
            ->unique()
            ->sort()
            ->mapWithKeys(fn ($t) => [$t => SirePurchase::nombreTipo($t)])
            ->all();
    }

    private function filtrosActivos(): array
    {
        return [
            'periodo'   => $this->periodo,
            'search'    => $this->search,
            'tipo'      => $this->tipo,
            'proveedor' => $this->proveedor,
            'emision'   => $this->emision,
        ];
    }

    public function verDetalle($id)
    {
        $this->detalleId = (int) $id;
        $this->dispatch('open-sire-detalle-modal');
    }

    /** Desde "Por proveedor" o el detalle: sus comprobantes, con los demas filtros. */
    public function verProveedor($ruc)
    {
        $this->proveedor = (string) $ruc;
        $this->vista = 'comprobantes';
        $this->resetPage();
        $this->dispatch('close-sire-detalle-modal');
    }

    public function quitarProveedor()
    {
        $this->proveedor = '';
        $this->resetPage();
    }

    /** Nombre del proveedor filtrado, para la etiqueta que permite quitar el filtro. */
    private function nombreProveedorFiltrado(): ?string
    {
        return (string) $this->proveedor === '' ? null : SirePurchase::nombreDeProveedor((string) $this->proveedor);
    }

    /** Pide el periodo a SUNAT. La respuesta llega por ticket: la consulta revisarDescarga(). */
    public function descargar(SireService $sire)
    {
        if (! self::periodoValido($this->periodo)) {
            return;
        }

        if (! SireService::configurado()) {
            $this->dispatch('error', ['label' => 'Faltan las credenciales de SIRE en la configuración del sistema (SIRE_CLIENT_ID, SIRE_CLIENT_SECRET, SIRE_USUARIO_SOL y SIRE_CLAVE_SOL).']);

            return;
        }

        if ($this->periodo > SireService::ultimoPeriodoDisponible()) {
            $this->dispatch('error', ['label' => 'SUNAT publica las compras de un mes recién a partir del día 2 del mes siguiente.']);

            return;
        }

        if ($this->descargaPendiente()) {
            return;
        }

        try {
            $descarga = $sire->solicitar($this->periodo, auth()->id());

            // Sin comprobantes SUNAT responde al instante, sin ticket.
            if ($descarga->status === SireDownload::STATUS_DONE) {
                $this->resetPage();
                $this->dispatch('successNotRoute', ['label' => 'SUNAT no tiene compras registradas para ' . self::etiquetaPeriodo($this->periodo) . '.']);
            }
        } catch (\Throwable $e) {
            Log::error('SIRE: no se pudo solicitar el periodo', ['periodo' => $this->periodo, 'error' => $e->getMessage()]);

            $this->dispatch('error', ['label' => 'SUNAT no respondió al pedir el periodo: ' . $e->getMessage()]);
        }
    }

    /** Lo llama el wire:poll mientras el periodo tenga un ticket sin terminar. */
    public function revisarDescarga(SireService $sire)
    {
        $descarga = $this->descargaPendiente();

        if (! $descarga) {
            return;
        }

        $descarga = $sire->avanzar($descarga);

        if ($descarga->status === SireDownload::STATUS_DONE) {
            $this->resetPage();
            $this->dispatch('successNotRoute', ['label' => "Se descargaron {$descarga->total_rows} comprobante(s) de " . self::etiquetaPeriodo($descarga->period) . '.']);
        } elseif ($descarga->status === SireDownload::STATUS_ERROR) {
            $this->dispatch('error', ['label' => $descarga->message]);
        }
    }

    private function descargaPendiente(): ?SireDownload
    {
        return SireDownload::pendientes()->where('period', $this->periodo)->latest('id')->first();
    }

    public function exportar()
    {
        $url = route('sire_purchases.export', array_filter(
            $this->filtrosActivos(),
            fn ($v) => $v !== null && $v !== ''
        ));

        $this->dispatch('abrir-nueva-pestania', ['url' => $url]);
    }

    public function render()
    {
        $filtros = $this->filtrosActivos();

        return view('livewire.purchases.table-sire-purchases', [
            'compras'           => $this->porProveedores()
                ? self::porProveedor($filtros)->paginate($this->porPagina())
                : self::filtrar(SirePurchase::query(), $filtros)
                    ->orderBy('issue_date')->orderBy('id')
                    ->paginate($this->porPagina()),
            'resumen'           => self::resumen($filtros),
            // Del periodo entero, sin los demas filtros: decide si se muestra el filtro de emision.
            'delPeriodo'        => self::resumen(['periodo' => $this->periodo]),
            'porProveedores'    => $this->porProveedores(),
            'detalle'           => $this->detalleId ? SirePurchase::with('download.user:id,name')->find($this->detalleId) : null,
            'nombreProveedor'   => $this->nombreProveedorFiltrado(),
            'periodos'          => $this->periodos,
            'tipos'             => $this->tiposDelPeriodo(),
            'pendiente'         => $this->descargaPendiente(),
            'ultima'            => SireDownload::with('user:id,name')->where('period', $this->periodo)->latest('id')->first(),
            'configurado'       => SireService::configurado(),
            'opcionesPorPagina' => self::OPCIONES_POR_PAGINA,
        ]);
    }
}
