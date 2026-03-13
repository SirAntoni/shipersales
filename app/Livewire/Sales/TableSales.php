<?php

namespace App\Livewire\Sales;

use App\Models\Article;
use Carbon\Carbon;
use Livewire\Component;
use App\Models\Sale;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use Log;
use Illuminate\Validation\ValidationException;

class TableSales extends Component
{
    use WithPagination;

    public $search;
    public $details;
    public $titleNotification;
    public $bodyNotification;

    public $startDate;
    public $endDate;
    public $limit;
    public $status;

    public $sectionDelete = false;
    public $sectionMoreDetails = false;

    public $motive;
    public $motiveDetail;
    public $saleDeleteSelect = null;

    public ?int $editingSaleId = null;
    public $newTotal = null;

    public function mount(){
        $this->limit = 40;
        $this->startDate = null;
        $this->endDate = null;
        $this->status = null;
    }
    public function startEditTotal(int $saleId): void
    {
        $sale = Sale::with('saleDetails')->findOrFail($saleId);

        // Reglas de negocio: no editar canceladas
        if ($sale->status == Sale::SALE_CANCELED) {
            $this->dispatch('errorNotRoute', ['label' => 'No puedes editar una venta anulada.']);
            return;
        }

        // Debe tener exactamente 1 detalle y cantidad 1
        if ($sale->saleDetails->count() !== 1 || (int)$sale->saleDetails->first()->quantity !== 1) {
            $this->dispatch('errorNotRoute', ['label' => 'Solo se puede editar el total cuando hay 1 ítem con cantidad 1.']);
            return;
        }

        $this->editingSaleId = $saleId;

        // Si tu campo es total_amount, cámbialo aquí:
        $this->newTotal = number_format((float) ($sale->total ?? 0), 2, '.', '');
    }

    public function cancelEditTotal(): void
    {
        $this->editingSaleId = null;
        $this->newTotal = null;
    }

    // --- Guardar edición ---
    public function saveEditTotal(int $saleId): void
    {
        $this->validate([
            'newTotal' => 'required|numeric|min:0.01',
        ], [], [
            'newTotal' => 'nuevo total',
        ]);

        DB::transaction(function () use ($saleId) {
            /** @var \App\Models\Sale $sale */
            $sale = Sale::lockForUpdate()->with('saleDetails')->findOrFail($saleId);

            if ($sale->status == Sale::SALE_CANCELED) {
                throw ValidationException::withMessages(['newTotal' => 'No puedes editar una venta anulada.']);
            }

            if ($sale->saleDetails()->count() !== 1) {
                throw ValidationException::withMessages(['newTotal' => 'La venta debe tener exactamente un ítem.']);
            }

            $detail = $sale->saleDetails()->first();
            if ((int)$detail->quantity !== 1) {
                throw ValidationException::withMessages(['newTotal' => 'La cantidad del ítem debe ser 1.']);
            }

            $nuevo = (float) $this->newTotal;

            // Actualizar precio del detalle (y subtotal si aplica)
            $detail->price = $nuevo;
            if ($detail->isFillable('subtotal') || isset($detail->subtotal)) {
                $detail->subtotal = $nuevo;
            }
            $detail->save();

            // Actualizar total de la venta
            // Si tu columna es total_amount, cámbialo aquí:
            $sale->total = $nuevo;
            $sale->updated_at = now();
            $sale->save();
        });

        // Reset UI
        $this->editingSaleId = null;
        $this->newTotal = null;

        // Notificación UI
        $this->dispatch('successNotRoute', ['label' => 'Total actualizado correctamente.']);
    }

    public function updatingSearch(){
        $this->resetPage();
    }

    public function updatedMotive(){
        if($this->motive == 'Otros'){
            $this->sectionMoreDetails = true;
        }else{
            $this->sectionMoreDetails = false;
            $this->reset('motiveDetail');
        }
    }

    public function deleteSale(){

        $this->validate([
            'motive' => 'required',
            'motiveDetail' => 'required_if:motive,Otros'
        ],[],[
            'motive' => 'motivo',
            'motiveDetail' => 'detalle'
        ]);

        $this->destroy($this->saleDeleteSelect);
        $this->sectionDelete = false;
        $this->saleDeleteSelect = null;

        $this->dispatch('successNotRoute', ['label' => 'Venta eliminada con éxito.']);

    }

