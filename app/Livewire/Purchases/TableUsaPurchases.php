<?php

namespace App\Livewire\Purchases;

use App\Models\UsaPurchase;
use App\Imports\UsaPurchaseImport;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class TableUsaPurchases extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $filterType = '';
    public $filterYear = '';
    public $filterStatus = '';
    public $filterStore = '';

    public $importFile;

    // Edit modal
    public $editingId = null;
    public $editDate = '';
    public $editCarrier = '';
    public $editStore = '';
    public $editOrderNumber = '';
    public $editTracking = '';
    public $editQuantity = 0;
    public $editDescription = '';
    public $editStatus = '';
    public $editArrivalDate = '';
    public $editComments = '';
    public $editType = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterType()
    {
        $this->resetPage();
    }

    public function updatingFilterYear()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterStore()
    {
        $this->resetPage();
    }

    public function importExcel()
    {
        if (!$this->importFile) {
            $this->dispatch('errorNotRoute', ['label' => 'Selecciona un archivo primero.']);
            return;
        }

        $ext = strtolower($this->importFile->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xls'])) {
            $this->dispatch('errorNotRoute', ['label' => 'El archivo debe ser .xlsx o .xls']);
            return;
        }

        try {
            $import = new UsaPurchaseImport();
            $import->import($this->importFile->getRealPath());

            $this->reset('importFile');
            $this->dispatch('close-import-usa-modal');
            $this->dispatch('successNotRoute', [
                'label' => "Importación completada: {$import->imported} registros importados, {$import->skipped} omitidos."
            ]);
        } catch (\Exception $e) {
            $this->dispatch('errorNotRoute', ['label' => 'Error al importar: ' . $e->getMessage()]);
        }
    }

    public function newRecord()
    {
        $this->resetEditFields();
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
        $this->editQuantity = $record->quantity;
        $this->editDescription = $record->description ?? '';
        $this->editStatus = $record->status ?? '';
        $this->editArrivalDate = $record->arrival_date?->format('Y-m-d') ?? '';
        $this->editComments = $record->comments ?? '';
        $this->editType = $record->type ?? '';
        $this->dispatch('open-edit-usa-modal');
    }

    public function saveRecord()
    {
        $this->validate([
            'editCarrier' => 'required',
            'editDescription' => 'required',
            'editStatus' => 'required',
            'editType' => 'required',
        ], [], [
            'editCarrier' => 'pasajero/consignatario',
            'editDescription' => 'descripción',
            'editStatus' => 'estado',
            'editType' => 'tipo',
        ]);

        $data = [
            'date' => $this->editDate ?: null,
            'carrier' => $this->editCarrier,
            'store' => $this->editStore,
            'order_number' => $this->editOrderNumber,
            'tracking' => $this->editTracking,
            'quantity' => (int) $this->editQuantity,
            'description' => $this->editDescription,
            'status' => $this->editStatus,
            'arrival_date' => $this->editArrivalDate ?: null,
            'comments' => $this->editComments ?: null,
            'type' => $this->editType,
            'year' => $this->editDate ? date('Y', strtotime($this->editDate)) : now()->year,
        ];

        if ($this->editingId) {
            UsaPurchase::findOrFail($this->editingId)->update($data);
            $msg = 'Registro actualizado.';
        } else {
            UsaPurchase::create($data);
            $msg = 'Registro creado.';
        }

        $this->dispatch('close-edit-usa-modal');
        $this->dispatch('successNotRoute', ['label' => $msg]);
        $this->resetEditFields();
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

    public function clearSearch()
    {
        $this->reset('search');
    }

    public function clearFilters()
    {
        $this->reset('filterType', 'filterYear', 'filterStatus', 'filterStore');
        $this->resetPage();
    }

    private function resetEditFields()
    {
        $this->reset([
            'editingId', 'editDate', 'editCarrier', 'editStore',
            'editOrderNumber', 'editTracking', 'editQuantity',
            'editDescription', 'editStatus', 'editArrivalDate',
            'editComments', 'editType'
        ]);
    }

    public function render()
    {
        $records = UsaPurchase::query()
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
                            ->orWhere('order_number', 'like', $like)
                            ->orWhere('tracking', 'like', $like)
                            ->orWhere('store', 'like', $like);
                    });
                }
            })
            ->orderByDesc('date')
            ->paginate(20);

        // Data for filters
        $years = UsaPurchase::select('year')->distinct()->orderByDesc('year')->pluck('year');
        $stores = UsaPurchase::select('store')->distinct()->whereNotNull('store')->where('store', '!=', '')->orderBy('store')->pluck('store');

        // Summary counts
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
