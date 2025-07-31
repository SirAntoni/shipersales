<?php

namespace App\Livewire\Canceled;

use Livewire\Component;
use App\Models\Sale;

class TableCanceled extends Component
{
    public $search;
    public function rendered(){
        $this->dispatch('reinit-tippy');
    }
    public function edit($id)
    {
        return redirect()->route('sales.show', $id);
    }
    public function render()
    {

        $limit = 15;
        $sales = Sale::with(['saleDetails', 'client:id,name','contact:id,name','paymentMethod:id,name','user:id,name'])
            ->where('status', Sale::SALE_CANCELED)
            ->when($this->search, function ($query, $search) {
                $search = trim($search);
                $query->where(function ($q) use ($search) {
                    $q->where('number', 'like', "%{$search}%")
                        ->orWhereHas('contact', fn ($c) => $c->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('saleDetails.article', fn($a) =>
                            $a->where('title', 'like', "%{$search}%")
                        );
                });
            })
            ->orderBy('deletion_date', 'desc')
            ->orderByDesc('id')
            ->paginate($limit);

        foreach ($sales as $sale) {

            $htmlDetails = "<p><strong>Cliente: </strong> {$sale->client->name} </p><br><table style='border: 1px solid;'><thead style='border:1px solid;'><tr><th style='border:1px solid'>Titulo</th><th style='border:1px solid;padding:10px'>Cantidad</th><th style='padding:10px'>Precio</thstyle></tr></thead><tbody style='border:1px solid;'>";

            foreach ($sale->saleDetails as $detail) {
                $htmlDetails .= "<tr>"
                    . "<td style='border:1px solid;padding:5px'>"
                    . ($detail->article?->title ?? '-')
                    . "</td>"
                    . "<td style='text-align:center;border:1px solid;'>{$detail->quantity}</td>"
                    . "<td style='text-align:center;border:1px solid;'>{$detail->price}</td>"
                    . "</tr>";
            }
            $sale->htmlDetails = $htmlDetails . '</tbody></table>';

        }

        return view('livewire.canceled.table-canceled', compact('sales'));
    }
}
