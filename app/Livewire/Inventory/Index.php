<?php

namespace App\Livewire\Inventory;

use App\Models\Article;
use App\Models\InventoryAdjustment;
use App\Models\InventoryCount;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Index extends Component
{
    /** Fecha que define “ventas del día” */
    public string $date = '';

    /** Filas con datos: almacén, kardex, diff, vendidos, físico guardado */
    public Collection $rows;

    /** Inputs físicos por artículo: [article_id => int|null] */
    public array $physicalStocks = [];

    /** Nota opcional para guardar conteos */
    public ?string $note = null;

    /** Mensaje simple para UI */
    public ?string $flash = null;

    public function mount(): void
    {
        $this->date = Carbon::today()->format('Y-m-d');
        $this->rows = collect();
        $this->loadRows();
    }

    public function updatedDate(): void
    {
        $this->loadRows();
    }

    /**
     * Carga artículos con ventas en $this->date y agrega:
     * - warehouse_stock (articles.stock)
     * - kardex_stock (VIEW v_kardex_stock)
     * - diff_kardex_vs_warehouse
     * - sold_today
     * - physical_saved (último conteo guardado del día)
     */
    public function loadRows(): void
    {
        // Subconsulta: vendidos hoy
        $soldToday = DB::table('sale_details as sd')
            ->join('sales as s', 's.id', '=', 'sd.sale_id')
            ->select('sd.article_id', DB::raw('SUM(sd.quantity) AS sold_today'))
            ->whereDate('s.date', $this->date)   // usa created_at si prefieres
            ->whereIn('s.status', [1, 2, 3])
            ->whereNull('sd.deleted_at')
            ->whereNull('s.deleted_at')
            ->groupBy('sd.article_id');

        // Subselect correlacionado: último conteo del día para cada artículo
        $physicalSavedSub = DB::table('inventory_counts as ic')
            ->select('ic.counted_stock')
            ->whereColumn('ic.article_id', 'a.id')
            ->whereDate('ic.counted_date', $this->date)
            ->orderByDesc('ic.id')
            ->limit(1);

        $rows = DB::table('articles as a')
            ->leftJoin('v_kardex_stock as k', 'k.article_id', '=', 'a.id')
            ->leftJoinSub($soldToday, 'st', fn($j) => $j->on('st.article_id', '=', 'a.id'))
            ->whereNull('a.deleted_at')
            ->whereNotNull('st.sold_today') // solo los que vendieron ese día
            ->select([
                'a.id as article_id',
                'a.sku',
                'a.title',
                'a.stock as warehouse_stock',
                DB::raw('COALESCE(k.kardex_stock, 0) AS kardex_stock'),
                DB::raw('(COALESCE(k.kardex_stock, 0) - a.stock) AS diff_kardex_vs_warehouse'),
                DB::raw('COALESCE(st.sold_today, 0) AS sold_today'),
            ])
            ->selectSub($physicalSavedSub, 'physical_saved')
            ->orderBy('a.title')
            ->get();

        $this->rows = collect($rows);

        // Sincroniza SIEMPRE los inputs con lo que hay en BD
        foreach ($this->rows as $r) {
            $this->physicalStocks[$r->article_id] = $r->physical_saved ?? null;
        }
    }

    /** Limpia input físico por fila */
    public function clearPhysical(int $articleId): void
    {
        $this->physicalStocks[$articleId] = null;
    }

    /**
     * Guarda conteos físicos del día (NO toca articles.stock) y recarga tabla/inputs.
     */
    public function saveCounts(): void
    {
        // Reglas por fila: cuando esté seteado, entero >= 0
        $rules = [];
        foreach ($this->rows as $r) {
            $rules["physicalStocks.{$r->article_id}"] = 'nullable|integer|min:0';
        }
        $this->validate($rules, [
            'integer' => 'Sólo números enteros.',
            'min'     => 'No puede ser negativo.',
        ]);

        $day = Carbon::parse($this->date)->toDateString();
        $uid = Auth::id();

        try {
            DB::transaction(function () use ($day, $uid) {
                foreach ($this->rows as $r) {
                    $physical = $this->physicalStocks[$r->article_id] ?? null;
                    if ($physical === '' || $physical === null) {
                        continue;
                    }

                    InventoryCount::create([
                        'article_id'    => $r->article_id,
                        'counted_stock' => (int) $physical,
                        'counted_date'  => $day,
                        'counted_by'    => $uid,   // nullable
                        'note'          => $this->note,
                    ]);
                }
            });

            $this->flash = 'Conteos físicos guardados.';
            // Relee BD y pre-carga inputs con el último conteo
            $this->loadRows();

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
     */
    public function updateWarehouseStock(int $articleId): void
    {
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
            // Relee para ver almacén actualizado y mantener input
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