    public function rendered(){
        $this->dispatch('reinit-tippy');
    }

    public function edit($id)
    {
        $url = route('sales.show', $id);
        $this->dispatch('abrir-nueva-pestania', ['url' => $url]);
    }

    public function newDocument($id)
    {
        return redirect()->route('documents.show', $id);
    }

    #[On('destroy')]
    public function destroy($id)
    {

        $sale = Sale::find($id);

        DB::transaction(function () use ($sale) {
            $articleIds = $sale->saleDetails->pluck('article_id')->unique();

            $articles = Article::whereIn('id', $articleIds)->get()->keyBy('id');

            foreach ($sale->saleDetails as $item) {
                if (isset($articles[$item->article_id])) {
                    $article = $articles[$item->article_id];
                    $article->stock += $item->quantity;
                    $article->save();
                } else {
                    throw new \Exception("Artículo no encontrado: {$item->article_id}");
                }
            }
        });

        $sale->update([
            'status' => Sale::SALE_CANCELED,
            'updated_at' => now(),
            'deletion_date' => now(),
            'deletion_reason' => ($this->motive == 'Otros') ? $this->motive . ' - ' . $this->motiveDetail : $this->motive
        ]);
        $this->render();
    }

    public function delete($id)
    {
        $this->saleDeleteSelect = $id;
        $this->sectionDelete = true;
        $this->dispatch('topPage');
        //$this->dispatch('delete', ['label' => 'Esta seguro que desea anular la venta?.', 'btn' => 'Eliminar', 'route' => route('sales.index'), 'id' => $id]);
    }

    public function questionStatus($id)
    {
        if(auth()->user()->can('update')){
            $sale = Sale::find($id);
            if($sale->status == Sale::SALE_APPROVED){
                $this->dispatch('question', [
                    'label' => 'Esta seguro que desea cambiar de estado a la venta?',
                    'btn' => 'Editar',
                    'id' => $id
                ]);
            }else{
                $this->changeStatus($id);
            }
        }
    }

    #[On('changeStatus')]
    public function changeStatus($id){
        $sale = Sale::find($id);
        if ($sale->status == Sale::SALE_APPROVED || $sale->status == Sale::SALE_PENDING) {
            $sale->status = ($sale->status == Sale::SALE_APPROVED)
                ? Sale::SALE_PENDING
                : Sale::SALE_APPROVED;
            $sale->save();
            $this->dispatch('notification');
        }
    }

    public function verPDF($id)
    {
        $url = route('pdf.view', ['id' => $id]);
        $this->dispatch('abrir-nueva-pestania', ['url' => $url]);
    }

    public function clearSearch()
    {
        $this->reset('search');
    }

    public function refresh(){
        $this->render();
    }

    public function render()
    {

        $limit = $this->limit ?? 40;
        $sales = Sale::query()
            ->with([
                'saleDetails.article',
                'document',
                'user:id,name',
                'client:id,name',
                'contact:id,name',
                'paymentMethod:id,name'
            ])
            ->where('status', '!=', Sale::SALE_CANCELED)
            ->when($this->search, function ($query, $search) {
                $terms = collect(preg_split('/[\s\+]+/', trim($search)))
                    ->filter()
                    ->values();

                foreach ($terms as $term) {
                    $like = '%'.$term.'%';
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
            ->when($this->status, function ($query) {
                $query->where('sales.status', $this->status);
            })
            ->orderByDesc('sales.id')
            ->paginate($limit);

        $statusVariants = [
            Sale::SALE_APPROVED    => 'success',
            Sale::SALE_OBSERVATION => 'warning',
            Sale::SALE_SQUARE      => 'primary',
        ];

        foreach ($sales as $sale) {
            $sale->btnDetails = $statusVariants[$sale->status] ?? 'dark';

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

            if ($sale->observations) {
                $sale->htmlDetails .= "<br><p>Observaciones: " . e($sale->observations) . "</p><br>";
            }
        }

        return view('livewire.sales.table-sales', compact('sales'));
    }
}
