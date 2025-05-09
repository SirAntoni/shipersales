<?php

namespace App\Livewire\Sales;

use App\Models\Article;
use App\Models\Client;
use App\Models\Contact;
use App\Models\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use App\Models\Sale;
use App\Models\SaleDetail;

class ShowSale extends Component
{
    const SALE_CANCELED = 0;
    const SALE_PENDING = 1;
    const SALE_APPROVED = 2;
    const SALE_OBSERVATION = 3;

    public $id;
    public $clients;
    public $client;
    public $defaultClient;
    public $defaultPaymentMethod;
    public $defaultContact;
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
    public $articlesSelected = [];
    public $observation = "";

    public function mount()
    {

        $sale = Sale::find($this->id);

        $clients = Client::select('id','name','document_number')->whereNot('id',1)->orderBy('id','desc')->get();
        $articles = Article::select('id','title','stock','sale_price')->where('stock', '>', 0)
            ->whereNot('id', 1)
            ->OrderBy('id', 'desc')
            ->get();
        $contacts = Contact::select('id','name')->get();
        $paymentMethods = PaymentMethod::select('id','name')->get();
        $this->clients = $clients;
        $this->articles = $articles;
        $this->contacts = $contacts;
        $this->paymentMethods = $paymentMethods;
        $this->date = $sale->date;
        $this->defaultClient = $sale->client->id;
        $this->defaultPaymentMethod = $sale->paymentMethod->id;
        $this->defaultContact = $sale->contact->id;
        $this->number = $sale->number;
        $this->delivery_fee = $sale->delivery_fee;
        $this->granSubtotal = $sale->granSubtotal;
        $this->tax = ($sale->tax > 0) ? 1:0;
        $this->observation = $sale->observations;

        foreach ($sale->saleDetails as $detail) {
            $this->addToArticleSale($detail->id);
        }

    }

    protected $rules = [
        'client' => 'required',
        'date' => 'required|date_format:Y-m-d',
        'contact' => 'required',
        'paymentMethod' => 'required',
        'delivery_fee' => 'numeric|nullable',
        'articlesSelected' => 'required|array|min:1'
    ];

    public function save()
    {
        $this->validate();


        $sale = Sale::find($this->id);

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

            SaleDetail::where('sale_id', $sale->id)->delete();

        });

        $sale->update([
            'number' => $this->number,
            'date' => $this->date,
            'subtotal' => $this->granSubtotal,
            'tax' => $this->granTax,
            'total' => $this->granTotal,
            'delivery' => empty($this->delivery_fee) ? 0 : 1,
            'delivery_fee' => $this->delivery_fee ?? 0,
            'client_id' => $this->client,
            'contact_id' => $this->contact,
            'payment_method_id' => $this->paymentMethod
        ]);

        foreach ($this->articlesSelected as $article) {

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
        $this->dispatch('success', ['label' => 'La venta fue editada con éxito.', 'btn' => 'Ir a ventas', 'route' => route('sales.index')]);
    }

    public function saveObservation(){
        $this->validate([
            'observation' => 'required|string',
        ]);

        $sale = Sale::find($this->id);
        $sale->update([
            'observations' => $this->observation,
            'status' => self::SALE_OBSERVATION
        ]);

        $this->dispatch('notification');

    }

    public function deleteObservation(){

        $sale = Sale::find($this->id);
        $sale->update([
            'observations' => "",
            'status' => self::SALE_PENDING
        ]);

        $this->observation = null;

        $this->dispatch('notification');

    }

    protected $messages = [
        'articlesSelected.required' => 'Debe seleccionar al menos 1 artículo'
    ];

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

        if ($article) {

            $index = collect($this->articlesSelected)->search(function ($item) use ($article) {
                return $item['id'] == $article->id;
            });

            if ($index !== false) {

                if ($this->articlesSelected[$index]['quantity'] < $article->stock) {
                    $this->articlesSelected[$index]['quantity']++;
                    $this->articlesSelected[$index]['total'] = $this->articlesSelected[$index]['quantity'] * $article->purchase_price;
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

    public function addToArticleSale($id)
    {

        $article = SaleDetail::find($id);


        if ($article) {

            $index = collect($this->articlesSelected)->search(function ($item) use ($article) {
                return $item['id'] == $article->id;
            });

            if ($index !== false) {

                if ($this->articlesSelected[$index]['quantity'] < $article->stock) {
                    $this->articlesSelected[$index]['quantity']++;
                    $this->articlesSelected[$index]['total'] = $this->articlesSelected[$index]['quantity'] * $article->purchase_price;
                } else {
                    $this->dispatch('error', ['label' => 'No hay stock disponible para ' . $article->title]);
                }

            } else {

                if ($article->article->stock > 0) {

                    $this->articlesSelected[] = [
                        'id' => $article->article->id,
                        'category' => $article->article->category_id,
                        'brand' => $article->brand_id,
                        'title' => $article->article->title,
                        'price' => $article->price,
                        'quantity' => $article->quantity,
                        'total' => $article->price * $article->quantity,
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
        return view('livewire.sales.show-sale');
    }
}
