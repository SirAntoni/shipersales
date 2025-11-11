<?php

namespace App\Livewire\Inventory;

use App\Models\Article;
use App\Models\InventoryAdjustment;
use App\Models\InventoryCount;
use App\Models\InventorySession;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Index extends Component
{
    /** Fecha que define “ventas del día” (YYYY-mm-dd) */
    public string $date = '';

    /** Filas con datos: almacén, kardex, diff, vendidos, físico guardado */
    public Collection $rows;

    /** Inputs físicos por artículo: [article_id => int|null] */
    public array $physicalStocks = [];

    /** Nota opcional para guardar conteos */
    public ?string $note = null;

    /** Mensaje simple para UI */
    public ?string $flash = null;

    /** ====== Modo inventario / tiempo ====== */
    public bool $editing = false;        // habilita/deshabilita edición
    public ?string $startedAt = null;    // ISO 8601 para cronómetro UI
    public ?int $sessionId = null;       // inventory_sessions.id
    public int $totalRows = 0;           // total de filas a completar
    public int $completedRows = 0;       // filas con valor válido

    public string $q = '';

    public function mount(): void
    {
        $this->date = Carbon::today()->format('Y-m-d');
        $this->rows = collect();
        $this->loadRows();
    }

    public function updatedQ(): void
    {
        if ($this->editing) return;
        $this->loadRows();
    }

    public function updatedDate(): void
    {
        // si está en modo inventario, no permitir cambiar la fecha
        if ($this->editing) return;
        $this->loadRows();
    }

    public function updated($name): void
    {
        // Cuando cambia cualquier clave de physicalStocks.*, recalculamos progreso
        if (str_starts_with($name, 'physicalStocks.')) {
            $this->recountCompleted();
        }
    }

    /**
     * Carga artículos con ventas en $this->date y agrega:
     * - warehouse_stock (articles.stock)
     * - kardex_stock (VIEW v_kardex_stock)
     * - diff_kardex_vs_warehouse
     * - sold_today
     * - physical_saved (último conteo guardado del día)
     */
    // 3) En loadRows(), aplicar el filtro si viene texto
    public function loadRows(): void
    {
        $day = $this->date; // 'Y-m-d'
        $term = trim($this->q);

        $soldTodaySum = DB::table('sale_details as sd')
            ->join('sales as s', 's.id', '=', 'sd.sale_id')
            ->whereColumn('sd.article_id', 'a.id')
            ->whereDate(DB::raw('COALESCE(s.date, s.created_at)'), $day)
            ->where('s.status', '<>', 0)
            ->whereNull('sd.deleted_at')
            ->whereNull('s.deleted_at')
            ->selectRaw('COALESCE(SUM(sd.quantity), 0)');

        $rows = DB::table('articles as a')
            ->leftJoin('v_kardex_stock as k', 'k.article_id', '=', 'a.id')
            ->whereNull('a.deleted_at')
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($qq) use ($term) {
                    $qq->where('a.title', 'like', "%{$term}%")
                        ->orWhere('a.sku', 'like', "%{$term}%");
                });
            })
            ->whereExists(function ($q) use ($day) {
                $q->from('sale_details as sd')
                    ->join('sales as s', 's.id', '=', 'sd.sale_id')
                    ->whereColumn('sd.article_id', 'a.id')
                    ->whereDate(DB::raw('COALESCE(s.date, s.created_at)'), $day)
                    ->where('s.status', '<>', 0)
                    ->whereNull('sd.deleted_at')
                    ->whereNull('s.deleted_at');
            })
            ->select([
                'a.id as article_id',
                'a.sku',
                'a.title',
                'a.stock as warehouse_stock',
                DB::raw('COALESCE(k.kardex_stock, 0) AS kardex_stock'),
                DB::raw('(COALESCE(k.kardex_stock, 0) - a.stock) AS diff_kardex_vs_warehouse'),
            ])
            ->selectSub($soldTodaySum, 'sold_today')
            ->selectSub(
                DB::table('inventory_counts as ic')
                    ->select('ic.counted_stock')
                    ->whereColumn('ic.article_id', 'a.id')
                    ->whereDate('ic.counted_date', $day)
                    ->orderByDesc('ic.id')
                    ->limit(1),
                'physical_saved'
            )
            ->orderBy('a.title')
            ->get();

        $this->rows = collect($rows);

        $this->physicalStocks = [];
        foreach ($this->rows as $r) {
            $this->physicalStocks[$r->article_id] = $r->physical_saved ?? null;
        }

        $this->totalRows = $this->rows->count();
        $this->recountCompleted();
    }


    /** Recalcula cuántos inputs están llenos (para progreso) */
    public function recountCompleted(): void
    {
        $this->completedRows = 0;
        foreach ($this->rows as $r) {
            $v = $this->physicalStocks[$r->article_id] ?? null;
            if ($v !== null && $v !== '') $this->completedRows++;
        }
    }

    /** Hook: si el usuario va llenando, recalculamos progreso */
    public function updatedPhysicalStocks(): void
    {
        $this->recountCompleted();
    }

    /** Limpia input físico por fila */
    public function clearPhysical(int $articleId): void
    {
        if (!$this->editing) return;
        $this->physicalStocks[$articleId] = null;
        $this->recountCompleted();
    }

    /** Arranca el modo inventario: habilita inputs, marca inicio y crea sesión */
    public function startInventory(): void
    {
        if ($this->editing) return;

        // refresca filas (aseguramos totalRows correcto)
        $this->loadRows();

        $this->editing   = true;
        $this->startedAt = now()->toISOString();

        $session = InventorySession::create([
            'count_date'     => $this->date,
            'user_id'        => Auth::id(),
            'started_at'     => now(),
            'total_rows'     => $this->totalRows,
            'completed_rows' => 0,
            'note'           => $this->note,
        ]);

        $this->sessionId = $session->id;
        $this->dispatch('inventory-started', startedAt: $this->startedAt);
    }

    /** Cancela el modo inventario (no persiste duración) */
    public function cancelInventory(): void
    {
        if (!$this->editing) return;

        $this->editing   = false;
        $this->startedAt = null;
        $this->sessionId = null;
        $this->flash     = 'Inventario cancelado.';
        $this->dispatch('inventory-stopped');
    }

    /**
     * Guarda conteos físicos del día (NO toca articles.stock) y persiste duración.
     * Bloquea si no están TODAS las filas completas.
     */
    public function saveCounts(): void
    {
        if (!$this->editing || !$this->sessionId) {
            $this->flash = 'Inicia el inventario antes de guardar.';
            return;
        }

        // Exigir TODOS los campos: required|integer|min:0
        $rules = [];
        foreach ($this->rows as $r) {
            $rules["physicalStocks.{$r->article_id}"] = 'required|integer|min:0';
        }
        $this->validate($rules, [
            'required' => 'Completa todos los productos antes de guardar.',
            'integer'  => 'Sólo números enteros.',
            'min'      => 'No puede ser negativo.',
        ]);

        $day = Carbon::parse($this->date)->toDateString();
        $uid = Auth::id();

        try {
            DB::transaction(function () use ($day, $uid) {
                foreach ($this->rows as $r) {
                    $physical = (int) $this->physicalStocks[$r->article_id];
                    InventoryCount::create([
                        'article_id'    => $r->article_id,
                        'counted_stock' => $physical,
                        'counted_date'  => $day,
                        'counted_by'    => $uid,
                        'note'          => $this->note,
                    ]);
                }

                // Duración
                $duration = null;
                if ($this->startedAt) {
                    $duration = Carbon::parse($this->startedAt)->diffInSeconds(now());
                }

                // Actualiza la sesión
                InventorySession::where('id', $this->sessionId)->update([
                    'finished_at'    => now(),
                    'duration_sec'   => $duration,
                    'completed_rows' => $this->totalRows,
                    'note'           => $this->note,
                ]);
            });

            $this->flash = 'Conteos físicos guardados.';
            // Salimos de modo inventario y refrescamos
            $this->editing   = false;
            $this->startedAt = null;
            $this->sessionId = null;

            $this->loadRows();
            $this->dispatch('inventory-stopped');

        } catch (\Throwable $e) {
            \Log::error('Inventory saveCounts error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            $this->flash = 'Error al guardar conteos: ' . $e->getMessage();
        }
    }

    /**
     * Actualiza almacén (articles.stock) para UNA fila y audita.
     * Habilitado sólo si $editing y el input está lleno.
     */
    public function updateWarehouseStock(int $articleId): void
    {
        if (!$this->editing) return;

        $this->validate([
            "physicalStocks.$articleId" => 'required|integer|min:0',
        ], [
            'required' => 'Ingresa un valor.',
            'integer'  => 'Sólo enteros.',
            'min'      => 'No puede ser negativo.',
        ]);

        $physical = (int) $this->physicalStocks[$articleId];

        try {
            DB::transaction(function () use ($articleId, $physical) {
                /** @var Article $article */
                $article = Article::lockForUpdate()->findOrFail($articleId);
                $old     = (int) $article->stock;

                // Update almacén
                $article->update(['stock' => $physical]);

                // Auditoría
                InventoryAdjustment::create([
                    'article_id' => $articleId,
                    'old_stock'  => $old,
                    'new_stock'  => $physical,
                    'delta'      => $physical - $old,
                    'reason'     => 'Conteo físico',
                    'source'     => 'inventory_module',
                    'created_by' => Auth::id(),
                ]);
            });

            $this->flash = 'Stock de almacén actualizado.';
            $this->loadRows();

        } catch (\Throwable $e) {
            \Log::error('Inventory updateWarehouseStock error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            $this->flash = 'Error al actualizar almacén: ' . $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.inventory.index');
    }
}
