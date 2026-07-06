<?php

namespace App\Livewire\Quotations;

use App\Models\Article;
use App\Models\Client;
use App\Models\Quotation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class NewQuotation extends Component
{
    public $client;
    public $date;
    public $validDays = 7;
    public $notes;
    public $tax = true;

    public $granSubtotal = 0;
    public $granTax = 0;
    public $granTotal = 0;

    public $articleSelected;
    public array $articlesSelected = [];

    public function rules()
    {
        return [
            'client'           => 'required',
            'date'             => 'required|date_format:Y-m-d',
            'validDays'        => 'required|integer|min:1|max:90',
            'notes'            => 'nullable|string|max:1000',
            'articlesSelected' => 'required|array|min:1',
        ];
    }

    protected $messages = [
        'articlesSelected.required' => 'Debe agregar al menos 1 artículo a la cotización.',
        'client.required'           => 'Debe seleccionar un cliente.',
        'validDays.required'        => 'Debe indicar los días de validez.',
    ];

    public function mount()
    {
        $this->date = Carbon::now()->format('Y-m-d');
    }

    public function save()
    {
        $this->validate();

        try {
            $quotation = null;

            DB::transaction(function () use (&$quotation) {
                $quotation = Quotation::create([
                    'number'      => Quotation::nextNumber(),
                    'date'        => $this->date,
                    'valid_until' => Carbon::parse($this->date)->addDays((int) $this->validDays)->format('Y-m-d'),
                    'status'      => Quotation::STATUS_PENDING,
                    'notes'       => $this->notes,
                    'subtotal'    => $this->granSubtotal,
                    'tax'         => $this->granTax,
                    'total'       => $this->granTotal,
                    'client_id'   => $this->client,
                    'user_id'     => auth()->id(),
                ]);

                foreach ($this->articlesSelected as $item) {
                    $subtotal = (float) $item['total'];
                    $tax      = ($this->tax == 1) ? $subtotal * 0.18 : 0.0;

                    $quotation->quotationDetails()->create([
                        'article_id' => (int) $item['id'],
                        'price'      => (float) $item['price'],
                        'quantity'   => (int) $item['quantity'],
                        'subtotal'   => $subtotal,
                        'tax'        => $tax,
                        'total'      => $subtotal + $tax,
                    ]);
                }
            });

            $this->dispatch('success', [
                'label' => "La cotización {$quotation->number} fue registrada con éxito.",
                'btn'   => 'Ir a cotizaciones',
                'route' => route('quotations.index'),
            ]);
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('error', ['label' => 'Ocurrió un error registrando la cotización. Inténtelo nuevamente.']);
        }
    }

    public function updateTax()
    {
        $this->calculateTotals();
    }

    public function searchClients($query)
    {
        return Client::query()
            ->where('name', 'like', '%' . $query . '%')
            ->orWhere('document_number', 'like', '%' . $query . '%')
            ->limit(10)
            ->get(['id', 'name', 'document_number'])
            ->map(fn ($c) => [
                'value' => $c->id,
                'text'  => $c->name . ' - ' . $c->document_number,
            ])
            ->toArray();
    }

    public function searchArticles($query)
    {
        return Article::query()
            ->where('title', 'like', '%' . $query . '%')
            ->limit(10)
            ->get(['id', 'title', 'stock', 'sale_price'])
            ->map(fn ($c) => [
                'value' => $c->id,
                'text'  => "{$c->title} | Stock: {$c->stock} | Precio Venta: S/.{$c->sale_price}",
            ])
            ->toArray();
    }

    public function updatedArticleSelected($id)
    {
        if ($id) {
            $this->addToArticle($id);
            $this->articleSelected = null;
        }
    }

    public function addToArticle($id)
    {
        $article = Article::find($id);

        if (!$article) {
            return;
        }

        $index = collect($this->articlesSelected)->search(fn ($item) => $item['id'] == $article->id);

        // Una cotización no compromete stock: no se limita por disponibilidad
        if ($index !== false) {
            $this->articlesSelected[$index]['quantity']++;
            $this->articlesSelected[$index]['total'] =
                $this->articlesSelected[$index]['quantity'] * $this->articlesSelected[$index]['price'];
        } else {
            $this->articlesSelected[] = [
                'id'       => $article->id,
                'title'    => $article->title,
                'price'    => $article->sale_price,
                'quantity' => 1,
                'total'    => $article->sale_price,
            ];
        }

        $this->calculateTotals();
    }

    public function remove($index)
    {
        unset($this->articlesSelected[$index]);
        $this->articlesSelected = array_values($this->articlesSelected);
        $this->calculateTotals();
    }

    public function updateTotal($index)
    {
        if (!isset($this->articlesSelected[$index])) {
            return;
        }

        $selected = &$this->articlesSelected[$index];
        $selected['quantity'] = max(1, (int) $selected['quantity']);
        $selected['total']    = (float) $selected['price'] * (int) $selected['quantity'];

        $this->calculateTotals();
    }

    public function calculateTotals()
    {
        $this->granSubtotal = collect($this->articlesSelected)->sum('total');
        $this->granTax      = ($this->tax == 1) ? $this->granSubtotal * 0.18 : 0;
        $this->granTotal    = $this->granSubtotal + $this->granTax;
    }

    public function render()
    {
        return view('livewire.quotations.new-quotation');
    }
}
