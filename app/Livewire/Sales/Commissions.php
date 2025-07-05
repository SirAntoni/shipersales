<?php

namespace App\Livewire\Sales;

use App\Models\Article;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Setting;
use App\Models\User;

class Commissions extends Component
{
    use WithPagination;

    public $search;
    public $details;
    public $titleNotification;
    public $bodyNotification;
    public $startDate;
    public $endDate;
    public $limit;
    public $month;
    public $year;
    public $commission;
    public $granTotalMonth;
    public $granTotalMonthCommission;
    public $users;
    public $user;

    public function mount()
    {
        $this->limit = 40;
        $this->startDate = null;
        $this->endDate = null;
        $this->month = Carbon::now()->format('m');
        $this->year = Carbon::now()->format('Y');
        $this->commission = Setting::first()->commission;
        $this->users = User::all();
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
        return redirect()->route('sales.show', $id);
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

        $sale->update(['status' => Sale::SALE_CANCELED, 'updated_at' => now()]);
        $this->render();
    }

    public function delete($id)
    {
        $this->dispatch('delete', ['label' => 'Esta seguro que desea anular la venta?.', 'btn' => 'Eliminar', 'route' => route('sales.index'), 'id' => $id]);
    }

    public function changeStatus($id)
    {
        if (auth()->user()->can('update')) {
            $sale = Sale::find($id);
            if ($sale->status == Sale::SALE_APPROVED || $sale->status == Sale::SALE_PENDING) {
                $sale->status = ($sale->status == Sale::SALE_APPROVED)
                    ? Sale::SALE_PENDING
                    : Sale::SALE_APPROVED;
                $sale->save();
                $this->dispatch('notification');
            }
        }
    }

    public function verPDF($id)
    {
        $url = route('pdf.view', ['id' => $id]);
        $this->dispatch('abrir-nueva-pestania', ['url' => $url]);
    }


    public function reportCommissions(){
        $this->validate([
            'month' => 'required',
            'year' => 'required'
        ]);

        $url = route('reports.commissions.export',['month' => $this->month,'year' => $this->year]);
        $this->dispatch('abrir-nueva-pestania', ['url' => $url]);
    }

    public function render()
    {

        $limit = $this->limit ?? 40;
        $query = Sale::query()
            ->with([
                'saleDetails.article',
                'document',
                'client:id,name',
                'contact:id,name',
                'paymentMethod:id,name'
            ])
            ->where('status', '!=', Sale::SALE_CANCELED)
            ->whereIn('sales.contact_id', [5, 1, 4, 12])
            // —> AÑADE ESTO:
            ->when($this->user, function ($query, $userId) {
                $query->where('sales.user_id', $userId);
            })
            // —> CONTINÚA CON EL RESTO…
            ->when($this->search, function ($query, $search) {
                $terms = collect(preg_split('/[\s\+]+/', trim($search)))
                    ->filter()
                    ->map(fn($t) => Str::lower($t));

                foreach ($terms as $term) {
                    $query->where(function ($q) use ($term) {
                        $q->whereRaw('LOWER(number) LIKE ?', ["%{$term}%"])
                            ->orWhereHas('client', fn($c) => $c->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"]))
                            ->orWhereHas('contact', fn($c) => $c->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"]))
                            ->orWhereHas('paymentMethod', fn($p) => $p->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"]))
                            ->orWhereHas('saleDetails.article', fn($a) => $a->whereRaw('LOWER(title) LIKE ?', ["%{$term}%"]));
                    });
                }
            })
            ->whereMonth('sales.created_at', '=', $this->month)
            ->whereYear('sales.created_at', '=', $this->year)
            ->orderByDesc('id');


        $this->granTotalMonth = (clone $query)->sum('total');
        $this->granTotalMonthCommission = ($this->granTotalMonth * $this->commission) / 100;

        $sales = $query->paginate($limit);

        foreach ($sales as $sale) {
            $htmlDetails = "<p><strong>Cliente: </strong> {$sale->client->name} </p><br><table style='border: 1px solid;'><thead style='border:1px solid;'><tr><th style='border:1px solid'>Titulo</th><th style='border:1px solid;padding:10px'>Cantidad</th><th style='padding:10px'>Precio</thstyle></tr></thead><tbody style='border:1px solid;'>";
            $btnDetails = '';
            switch ($sale->status) {
                case Sale::SALE_APPROVED:
                    $btnDetails = 'success';
                    break;
                case Sale::SALE_OBSERVATION:
                    $btnDetails = 'warning';
                    break;
                default:
                    $btnDetails = 'dark';
                    break;
            }
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
            if ($sale->observations != null) {
                $sale->htmlDetails .= "<br><p>Observaciones: " . $sale->observations . "</p><br>";
            }
            $sale->btnDetails = $btnDetails;

        }

        return view('livewire.sales.commissions', compact('sales'));
    }
}
