<?php

namespace App\Livewire\Documents;

use App\Models\Article;
use App\Models\Client;
use App\Models\Document;
use App\Services\MigoApiService;
use App\Services\SunatService;
use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use App\Models\Voucher;
use Luecano\NumeroALetras\NumeroALetras;
use App\Models\Setting;

class CreditNote extends Component
{
    public $id;
    public $clients;
    public $client;
    public $defaultClient;
    public $articles;
    public $number;
    public $date;
    public $granSubtotal;
    public $granTax;
    public $granTotal;
    public $dateSelected;
    public $articleSelected;
    public $articlesSelected = [];

    public $clientSelected;


    public $serie;
    public $correlative;
    public $documentType;

    public $legends;
    public $token;

    public $affected_document;

    protected array $docConfig = [
        'DNI' => [
            'size'        => 8,
            'field'       => 'dni',
            'responseKey' => 'nombre',
        ],
        'RUC' => [
            'size'        => 11,
            'field'       => 'ruc',
            'responseKey' => 'nombre_o_razon_social',
        ],
    ];

    public function mount(){
        $document = Document::find($this->id);
        $this->token = env('MIGO_API_TOKEN');
        $this->serie = ($document->document_type == '1') ? "FC01" : "BC01";
        $this->correlative = $document->correlative;


        //Inicio Client
        $this->client = $document->client_id;
        $this->clientSelected = $document->client;
        //Fin Client

        $this->affected_document = $document->serie . '-' . $document->correlative;

        $this->date = $document->date;
        $this->defaultClient = $document->client->id;
        $this->granSubtotal = $document->total;
        $this->documentType = $document->document_type;


        foreach ($document->documentDetails as $detail) {
            $this->addToArticleSale($detail->id);
        }
    }

    protected $rules = [
        'date'=>'required',
        'documentType' => 'required',
        'articlesSelected' => 'required|array|min:1',
        'client' => 'required',
    ];

    public function rules()
    {
        // Calculamos la fecha mínima permitida (hoy menos 5 días).
        $minDate = Carbon::now()->subDays(5)->format('Y-m-d');

        return [
            'date'             => ['required', 'date', "after_or_equal:{$minDate}"],
            'documentType'     => ['required'],
            'articlesSelected' => ['required', 'array', 'min:1'],
            'client'           => ['required'],
        ];
    }

    public function save(MigoApiService $api)
    {

        Log::info("inicio de emisión de comprobante");



        $this->validate();

        $items = collect($this->articlesSelected);

        $tiposIdentidad = [
            '0' => 'NO DOMICILIADO',
            '1' => 'DNI',
            '4' => 'CE',
            '6' => 'RUC',
            '7' => 'PASAPORTE',
        ];

        $inverseTipos = array_flip($tiposIdentidad);

        $client = Client::find($this->client);



        $textoDoc = $client->document_type;
        $tipoDoc  = $inverseTipos[$textoDoc] ?? null;

        $config = $this->docConfig[$textoDoc];

        $payload = [
            $config['field'] => $client->document_number,
            'token'          => $this->token,
        ];

        $responseMigoApi = $api->post(
            strtolower($client->document_type),
            $payload
        );

        if ($this->documentType == '1'
            && $client->document_type != 'RUC') {
            $this->dispatch('error', ['label' => 'No puede emitir una factura a un cliente con un documento diferente a RUC.']);
            return;
        }

        $data = [
            "serie" => $this->serie,
            "correlative" => $this->correlative,
            "date" => $this->date ?? "2005-01-01",
            "tipoDoc" => ($this->documentType == '1') ? '01' : '03',
            "subtotal" => $this->granSubtotal,
            "igv"=> $this->granTax,
            "total" => $this->granTotal,
            "client" => [
                "tipoDoc" => $tipoDoc,
                "numDoc" => $client->document_number,
                "name" => $responseMigoApi[$config['responseKey']] ?? '',
                "address" => $client->address,
            ],
            "items"=> $items,
            "legend"=> $this->legends,
        ];

        $sunat = new SunatService();

        $see = $sunat->getSee();

        $invoice = $sunat->getInvoice($data);

        Log::info("invoice: " . json_encode($data));;

        $result = $see->send($invoice);

        file_put_contents(storage_path('/xml_path/'.$invoice->getName().'.xml'),$see->getFactory()->getLastXml());

        $sunatResponse = $sunat->sunatResponse($invoice,$result);

        $pdf_path = "";
        if($sunatResponse['status'] == 1){
            $pdf_path = $sunat->generatePDF($invoice, 'invoice', [
                'affected' => $this->affected_document,
                'reason'   => 'Anulación de la operación',
            ]);
        }

        if($sunatResponse['status'] != 1){
            $this->dispatch('error', ['label' => 'No se puede emitir un comprobante en estos momentos por fallos con sunat, Intentarlo mas tarde.']);
            return;
        }

        $document = Document::create([
            'estado' => "enviado",
            'document_type' => $this->documentType,
            'serie' => $this->serie,
            'correlative' => $this->correlative,
            'date' => $this->date,
            'currency' => 'PEN',
            'payment_method' => 'CONTADO',
            'subtotal' => $this->granSubtotal,
            'tax' => $this->granTax,
            'total' => $this->granTotal,
            'xml_path' => '/xml_path/'.$invoice->getName().'.xml',
            'cdr_path' => $sunatResponse['cdr'] ?? '',
            'pdf_path' => $pdf_path,
            'status_sunat'=> ($sunatResponse['status'] == "1") ? "aceptado" : "rechazado",
            'notes'=> $sunatResponse['notes'],
            'sale_id' => $this->id,
            'client_id' => $this->client,
            'user_id' => auth()->id()
        ]);

        foreach ($this->articlesSelected as $article) {
            $art = Article::find($article['id']);
            $document->documentDetails()->create([
                'price' => $article['price'],
                'quantity' => $article['quantity'],
                'tax' => $article['total'] * 0.18,
                'total' => $article['total'] + ($article['total'] * 0.18),
                'article_id' => $article['id'],
                'category_id' => $article['category'],
                'brand_id' => $article['brand'],
                'subtotal' => $article['total'],
            ]);

        }
        $this->dispatch('success', ['label' => 'La documento fue registrada con éxito.', 'btn' => 'Ir a documentos', 'route' => route('documents.index')]);

    }

