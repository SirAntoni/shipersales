<?php

namespace App\Livewire\Sales;

use App\Models\Article;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class TableReturns extends Component
{
    use WithPagination;

    public $search = '';
    public $startDate;
    public $endDate;
    public $limit;

    public function mount()
    {
        $this->limit = 15;
        $this->startDate = null;
        $this->endDate = null;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function rendered()
    {
        $this->dispatch('reinit-tippy');
    }

    public function edit($id)
    {
        $url = route('sales.show', $id);
        $this->dispatch('abrir-nueva-pestania', ['url' => $url]);
    }

    public function confirmCancel($id)
    {
        $this->dispatch('questionConfirmReturn', [
            'label' => '¿Confirmar anulación por devolución? Se devolverá el stock.',
            'id' => $id
        ]);
    }

    #[On('processReturn')]
    public function processReturn($id)
    {
        $sale = Sale::findOrFail($id);

        if ((int) $sale->status !== Sale::SALE_RETURN) {
            $this->dispatch('errorNotRoute', ['label' => 'Esta venta no está en estado de devolución.']);
            return;
        }

        DB::transaction(function () use ($sale) {
            $articleIds = $sale->saleDetails->pluck('article_id')->unique();
            $articles = Article::whereIn('id', $articleIds)->get()->keyBy('id');

            foreach ($sale->saleDetails as $item) {
                if (isset($articles[$item->article_id])) {
                    $articles[$item->article_id]->increment('stock', $item->quantity);
                }
            }
        });

        $sale->update([
            'status' => Sale::SALE_CANCELED,
            'deletion_date' => now(),
            'deletion_reason' => 'Devolución confirmada'
        ]);

        $this->dispatch('successNotRoute', ['label' => 'Devolución procesada y stock devuelto.']);
    }

    public function revertReturn($id)
    {
        $this->dispatch('questionRevertReturn', [
            'label' => '¿Desea revertir esta devolución y devolver la venta a su estado aprobado?',
            'id' => $id
        ]);
    }

    #[On('processRevertReturn')]
    public function processRevertReturn($id)
    {
        $sale = Sale::findOrFail($id);

        if ((int) $sale->status !== Sale::SALE_RETURN) {
            $this->dispatch('errorNotRoute', ['label' => 'Esta venta no está en estado de devolución.']);
            return;
        }

        $sale->update(['status' => Sale::SALE_APPROVED]);
        $this->dispatch('successNotRoute', ['label' => 'Venta devuelta a estado aprobado.']);
    }

    public function clearSearch()
    {
        $this->reset('search');
    }

    public function render()
    {
        $limit = $this->limit ?? 15;

        $sales = Sale::query()
            ->with([
                'saleDetails.article',
                'document',
                'user:id,name',
                'client:id,name',
                'contact:id,name',
                'paymentMethod:id,name'
            ])
            ->where('status', Sale::SALE_RETURN)
            ->when($this->search, function ($query, $search) {
                $terms = collect(preg_split('/[\s\+]+/', trim($search)))
                    ->filter()
                    ->values();

                foreach ($terms as $term) {
                    $like = '%' . $term . '%';
                    $query->where(function ($q) use ($like) {
                        $q->where('sales.number', 'like', $like)
                            ->orWhereIn('sales.client_id', function ($sub) use ($like) {
                                $sub->select('id')->from('clients')->where('name', 'like', $like);
                            })
                            ->orWhereIn('sales.contact_id', function ($sub) use ($like) {
                                $sub->select('id')->from('contacts')->where('name', 'like', $like);
                            })
                            ->orWhereIn('sales.payment_method_id', function ($sub) use ($like) {
                                $sub->select('id')->from('payment_methods')->where('name', 'like', $like);
                            })
                            ->orWhereIn('sales.id', function ($sub) use ($like) {
                                $sub->select('sale_id')->from('sale_details')
                                    ->join('articles', 'articles.id', '=', 'sale_details.article_id')
                                    ->where('articles.title', 'like', $like);
                            });
                    });
                }
            })
            ->when($this->startDate && $this->endDate, function ($query) {
                $query->whereBetween('sales.created_at', [
                    Carbon::parse($this->startDate)->startOfDay(),
                    Carbon::parse($this->endDate)->endOfDay(),
                ]);
            })
            ->orderByDesc('sales.updated_at')
            ->paginate($limit);

        foreach ($sales as $sale) {
            $rows = '';
            foreach ($sale->saleDetails as $detail) {
                $title = e($detail->article?->title ?? '-');
                $rows .= "<tr><td style='border:1px solid;padding:5px'>{$title}</td>"
                    . "<td style='text-align:center;border:1px solid;'>{$detail->quantity}</td>"
                    . "<td style='text-align:center;border:1px solid;'>{$detail->price}</td></tr>";
            }

            $clientName = e($sale->client->name ?? '-');
            $sale->htmlDetails = "<p><strong>Cliente: </strong> {$clientName}</p><br>"
                . "<table style='border:1px solid;'><thead style='border:1px solid;'><tr>"
                . "<th style='border:1px solid'>Titulo</th>"
                . "<th style='border:1px solid;padding:10px'>Cantidad</th>"
                . "<th style='padding:10px'>Precio</th></tr></thead>"
                . "<tbody style='border:1px solid;'>{$rows}</tbody></table>";
        }

        return view('livewire.sales.table-returns', compact('sales'));
    }
}
