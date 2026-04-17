<?php

namespace App\Livewire\Purchases;

use App\Models\Article;
use App\Models\Provider;
use App\Models\Purchase;
use App\Models\UsaPurchase;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class TableUsaPurchases extends Component
{
    use WithPagination;

    public $search = '';
    public $filterType = '';
    public $filterYear = '';
    public $filterStatus = '';
    public $filterStore = '';

    // Form fields (header)
    public $editingId = null;
    public $editDate = '';
    public $editCarrier = '';
    public $editStore = '';
    public $editOrderNumber = '';
    public $editTracking = '';
    public $editStatus = '';
    public $editArrivalDate = '';
    public $editComments = '';
    public $editType = '';

    // Article selection
    public $articleSelected = null;
    public $articlesSelected = [];

    // Edit mode single article
    public $editArticleId = null;
    public $editQuantity = 1;

    // Import to stock
    public $importingId = null;
    public $importArticleTitle = '';
    public $importOriginalQuantity = 0;
    public $importQuantity = 1;
    public $importPrice = 0;
    public $importProviderId = null;
    public $providers = [];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterType() { $this->resetPage(); }
    public function updatingFilterYear() { $this->resetPage(); }
    public function updatingFilterStatus() { $this->resetPage(); }
    public function updatingFilterStore() { $this->resetPage(); }

    public function newRecord()
    {
        $this->resetFormFields();
        $this->editStatus = 'EMBARCADO';
        $this->editType = 'CARGO';
        $this->editDate = now()->format('Y-m-d');
        $this->dispatch('open-edit-usa-modal');
    }

    public function editRecord($id)
    {
        $record = UsaPurchase::findOrFail($id);
        $this->editingId = $record->id;
        $this->editDate = $record->date?->format('Y-m-d') ?? '';
        $this->editCarrier = $record->carrier ?? '';
        $this->editStore = $record->store ?? '';
        $this->editOrderNumber = $record->order_number ?? '';
        $this->editTracking = $record->tracking ?? '';
        $this->editStatus = $record->status ?? '';
        $this->editArrivalDate = $record->arrival_date?->format('Y-m-d') ?? '';
        $this->editComments = $record->comments ?? '';
        $this->editType = $record->type ?? '';
        $this->editArticleId = $record->article_id;
        $this->editQuantity = $record->quantity ?: 1;
        $this->articlesSelected = [];

        if ($record->article_id) {
            $article = Article::find($record->article_id);
            if ($article) {
                $this->articlesSelected = [[
                    'id' => $article->id,
                    'sku' => $article->sku,
                    'title' => $article->title,
                    'quantity' => (int) $record->quantity ?: 1,
                ]];
            }
        }

        $this->dispatch('open-edit-usa-modal');
    }

    public function searchArticles($query)
    {
        return Article::where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', '%' . $query . '%')
                    ->orWhere('sku', 'like', '%' . $query . '%')
                    ->orWhereHas('brand', fn($b) => $b->where('name', 'like', '%' . $query . '%'));
            })
            ->limit(10)
            ->get()
            ->map(fn($a) => [
                'value' => $a->id,
                'text' => trim(
                    ($a->sku ? "[{$a->sku}] " : '') .
                    $a->title .
                    ($a->brand?->name ? " ({$a->brand->name})" : '')
                ),
            ])
            ->toArray();
    }

    public function updatedArticleSelected($id)
    {
        if ($id) {
            $this->addArticle($id);
            $this->articleSelected = null;
        }
    }

    public function addArticle($id)
    {
        $article = Article::find($id);
        if (!$article) return;

        // Edit mode → replace single article
        if ($this->editingId) {
            $this->articlesSelected = [[
                'id' => $article->id,
                'sku' => $article->sku,
                'title' => $article->title,
                'quantity' => $this->editQuantity ?: 1,
            ]];
            $this->editArticleId = $article->id;
            return;
        }

        // New mode → append if not already selected
        $existsIndex = collect($this->articlesSelected)->search(fn($item) => $item['id'] == $article->id);
        if ($existsIndex !== false) {
            $this->articlesSelected[$existsIndex]['quantity']++;
            return;
        }

        $this->articlesSelected[] = [
            'id' => $article->id,
            'sku' => $article->sku,
            'title' => $article->title,
            'quantity' => 1,
        ];
    }

    public function removeArticle($index)
    {
        unset($this->articlesSelected[$index]);
        $this->articlesSelected = array_values($this->articlesSelected);
    }

    public function incrementQty($index)
    {
        $this->articlesSelected[$index]['quantity']++;
    }

    public function decrementQty($index)
    {
        if ($this->articlesSelected[$index]['quantity'] > 1) {
            $this->articlesSelected[$index]['quantity']--;
        }
    }

    public function saveRecord()
    {
        $this->validate([
            'editCarrier' => 'required',
            'editStatus' => 'required',
            'editType' => 'required',
            'articlesSelected' => 'required|array|min:1',
        ], [
            'articlesSelected.required' => 'Debe seleccionar al menos 1 artículo',
            'articlesSelected.min' => 'Debe seleccionar al menos 1 artículo',
        ], [
            'editCarrier' => 'pasajero/consignatario',
            'editStatus' => 'estado',
            'editType' => 'tipo',
        ]);

        $header = [
            'date' => $this->editDate ?: null,
            'carrier' => $this->editCarrier,
            'store' => $this->editStore,
            'order_number' => $this->editOrderNumber,
            'tracking' => $this->editTracking,
            'status' => $this->editStatus,
            'arrival_date' => $this->editArrivalDate ?: null,
            'comments' => $this->editComments ?: null,
            'type' => $this->editType,
            'year' => $this->editDate ? date('Y', strtotime($this->editDate)) : now()->year,
        ];

        if ($this->editingId) {
            $article = $this->articlesSelected[0] ?? null;
            UsaPurchase::findOrFail($this->editingId)->update(array_merge($header, [
                'description' => $article['title'] ?? '',
                'sku' => $article['sku'] ?? null,
                'article_id' => $article['id'] ?? null,
                'quantity' => (int) ($article['quantity'] ?? 1),
            ]));
            $msg = 'Registro actualizado.';
        } else {
            foreach ($this->articlesSelected as $article) {
                UsaPurchase::create(array_merge($header, [
                    'description' => $article['title'],
                    'sku' => $article['sku'],
                    'article_id' => $article['id'],
                    'quantity' => (int) $article['quantity'],
                ]));
            }
            $msg = count($this->articlesSelected) > 1
                ? count($this->articlesSelected) . ' registros creados.'
                : 'Registro creado.';
        }

        $this->dispatch('close-edit-usa-modal');
        $this->dispatch('successNotRoute', ['label' => $msg]);
        $this->resetFormFields();
    }

    public function deleteRecord($id)
    {
        $this->dispatch('questionDeleteUsa', [
            'label' => '¿Está seguro que desea eliminar este registro?',
            'id' => $id
        ]);
    }

    #[On('confirmDeleteUsa')]
    public function confirmDeleteUsa($id)
    {
        UsaPurchase::findOrFail($id)->delete();
        $this->dispatch('successNotRoute', ['label' => 'Registro eliminado.']);
    }

    public function openImport($id)
    {
        $record = UsaPurchase::with('article')->findOrFail($id);

        if (!in_array($record->status, ['ENTREGADO', 'PARCIAL'])) {
            $this->dispatch('errorNotRoute', ['label' => 'Solo se pueden importar registros ENTREGADO o PARCIAL.']);
            return;
        }

        if ($record->processed) {
            $this->dispatch('errorNotRoute', ['label' => 'Este registro ya fue procesado.']);
            return;
        }

        if (!$record->article_id) {
            $this->dispatch('errorNotRoute', ['label' => 'Este registro no tiene artículo vinculado.']);
            return;
        }

        $this->importingId = $record->id;
        $this->importArticleTitle = ($record->article?->sku ? "[{$record->article->sku}] " : '') . $record->description;
        $this->importOriginalQuantity = (int) $record->quantity;
        $this->importQuantity = (int) $record->quantity;
        $this->importPrice = (float) ($record->article?->purchase_price ?? 0);
        $this->importProviderId = null;
        $this->providers = Provider::select('id', 'name')->orderBy('name')->get()->toArray();

        $this->dispatch('open-import-stock-modal');
    }

    public function confirmImport()
    {
        $this->validate([
            'importQuantity' => 'required|integer|min:1',
            'importPrice' => 'required|numeric|min:0',
            'importProviderId' => 'required',
        ], [], [
            'importQuantity' => 'cantidad recibida',
            'importPrice' => 'precio',
            'importProviderId' => 'proveedor',
        ]);

        $record = UsaPurchase::findOrFail($this->importingId);

        if ($record->processed) {
            $this->dispatch('errorNotRoute', ['label' => 'Este registro ya fue procesado.']);
            return;
        }

        DB::transaction(function () use ($record) {
            $article = Article::findOrFail($record->article_id);

            $price = (float) $this->importPrice;
            $qty = (int) $this->importQuantity;
            $subtotal = $price * $qty;

            $purchase = Purchase::create([
                'provider_id' => $this->importProviderId,
                'user_id' => auth()->id(),
                'voucher_type' => 'Boleta',
                'document' => 'USA-' . $record->id,
                'passenger' => $record->carrier ?? 'Compra USA',
                'subtotal' => $subtotal,
                'tax' => 0,
                'total' => $subtotal,
                'status' => Purchase::PURCHASE_FINISHED,
            ]);

            $purchase->purchaseDetails()->create([
                'article_id' => $article->id,
                'category_id' => $article->category_id,
                'brand_id' => $article->brand_id,
                'price' => $price,
                'quantity' => $qty,
                'subtotal' => $subtotal,
                'tax' => 0,
                'total' => $subtotal,
            ]);

            $article->increment('stock', $qty);
            $article->update(['provider_id' => $this->importProviderId]);

            $record->update([
                'processed' => true,
                'purchase_id' => $purchase->id,
            ]);
        });

        $this->dispatch('close-import-stock-modal');
        $this->dispatch('successNotRoute', ['label' => 'Compra importada al stock correctamente.']);
        $this->resetImportFields();
    }

    public function clearSearch()
    {
        $this->reset('search');
    }

    public function clearFilters()
    {
        $this->reset('filterType', 'filterYear', 'filterStatus', 'filterStore');
        $this->resetPage();
    }

    private function resetFormFields()
    {
        $this->reset([
            'editingId', 'editDate', 'editCarrier', 'editStore',
            'editOrderNumber', 'editTracking', 'editStatus',
            'editArrivalDate', 'editComments', 'editType',
            'articleSelected', 'articlesSelected', 'editArticleId', 'editQuantity',
        ]);
    }

    private function resetImportFields()
    {
        $this->reset([
            'importingId', 'importArticleTitle', 'importOriginalQuantity',
            'importQuantity', 'importPrice', 'importProviderId',
        ]);
    }

    public function render()
    {
        $records = UsaPurchase::query()
            ->with('article:id,sku,title')
            ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
            ->when($this->filterYear, fn($q) => $q->where('year', $this->filterYear))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterStore, fn($q) => $q->where('store', $this->filterStore))
            ->when($this->search, function ($q, $search) {
                $terms = collect(preg_split('/[\s\+]+/', trim($search)))->filter()->values();
                foreach ($terms as $term) {
                    $like = '%' . $term . '%';
                    $q->where(function ($sub) use ($like) {
                        $sub->where('carrier', 'like', $like)
                            ->orWhere('description', 'like', $like)
                            ->orWhere('sku', 'like', $like)
                            ->orWhere('order_number', 'like', $like)
                            ->orWhere('tracking', 'like', $like)
                            ->orWhere('store', 'like', $like);
                    });
                }
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20);

        $years = UsaPurchase::select('year')->distinct()->orderByDesc('year')->pluck('year');
        $stores = UsaPurchase::select('store')->distinct()->whereNotNull('store')->where('store', '!=', '')->orderBy('store')->pluck('store');

        $summaryQuery = UsaPurchase::query()
            ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
            ->when($this->filterYear, fn($q) => $q->where('year', $this->filterYear))
            ->when($this->filterStore, fn($q) => $q->where('store', $this->filterStore));

        $totalRecords = (clone $summaryQuery)->count();
        $totalDelivered = (clone $summaryQuery)->where('status', 'ENTREGADO')->count();
        $totalShipped = (clone $summaryQuery)->where('status', 'EMBARCADO')->count();
        $totalPending = (clone $summaryQuery)->whereIn('status', ['PARCIAL', 'NO LLEGO'])->count();
        $totalProcessed = (clone $summaryQuery)->where('processed', true)->count();

        return view('livewire.purchases.table-usa-purchases', compact(
            'records', 'years', 'stores',
            'totalRecords', 'totalDelivered', 'totalShipped', 'totalPending', 'totalProcessed'
        ));
    }
}