    public function searchClients($query)
    {
        return Client::query()
            ->where('name', 'like', '%'.$query.'%')
            ->limit(10)
            ->get(['id', 'name'])
            ->map(fn($c) => [
                'value' => $c->id,
                'text'  => $c->name,
            ])
            ->toArray();
    }

    public function updatedDocumentType(){
        $this->serie = Voucher::serie($this->documentType);
        $this->correlative = Voucher::nextCorrelativeById($this->documentType);
    }

    public function searchArticles($query)
    {
        $qb = Article::query()
            ->where('title', 'like', '%'.$query.'%')
            ->limit(10)
            ->get(['id', 'title', 'stock', 'sale_price', 'purchase_price']);

        $showPurchase = auth()->id() === 1;

        return $qb->map(function($c) use ($showPurchase) {
            $text = "{$c->title} | Stock: {$c->stock} | Precio Venta: S/.{$c->sale_price}";

            if ($showPurchase) {
                $text .= " | Precio Compra: $ {$c->purchase_price}";
            }

            return [
                'value' => $c->id,
                'text'  => $text,
            ];
        })->toArray();
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

        if ($article) {

            $index = collect($this->articlesSelected)->search(function ($item) use ($article) {
                return $item['id'] == $article->id;
            });

            if ($index !== false) {

                if ($this->articlesSelected[$index]['quantity'] < $article->stock) {
                    $this->articlesSelected[$index]['quantity']++;
                    $this->articlesSelected[$index]['total'] = $this->articlesSelected[$index]['quantity'] * $article->sale_price;
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

        $article = SaleDetail::with('article')->find($id);


        if ($article) {

            $index = collect($this->articlesSelected)->search(function ($item) use ($article) {
                return $item['id'] == $article->id;
            });

            if ($index !== false) {

                if ($this->articlesSelected[$index]['quantity'] < $article->stock) {
                    $this->articlesSelected[$index]['quantity']++;
                    $this->articlesSelected[$index]['total'] = $this->articlesSelected[$index]['quantity'] * $article->sale_price;
                } else {
                    $this->dispatch('error', ['label' => 'No hay stock disponible para ' . $article->title]);
                }

            } else {

                if ($article->article->stock > 0) {

                    $this->articlesSelected[] = [
                        'id' => $article->article->id,
                        'category' => $article->article->category_id,
                        'sku' => $article->article->sku,
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

    public function calculateTotals()
    {
        $this->granSubtotal = collect($this->articlesSelected)->sum('total');
        $this->granTax = $this->granSubtotal * 0.18;
        $this->granTotal = $this->granSubtotal + $this->granTax;

        $formatter = new NumeroALetras();

        $this->legends = $formatter->toInvoice($this->granTotal, 2, 'SOLES');
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

    public function render()
    {
        return view('livewire.documents.credit-note');
    }
}
