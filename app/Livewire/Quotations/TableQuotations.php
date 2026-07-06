<?php

namespace App\Livewire\Quotations;

use App\Models\Quotation;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class TableQuotations extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function rendered()
    {
        $this->dispatch('reinit-tippy');
    }

    public function changeStatus($id, $status)
    {
        $quotation = Quotation::findOrFail($id);

        if (!in_array($status, [Quotation::STATUS_ACCEPTED, Quotation::STATUS_REJECTED, Quotation::STATUS_PENDING])) {
            return;
        }

        $quotation->update(['status' => $status]);

        $this->dispatch('successNotRoute', ['label' => "La cotización {$quotation->number} pasó a estado " . strtoupper($status) . '.']);
    }

    public function delete($id)
    {
        $this->dispatch('delete', [
            'label' => '¿Está seguro que desea eliminar la cotización?',
            'btn'   => 'Eliminar',
            'id'    => $id,
        ]);
    }

    #[On('destroy')]
    public function destroy($id)
    {
        Quotation::find($id)?->delete(); // detalles caen por cascade
    }

    public function render()
    {
        $quotations = Quotation::with(['client:id,name', 'user:id,name'])
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('number', 'like', "%{$this->search}%")
                      ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.quotations.table-quotations', compact('quotations'));
    }
}
