<?php

namespace App\Livewire\Quotations;

use App\Models\Article;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class TableQuotations extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';

    /** Modal de productos nuevos (manuales) de una cotización */
    public ?int $catalogQuotationId = null;
    public ?string $catalogQuotationNumber = null;
    public array $catalogItems = [];

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

    /** Abre el modal con los productos manuales de la cotización */
    public function openCatalogModal($quotationId)
    {
        $quotation = Quotation::with(['quotationDetails' => fn ($q) => $q->whereNull('article_id')])
            ->findOrFail($quotationId);

        $this->catalogQuotationId     = $quotation->id;
        $this->catalogQuotationNumber = $quotation->number;
        $this->catalogItems = $quotation->quotationDetails->map(fn ($d) => [
            'detail_id' => $d->id,
            'title'     => $d->custom_product['title'] ?? '—',
            'detail'    => $d->custom_product['detail'] ?? null,
            'price'     => (float) $d->price,
            'saved'     => false,
        ])->values()->toArray();

        $this->dispatch('open-catalog-modal');
    }

    /** Crea el artículo en el catálogo y lo enlaza al detalle de la cotización */
    public function saveToCatalog($detailId)
    {
        $detail = QuotationDetail::whereNull('article_id')->find($detailId);

        if (!$detail || empty($detail->custom_product)) {
            $this->dispatch('errorNotRoute', ['label' => 'Este producto ya fue guardado en el catálogo.']);
            return;
        }

        $data = $detail->custom_product;

        try {
            DB::transaction(function () use ($detail, $data) {
                $article = Article::create([
                    'title'          => $data['title'],
                    'detail'         => $data['detail'] ?? '',
                    'description'    => '',
                    'sku'            => Article::generateSku(),
                    'stock'          => 0,
                    'brand_id'       => $data['brand_id'],
                    'category_id'    => $data['category_id'],
                    'purchase_price' => $data['purchase_price'] ?? 0,
                    'sale_price'     => (float) $detail->price,
                    'status'         => 'active',
                    'on_demand'      => 0,
                ]);

                $detail->update(['article_id' => $article->id]);
            });

            foreach ($this->catalogItems as &$item) {
                if ($item['detail_id'] == $detailId) {
                    $item['saved'] = true;
                }
            }
            unset($item);

            $this->dispatch('successNotRoute', ['label' => "«{$data['title']}» se guardó en el catálogo (stock 0)."]);
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('errorNotRoute', ['label' => 'Ocurrió un error guardando el producto en el catálogo.']);
        }
    }

    public function render()
    {
        $quotations = Quotation::with(['client:id,name', 'user:id,name'])
            ->withCount(['quotationDetails as custom_pending_count' => fn ($q) => $q->whereNull('article_id')])
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
