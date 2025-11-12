<?php

namespace App\Livewire\Canceled;

use Carbon\Carbon;
use Illuminate\Support\Str;
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
        $sales = Sale::query()
            ->with([
                'saleDetails.article',
                'document',
                'client:id,name',
                'contact:id,name',
                'paymentMethod:id,name'
            ])
            ->where('status', '=', Sale::SALE_CANCELED)
            ->when($this->search, function ($query, $search) {
                // 1. Reemplazamos "+" por espacio y separamos por espacios
                $terms = collect(preg_split('/[\s\+]+/', trim($search)))
                    ->filter()    // eliminamos strings vacíos
                    ->map(fn($t) => Str::lower($t));

                // 2. Por cada término, forzamos que aparezca en algún campo/relación
                foreach ($terms as $term) {
                    $query->where(function ($q) use ($term) {
                        $q->whereRaw('LOWER(number) LIKE ?', ["%{$term}%"])
                            ->orWhereHas('client', fn ($c) =>
                            $c->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                            )
                            ->orWhereHas('contact', fn ($c) =>
                            $c->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                            )
                            ->orWhereHas('paymentMethod', fn ($p) =>
                            $p->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                            )
                            ->orWhereHas('saleDetails.article', fn ($a) =>
                            $a->whereRaw('LOWER(title) LIKE ?', ["%{$term}%"])
                            );
                    });
                }
            })
            ->orderByDesc('sales.deletion_date')
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
