<?php

namespace App\Livewire\Purchases;

use App\Models\Article;
use App\Models\PurchaseDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;
use App\Models\Purchase;
use Livewire\Attributes\On;
use Livewire\WithPagination;

class TablePurchases extends Component
{

    use WithPagination;
    public $search;
    public $startDate;
    public $endDate;

    public function updatingSearch(){
        $this->resetPage();
    }

    public function rendered(){
        $this->dispatch('reinit-tippy');
    }

    public function changeStatus($id)
    {
        if(auth()->user()->can('update')){
            $purchase = Purchase::find($id);
            if ($purchase->status == Purchase::PURCHASE_FINISHED || $purchase->status == Purchase::PURCHASE_NOT_FINISHED) {
                $purchase->status = ($purchase->status == Purchase::PURCHASE_FINISHED)
                    ? Purchase::PURCHASE_NOT_FINISHED
                    : Purchase::PURCHASE_FINISHED;
                $purchase->save();
                $this->dispatch('notification');
            }
        }

    }

    public function edit($id){
        return redirect()->route('purchases.show',$id);
    }

    #[On('destroy')]
    public function destroy($id){
        $purchase = Purchase::find($id);
        DB::transaction(function () use ($purchase) {
            $articleIds = $purchase->purchaseDetails->pluck('article_id')->unique();

            $articles = Article::whereIn('id', $articleIds)->get()->keyBy('id');

            foreach ($purchase->purchaseDetails as $item) {
                if (isset($articles[$item->article_id])) {
                    $article = $articles[$item->article_id];
                    $article->stock -= $item->quantity;
                    $article->save();
                } else {
                    throw new \Exception("Artículo no encontrado: {$item->article_id}");
                }
            }
        });
        $purchase->update(['status' => Purchase::PURCHASE_CANCELED,'updated_at' => now()]);
        $this->render();
    }

    public function delete($id){
        $this->dispatch('delete',['label' => 'Esta seguro que desea eliminar la compra?.','btn' => 'Eliminar','route' => route('purchases.index'),'id' => $id]);
    }

    public function render()
    {
        $limit = 15;
        $purchases = Purchase::query()
            ->with([
                'purchaseDetails.article',
                'provider:id,name'
            ])
            ->where('status', '!=', Purchase::PURCHASE_CANCELED)

            // Búsqueda avanzada multi-término
            ->when($this->search, function ($query, $search) {
                // 1. Reemplazamos "+" por espacio y separamos por espacios
                $terms = collect(preg_split('/[\s\+]+/', trim($search)))
                    ->filter()    // eliminamos strings vacíos
                    ->map(fn($t) => Str::lower($t));

                // 2. Por cada término, forzamos que aparezca en algún campo/relación
                foreach ($terms as $term) {
                    $query->where(function ($q) use ($term) {
                        $q->whereRaw('LOWER(document) LIKE ?', ["%{$term}%"])
                            ->orWhereRaw('LOWER(passenger) LIKE ?', ["%{$term}%"])
                            ->orWhereHas('provider', fn ($p) =>
                            $p->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                            )
                            ->orWhereHas('purchaseDetails.article', fn ($a) =>
                            $a->whereRaw('LOWER(title) LIKE ?', ["%{$term}%"])
                            );
                    });
                }
            })

            // Filtro por rango de fechas
            ->when($this->startDate && $this->endDate, function ($query) {
                $query->whereBetween('purchases.created_at', [
                    Carbon::parse($this->startDate)->startOfDay(),
                    Carbon::parse($this->endDate)->endOfDay(),
                ]);
            })

            ->orderByDesc('id')
            ->paginate($limit);

        foreach ($purchases as $purchase) {
            $btnColor = '';
            switch($purchase->status) {
                case Purchase::PURCHASE_FINISHED:
                    $btnColor = 'success';
                    break;
                case Purchase::PURCHASE_NOT_FINISHED:
                    $btnColor = 'warning';
                    break;
                default:
                    $btnColor = 'info';
                    break;
            }

            $purchase->btnColor = $btnColor;
            $purchase->btnSize = "sm";

            $htmlDetails = "<p><strong>Proveedor: </strong> {$purchase->provider->name} </p><br><table style='border: 1px solid;'><thead style='border:1px solid;'><tr><th style='border:1px solid'>Titulo</th><th style='border:1px solid;padding:10px'>Cantidad</th><th style='padding:10px'>Precio</thstyle></tr></thead><tbody style='border:1px solid;'>";

            foreach ($purchase->purchaseDetails as $detail) {
                $htmlDetails .= "<tr>"
                    . "<td style='border:1px solid;padding:5px'>"
                    . ($detail->article?->title ?? '-')
                    . "</td>"
                    . "<td style='text-align:center;border:1px solid;'>{$detail->quantity}</td>"
                    . "<td style='text-align:center;border:1px solid;'>{$detail->price}</td>"
                    . "</tr>";
            }
            $purchase->htmlDetails = $htmlDetails . "</tbody></table>";

        }

        return view('livewire.purchases.table-purchases', compact('purchases'));
    }
}
