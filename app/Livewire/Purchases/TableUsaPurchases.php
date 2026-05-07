<?php

namespace App\Livewire\Purchases;

use App\Models\Article;
use App\Models\Provider;
use App\Models\Purchase;
use App\Models\UsaPurchase;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TableUsaPurchases extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public $search = '';
    #[Url(as: 'type', except: '')]
    public $filterType = '';
    #[Url(as: 'year', except: '')]
    public $filterYear = '';
    #[Url(as: 'status', except: '')]
    public $filterStatus = '';
    #[Url(as: 'store', except: '')]
    public $filterStore = '';
    #[Url(as: 'd', except: '')]
    public $dateExact = '';
    #[Url(as: 'ad', except: '')]
    public $arrivalDateExact = '';
    #[Url(as: 'df', except: '')]
    public $dateFrom = '';
    #[Url(as: 'dt', except: '')]
    public $dateTo = '';
    #[Url(as: 'adf', except: '')]
    public $arrivalDateFrom = '';
    #[Url(as: 'adt', except: '')]
    public $arrivalDateTo = '';

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

    // Bulk selection
    public $selectedIds = [];
    public $selectAll = false;
    public $bulkStatus = '';

    // Bulk import to stock
    public $bulkGroups = [];
    public $bulkConfirmExisting = [];
    public $bulkDiscardedCount = 0;
    public $bulkProviders = [];

    public function rendered() { $this->dispatch('reinit-tippy'); }

    public function updatingSearch() { $this->resetPage(); $this->clearSelection(); }
    public function updatingFilterType() { $this->resetPage(); $this->clearSelection(); }
    public function updatingFilterYear() { $this->resetPage(); $this->clearSelection(); }
    public function updatingFilterStatus() { $this->resetPage(); $this->clearSelection(); }
    public function updatingFilterStore() { $this->resetPage(); $this->clearSelection(); }
    public function updatingDateExact() { $this->resetPage(); $this->clearSelection(); }
    public function updatingArrivalDateExact() { $this->resetPage(); $this->clearSelection(); }
    public function updatingDateFrom() { $this->resetPage(); $this->clearSelection(); }
    public function updatingDateTo() { $this->resetPage(); $this->clearSelection(); }
    public function updatingArrivalDateFrom() { $this->resetPage(); $this->clearSelection(); }
    public function updatingArrivalDateTo() { $this->resetPage(); $this->clearSelection(); }

    public function updatedDateTo()        { $this->autoSwapDateRange('dateFrom', 'dateTo'); }
    public function updatedDateFrom()      { $this->autoSwapDateRange('dateFrom', 'dateTo'); }
    public function updatedArrivalDateTo()   { $this->autoSwapDateRange('arrivalDateFrom', 'arrivalDateTo'); }
    public function updatedArrivalDateFrom() { $this->autoSwapDateRange('arrivalDateFrom', 'arrivalDateTo'); }

    private function autoSwapDateRange(string $fromProp, string $toProp): void
    {
        $from = $this->{$fromProp};
        $to   = $this->{$toProp};
        if ($from && $to && $from > $to) {
            $this->{$fromProp} = $to;
            $this->{$toProp}   = $from;
        }
    }

    public function updatingPage() { $this->clearSelection(); }

    public function newRecord()
    {
        $this->resetFormFields();
        $this->editStatus = 'COMPRADO';
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
        $this->selectedIds = [(string) $id];
        $this->openBulkImport();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedIds = $this->getCurrentPageSelectableIds();
        } else {
            $this->selectedIds = [];
        }
    }

    public function clearSelection()
    {
        $this->selectedIds = [];
        $this->selectAll = false;
        $this->bulkStatus = '';
    }

    public function bulkUpdateStatus()
    {
        if (empty($this->selectedIds)) {
            $this->dispatch('errorNotRoute', ['label' => 'Selecciona al menos un registro.']);
            return;
        }
        if (empty($this->bulkStatus)) {
            $this->dispatch('errorNotRoute', ['label' => 'Selecciona el nuevo estado a aplicar.']);
            return;
        }

        $count = count($this->selectedIds);
        $this->dispatch('questionBulkStatusUsa', [
            'label' => "¿Cambiar estado de {$count} registro(s) a {$this->bulkStatus}?",
            'status' => $this->bulkStatus,
        ]);
    }

    #[On('confirmBulkStatusUsa')]
    public function confirmBulkUpdate($status)
    {
        if (empty($this->selectedIds) || empty($status)) {
            return;
        }

        $allowed = [
            UsaPurchase::STATUS_PURCHASED,
            UsaPurchase::STATUS_DELIVERED,
            UsaPurchase::STATUS_PARTIAL,
            UsaPurchase::STATUS_NOT_ARRIVED,
        ];
        if (!in_array($status, $allowed, true)) {
            $this->dispatch('errorNotRoute', ['label' => 'Estado inválido.']);
            return;
        }

        $updated = UsaPurchase::whereIn('id', $this->selectedIds)
            ->where('processed', false)
            ->update(['status' => $status]);

        $this->clearSelection();
        $this->dispatch('successNotRoute', ['label' => "{$updated} registro(s) actualizado(s)."]);
    }

    public function openBulkImport()
    {
        if (empty($this->selectedIds)) {
            $this->dispatch('errorNotRoute', ['label' => 'Selecciona al menos un registro.']);
            return;
        }

        $records = UsaPurchase::with('article:id,sku,title,purchase_price,category_id,brand_id')
            ->whereIn('id', $this->selectedIds)
            ->get();

        $valid = $records->filter(fn($r) =>
            !$r->processed
            && $r->article_id
            && in_array($r->status, [UsaPurchase::STATUS_DELIVERED, UsaPurchase::STATUS_PARTIAL])
        );

        $this->bulkDiscardedCount = $records->count() - $valid->count();

        if ($valid->isEmpty()) {
            $this->dispatch('errorNotRoute', [
                'label' => 'Ninguno de los registros seleccionados puede importarse (deben estar en ENTREGADO/PARCIAL, sin procesar y con artículo vinculado).'
            ]);
            return;
        }

        $groups = [];
        foreach ($valid as $r) {
            $key = $this->normalizeOrderNumber($r->order_number);
            if ($key === '') {
                $key = '__SOLO_' . $r->id;
            }

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'key' => $key,
                    'order_number' => trim((string) $r->order_number),
                    'is_orphan' => str_starts_with($key, '__SOLO_'),
                    'is_new' => true,
                    'existing_purchase_id' => null,
                    'existing_purchase_number' => null,
                    'provider_id' => Provider::defaultId(),
                    'voucher_type' => 'FACTURA',
                    'passenger' => trim((string) $r->carrier),
                    'items' => [],
                ];
            }

            $groups[$key]['items'][] = [
                'usa_purchase_id' => $r->id,
                'article_id' => $r->article_id,
                'category_id' => $r->article?->category_id,
                'brand_id' => $r->article?->brand_id,
                'sku' => $r->article?->sku ?? $r->sku,
                'title' => $r->article?->title ?? $r->description,
                'quantity' => (int) $r->quantity ?: 1,
                'price' => (float) ($r->article?->purchase_price ?? 0),
            ];
        }

        // Detect existing Purchase per group (by document = order_number)
        foreach ($groups as $key => &$group) {
            if ($group['is_orphan'] || $group['order_number'] === '') continue;

            $existing = Purchase::where('document', $group['order_number'])
                ->where('status', '!=', Purchase::PURCHASE_CANCELED)
                ->first();

            if ($existing) {
                $group['is_new'] = false;
                $group['existing_purchase_id'] = $existing->id;
                $group['existing_purchase_number'] = $existing->number;
                $group['provider_id'] = $existing->provider_id;
                $group['voucher_type'] = $existing->voucher_type;
            }
        }
        unset($group);

        $this->bulkGroups = array_values($groups);
        $this->bulkConfirmExisting = [];
        $this->bulkProviders = Provider::ordered()->toArray();

        $this->dispatch('open-bulk-import-modal');
    }

    public function confirmBulkImport()
    {
        if (empty($this->bulkGroups)) {
            $this->dispatch('errorNotRoute', ['label' => 'No hay grupos para importar.']);
            return;
        }

        // Per-group validation
        foreach ($this->bulkGroups as $i => $group) {
            $label = $group['order_number'] ?: "registro #{$group['items'][0]['usa_purchase_id']}";

            if ($group['is_new']) {
                if (empty($group['provider_id'])) {
                    $this->dispatch('errorNotRoute', ['label' => "Falta proveedor en {$label}."]);
                    return;
                }
                if (empty($group['voucher_type'])) {
                    $this->dispatch('errorNotRoute', ['label' => "Falta tipo de comprobante en {$label}."]);
                    return;
                }
            } else {
                if (empty($this->bulkConfirmExisting[$group['key']])) {
                    $this->dispatch('errorNotRoute', ['label' => "Confirma la edición de la compra existente para {$label}."]);
                    return;
                }
            }

            foreach ($group['items'] as $item) {
                if ((int) $item['quantity'] < 1) {
                    $this->dispatch('errorNotRoute', ['label' => "Cantidad inválida en {$label} ({$item['sku']})."]);
                    return;
                }
                if ((float) $item['price'] < 0) {
                    $this->dispatch('errorNotRoute', ['label' => "Precio inválido en {$label} ({$item['sku']})."]);
                    return;
                }
            }
        }

        $newCount = 0;
        $editedCount = 0;
        $detailsCount = 0;

        DB::transaction(function () use (&$newCount, &$editedCount, &$detailsCount) {
            foreach ($this->bulkGroups as $group) {
                if ($group['is_new']) {
                    $purchase = Purchase::create([
                        'provider_id' => $group['provider_id'],
                        'user_id' => auth()->id(),
                        'voucher_type' => $group['voucher_type'],
                        'document' => $group['is_orphan']
                            ? 'USA-' . $group['items'][0]['usa_purchase_id']
                            : $group['order_number'],
                        'passenger' => $group['passenger'] ?: null,
                        'subtotal' => 0,
                        'tax' => 0,
                        'total' => 0,
                        'status' => Purchase::PURCHASE_FINISHED,
                    ]);
                    $newCount++;
                } else {
                    $purchase = Purchase::findOrFail($group['existing_purchase_id']);
                    $editedCount++;
                }

                foreach ($group['items'] as $item) {
                    $price = (float) $item['price'];
                    $qty = (int) $item['quantity'];
                    $subtotal = $price * $qty;

                    $purchase->purchaseDetails()->create([
                        'article_id' => $item['article_id'],
                        'category_id' => $item['category_id'],
                        'brand_id' => $item['brand_id'],
                        'price' => $price,
                        'quantity' => $qty,
                        'subtotal' => $subtotal,
                        'tax' => 0,
                        'total' => $subtotal,
                    ]);
                    $detailsCount++;

                    $article = Article::find($item['article_id']);
                    if ($article) {
                        $article->increment('stock', $qty);
                        $article->update(['provider_id' => $purchase->provider_id]);
                    }

                    UsaPurchase::where('id', $item['usa_purchase_id'])->update([
                        'processed' => true,
                        'purchase_id' => $purchase->id,
                    ]);
                }

                $this->recalculatePurchaseTotals($purchase);
            }
        });

        $this->resetBulkImport();
        $this->clearSelection();

        $this->dispatch('close-bulk-import-modal');
        $this->dispatch('successNotRoute', [
            'label' => "{$detailsCount} artículo(s) importados. {$newCount} compra(s) nuevas, {$editedCount} editada(s)."
        ]);
    }

    public function cancelBulkImport()
    {
        $this->resetBulkImport();
        $this->dispatch('close-bulk-import-modal');
    }

    private function resetBulkImport()
    {
        $this->bulkGroups = [];
        $this->bulkConfirmExisting = [];
        $this->bulkDiscardedCount = 0;
        $this->bulkProviders = [];
    }

    private function normalizeOrderNumber($value): string
    {
        return strtoupper(trim((string) $value));
    }

    private function recalculatePurchaseTotals(Purchase $purchase): void
    {
        $totals = $purchase->purchaseDetails()
            ->selectRaw('SUM(subtotal) as subtotal, SUM(tax) as tax, SUM(total) as total')
            ->first();

        $purchase->update([
            'subtotal' => (float) ($totals->subtotal ?? 0),
            'tax' => (float) ($totals->tax ?? 0),
            'total' => (float) ($totals->total ?? 0),
        ]);
    }

    private function getCurrentPageSelectableIds(): array
    {
        return $this->buildBaseQuery()
            ->where('processed', false)
            ->orderByRaw('COALESCE(arrival_date, date) DESC')
            ->orderByDesc('id')
            ->forPage($this->getPage(), 20)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->toArray();
    }

    private function buildBaseQuery()
    {
        return UsaPurchase::query()
            ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
            ->when($this->filterYear, fn($q) => $q->where('year', $this->filterYear))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterStore, fn($q) => $q->where('store', $this->filterStore))
            ->when($this->dateExact,        fn($q) => $q->whereDate('date', '=', $this->dateExact))
            ->when($this->arrivalDateExact, fn($q) => $q->whereDate('arrival_date', '=', $this->arrivalDateExact))
            ->when($this->dateFrom,        fn($q) => $q->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo,          fn($q) => $q->whereDate('date', '<=', $this->dateTo))
            ->when($this->arrivalDateFrom, fn($q) => $q->whereDate('arrival_date', '>=', $this->arrivalDateFrom))
            ->when($this->arrivalDateTo,   fn($q) => $q->whereDate('arrival_date', '<=', $this->arrivalDateTo))
            ->when($this->search, function ($q) {
                $terms = collect(preg_split('/[\s\+]+/', trim($this->search)))->filter()->values();
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
            });
    }

    public function clearSearch()
    {
        $this->reset('search');
    }

    public function clearFilters()
    {
        $this->reset('filterType', 'filterYear', 'filterStatus', 'filterStore',
            'dateExact', 'arrivalDateExact',
            'dateFrom', 'dateTo', 'arrivalDateFrom', 'arrivalDateTo');
        $this->resetPage();
        $this->clearSelection();
    }

    public function clearDateFilters()
    {
        $this->reset('dateExact', 'arrivalDateExact',
            'dateFrom', 'dateTo', 'arrivalDateFrom', 'arrivalDateTo');
        $this->resetPage();
        $this->clearSelection();
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

    public function render()
    {
        $records = UsaPurchase::query()
            ->with('article:id,sku,title')
            ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
            ->when($this->filterYear, fn($q) => $q->where('year', $this->filterYear))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterStore, fn($q) => $q->where('store', $this->filterStore))
            ->when($this->dateExact,        fn($q) => $q->whereDate('date', '=', $this->dateExact))
            ->when($this->arrivalDateExact, fn($q) => $q->whereDate('arrival_date', '=', $this->arrivalDateExact))
            ->when($this->dateFrom,        fn($q) => $q->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo,          fn($q) => $q->whereDate('date', '<=', $this->dateTo))
            ->when($this->arrivalDateFrom, fn($q) => $q->whereDate('arrival_date', '>=', $this->arrivalDateFrom))
            ->when($this->arrivalDateTo,   fn($q) => $q->whereDate('arrival_date', '<=', $this->arrivalDateTo))
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
            ->orderByRaw('COALESCE(arrival_date, date) DESC')
            ->orderByDesc('id')
            ->paginate(20);

        $years = UsaPurchase::select('year')->distinct()->orderByDesc('year')->pluck('year');
        $stores = UsaPurchase::select('store')->distinct()->whereNotNull('store')->where('store', '!=', '')->orderBy('store')->pluck('store');

        $summaryQuery = UsaPurchase::query()
            ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
            ->when($this->filterYear, fn($q) => $q->where('year', $this->filterYear))
            ->when($this->filterStore, fn($q) => $q->where('store', $this->filterStore))
            ->when($this->dateExact,        fn($q) => $q->whereDate('date', '=', $this->dateExact))
            ->when($this->arrivalDateExact, fn($q) => $q->whereDate('arrival_date', '=', $this->arrivalDateExact))
            ->when($this->dateFrom,        fn($q) => $q->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo,          fn($q) => $q->whereDate('date', '<=', $this->dateTo))
            ->when($this->arrivalDateFrom, fn($q) => $q->whereDate('arrival_date', '>=', $this->arrivalDateFrom))
            ->when($this->arrivalDateTo,   fn($q) => $q->whereDate('arrival_date', '<=', $this->arrivalDateTo));

        $totalRecords = (clone $summaryQuery)->count();
        $totalDelivered = (clone $summaryQuery)->where('status', 'ENTREGADO')->count();
        $totalPurchased = (clone $summaryQuery)->where('status', 'COMPRADO')->count();
        $totalPending = (clone $summaryQuery)->whereIn('status', ['PARCIAL', 'NO LLEGO'])->count();
        $totalProcessed = (clone $summaryQuery)->where('processed', true)->count();

        return view('livewire.purchases.table-usa-purchases', compact(
            'records', 'years', 'stores',
            'totalRecords', 'totalDelivered', 'totalPurchased', 'totalPending', 'totalProcessed'
        ));
    }
}
