<?php

namespace App\Livewire\Sales;

use App\Models\Article;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;
use App\Models\Sale;
use App\Models\Client;
use App\Models\Contact;
use App\Models\PaymentMethod;
use DB;

class NewSale extends Component
{
    public $clients;
    public $client;
    public $articles;
    public $contacts;
    public $tax;
    public $number;
    public $contact;
    public $paymentMethod;
    public $paymentMethods;
    public $delivery_fee;
    public $date;
    public $granSubtotal;
    public $granTax;
    public $granTotal;
    public $dateSelected;
    public $articleSelected;
    public array $articlesSelected = [];

    public $userId;

    public function generarCodigo()
    {
        $fecha = Carbon::now();
        $yy = $fecha->format('y');
        $mm = $fecha->format('m');
        $dd = $fecha->format('d');
        $ss = $fecha->format('s');
        $randomNum = random_int(1000, 9999);

        return "{$yy}{$mm}{$dd}{$ss}{$randomNum}";
    }

    public function updatedNumber($value)
    {
        $this->number = trim($value);
    }

    public function rules()
    {
        return [
            'client' => 'required',
            'date' => 'required|date_format:Y-m-d',
            'contact' => 'required',
            'paymentMethod' => 'required',
            'delivery_fee' => 'numeric|nullable',
            'articlesSelected' => 'required|array|min:1',
            'number'            => [
                'nullable',
                Rule::unique('sales', 'number')
                    ->where(function ($query) {
                        return $query->where('status', '<>', 0);
                    }),
            ],
        ];
    }

    public function save()
    {

        $this->validate();
        $sale = Sale::create([
            'number' => empty($this->number) ? $this->generarCodigo() : $this->number,
            'date' => $this->date,
            'subtotal' => $this->granSubtotal,
            'tax' => $this->granTax,
            'total' => $this->granTotal,
            'delivery' => empty($this->delivery_fee) ? 0 : 1,
            'delivery_fee' => $this->delivery_fee ?? 0,
            'client_id' => $this->client,
            'user_id' => auth()->id(),
            'contact_id' => $this->contact,
            'payment_method_id' => $this->paymentMethod,
            'status' => Sale::SALE_PENDING,
        ]);

        foreach ($this->articlesSelected as $article) {
            $art = Article::find($article['id']);
            $sale->saleDetails()->create([
                'price' => $article['price'],
                'quantity' => $article['quantity'],
                'tax' => ($this->tax == 1) ? $article['total'] * 0.18 : 0,
                'total' => ($this->tax == 1) ? $article['total'] + ($article['total'] * 0.18) : $article['total'],
                'article_id' => $article['id'],
                'category_id' => $article['category'],
                'brand_id' => $article['brand'],
                'subtotal' => $article['total'],
            ]);

            Article::find($article['id'])->decrement('stock', $article['quantity']);

        }

        $this->reset(['client','date','contact','paymentMethod','articlesSelected','granTotal','granTax','granSubtotal','number','delivery_fee']);;
        $this->dispatch('success_sale', ['label' => 'La venta fue registrada con éxito.']);

    }

    protected $messages = [
        'articlesSelected.required' => 'Debe seleccionar al menos 1 artículo'
    ];

    public function mount()
    {
        $contacts = Contact::select('id','name')->get();
        $paymentMethods = PaymentMethod::select('id','name')->get();
        $this->contacts = $contacts;
        $this->paymentMethods = $paymentMethods;
        $this->date = Carbon::now()->format('Y-m-d');
        $this->userId = auth()->id() ?? 0;
    }

    public function updatedContact(){
        if($this->contact == 2 || $this->contact == 7 || $this->contact == 8 || $this->contact == 9 || $this->contact == 10){
            $this->paymentMethod = 1;
        }elseif ($this->contact == 3){
            $this->paymentMethod = 4;
        }else{
            $this->paymentMethod = "";
        }
    }

    public function searchClients($query)
    {
        return Client::query()
            ->where('name', 'like', '%'.$query.'%')
            ->orWhere('document_number', 'like', '%'.$query.'%')
            ->limit(10)
            ->get(['id', 'name','document_number'])
            ->map(fn($c) => [
                'value' => $c->id,
                'text'  => $c->name . " - ".  $c->document_number,
            ])
            ->toArray();
    }

    public function searchArticles($query)
    {
        return Article::where('title', 'like', '%'.$query.'%')
            ->orWhereHas('brand', fn($q) =>
            $q->where('name', 'like', '%'.$query.'%')
            )
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'value' => $c->id,
                'text'  => trim(
                    $c->title
                    // si existe brand, lo pinto entre paréntesis
                    . ($c->brand?->name ? " ({$c->brand->name})" : '')
                    . " | stock: {$c->stock}"
                    . " | precio: S/. {$c->sale_price}"
                ),
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
        $article = DB::table('articles')->find($id);

        if ($article) {

            $index = collect($this->articlesSelected)->search(function ($item) use ($article) {
                return $item['id'] == $article->id;
            });

            if ($index !== false) {

                if ($this->articlesSelected[$index]['quantity'] < $article->stock) {
                    $this->articlesSelected[$index]['quantity']++;
                    $this->articlesSelected[$index]['total'] = $this->articlesSelected[$index]['quantity'] * $this->articlesSelected[$index]['price'];
                } else {
                    $this->dispatch('error', ['label' => 'No hay stock disponible para ' . $article->title]);
                }

            } else {

                if ($article->stock > 0) {
                    $this->articlesSelected[] = [
                        'id' => $article->id,
                        'category' => $article->category_id,
                        'brand' => $article->brand_id,
                        'title' => $article->title,
                        'price' => $article->sale_price,
                        'quantity' => 1,
                        'total' => $article->sale_price
                    ];

                } else {
                    $this->dispatch('error', ['label' => 'No hay stock disponible para ' . $article->title]);
                }
            }

            $this->calculateTotals();
        }
    }

    public function remove($index)
    {
        array_splice($this->articlesSelected, $index, 1);
        $this->calculateTotals();
    }

    public function updateTotal($index)
    {

        if (!isset($this->articlesSelected[$index])) {
            return;
        }

        $selected = &$this->articlesSelected[$index];

        $article = Article::find($selected['id']);
        if (!$article) {
            $this->dispatch('error', ['label' => 'Artículo no encontrado']);
            return;
        }

        if ($article->stock < $selected['quantity']) {
            $this->dispatch('error', ['label' => 'No hay stock disponible para ' . $article->title]);
            $selected['quantity'] = $article->stock;
        }

        $selected['total'] = (float)$selected['price'] * (int)$selected['quantity'];
        $this->calculateTotals();
    }

    public function calculateTotals()
    {
        $this->granSubtotal = collect($this->articlesSelected)->sum('total');
        if ($this->tax == 1) {
            $this->granTotal = $this->granSubtotal + ($this->granSubtotal * 0.18);
            $this->granTax = $this->granSubtotal * 0.18;
        } else {
            $this->granTotal = $this->granSubtotal;
            $this->granTax = 0;
        }
    }

    public function updateTax()
    {
        $this->calculateTotals();
    }

    public function render()
    {
        return view('livewire.sales.new-sale');
    }
}
