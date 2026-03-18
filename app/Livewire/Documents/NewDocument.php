<?php

namespace App\Livewire\Documents;

use App\Models\Article;
use App\Models\Client;
use App\Models\Document;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Voucher;
use App\Services\MigoApiService;
use App\Services\SunatService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Luecano\NumeroALetras\NumeroALetras;

class NewDocument extends Component
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

    protected $rules = [
        'date'             => 'required',
        'documentType'     => 'required',
        'articlesSelected' => 'required|array|min:1',
        'client'           => 'required',
    ];

    public function rules()
    {
        $minDate = Carbon::now()->subDays(5)->format('Y-m-d');

        return [
            'date'             => ['required', 'date', "after_or_equal:{$minDate}"],
            'documentType'     => ['required'],
            'articlesSelected' => ['required', 'array', 'min:1'],
            'client'           => ['required'],
        ];
    }

    public function mount()
    {
        $sale              = Sale::find($this->id);
        $this->token       = env('MIGO_API_TOKEN');
        $this->serie       = '-';
        $this->correlative = '-';

        // Cliente
        $this->client         = $sale->client_id;
        $this->clientSelected = $sale->client;

        $this->date          = $sale->date;
        $this->defaultClient = $sale->client->id;
        $this->number        = $sale->number;
        $this->granSubtotal  = $sale->granSubtotal;

        foreach ($sale->saleDetails as $detail) {
            $this->addToArticleSale($detail->id);
        }
    }

    /**
     * Guarda el detalle del documento (document_details) a partir de $this->articlesSelected.
     */
    protected function persistDocumentDetails(Document $document): void
    {
        foreach ($this->articlesSelected as $article) {
            $document->documentDetails()->create([
                'price'      => $article['price'],
                'quantity'   => $article['quantity'],
                'tax'        => $article['total'] * 0.18,
                'total'      => $article['total'] + ($article['total'] * 0.18),
                'article_id' => $article['id'],
                'category_id'=> $article['category'],
                'brand_id'   => $article['brand'],
            ]);
        }
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

        $responseMigoApi = null;

        Log::info("documento creado: " . $textoDoc);

        if ($textoDoc != 'CE') {
            $config = $this->docConfig[$textoDoc];

            $payload = [
                $config['field'] => $client->document_number,
                'token'          => $this->token,
            ];

            $responseMigoApi = $api->post(
                strtolower($client->document_type),
                $payload
            );
        }

        // Validación: factura solo a RUC
        if ($this->documentType == '1' && $client->document_type != 'RUC') {
            $this->dispatch('error', ['label' => 'No puede emitir una factura a un cliente con un documento diferente a RUC.']);
            return;
        }

        $data = [
            "serie"       => $this->serie,
            "correlative" => $this->correlative,
            "date"        => $this->date ?? "2005-01-01",
            "tipoDoc"     => ($this->documentType == '1') ? '01' : '03',
            "subtotal"    => $this->granSubtotal,
            "igv"         => $this->granTax,
            "total"       => $this->granTotal,
            "client"      => [
                "tipoDoc" => $tipoDoc,
                "numDoc"  => $client->document_number,
                "name"    => ($responseMigoApi === null)
                    ? $client->name
                    : ($responseMigoApi[$config['responseKey']] ?? ''),
                "address" => $client->address,
            ],
            "items"  => $items,
            "legend" => $this->legends,
        ];

        Log::info("data: " . json_encode($data));

        $sunat = new SunatService();

        $see     = $sunat->getSee();
        $invoice = $sunat->getInvoice($data);

        Log::info("invoice: " . json_encode($data));

        $result   = $see->send($invoice);
        $lastXml  = $see->getFactory()->getLastXml();
        $xmlPath  = '/xml_path/' . $invoice->getName() . '.xml';
        file_put_contents(storage_path($xmlPath), $lastXml);

        $sunatResponse = $sunat->sunatResponse($invoice, $result);

        // === 1) Manejo de error 1032: correlativo ya usado ===
        if ($sunatResponse['status'] != 1 && (string)($sunatResponse['code'] ?? '') === '1032') {
            Log::warning("SUNAT 1032: Correlativo ya existe. Reintentando con nuevo correlativo.");

            // Nuevo correlativo
            $this->correlative   = Voucher::nextCorrelativeById($this->documentType);
            $data['correlative'] = $this->correlative;

            // Regenerar invoice con nuevo correlativo
            $invoice = $sunat->getInvoice($data);
            $result  = $see->send($invoice);

            $lastXml = $see->getFactory()->getLastXml();
            $xmlPath = '/xml_path/' . $invoice->getName() . '.xml';
            file_put_contents(storage_path($xmlPath), $lastXml);

            $sunatResponse = $sunat->sunatResponse($invoice, $result);
        }

        // === 2) Si sigue fallando, controlar caso HTTP vs otros ===
        if ($sunatResponse['status'] != 1) {

            $code = (string)($sunatResponse['code'] ?? '');

            // Caso HTTP: se guarda el documento como pendiente
            // Caso HTTP: se guarda el documento como pendiente
            if (stripos($code, 'HTTP') !== false) {
                Log::warning("SUNAT HTTP ERROR ({$code}): se guardará el documento como PENDIENTE.");

                $document = Document::create([
                    'status'        => "pendiente",
                    'document_type' => $this->documentType,
                    'serie'         => $this->serie,
                    'correlative'   => $this->correlative,
                    'date'          => $this->date,
                    'currency'      => 'PEN',
                    'payment_method'=> 'CONTADO',
                    'subtotal'      => $this->granSubtotal,
                    'tax'           => $this->granTax,
                    'total'         => $this->granTotal,
                    'xml_path'      => $xmlPath,
                    'cdr_path'      => '',
                    'pdf_path'      => null,
                    'status_sunat'  => "pendiente",
                    'notes'         => $sunatResponse['notes'] ?? [],
                    'sale_id'       => $this->id,
                    'client_id'     => $this->client,
                    'user_id'       => auth()->id(),
                ]);

                // Guardar detalles también para pendientes
                $this->persistDocumentDetails($document);

                // Mismo flujo visual que el caso OK: mensaje + botón + redirect
                $this->dispatch('success', [
                    'label' => 'El comprobante se guardó como PENDIENTE por un error con SUNAT. Podrá reenviarse luego desde Documentos.',
                    'btn'   => 'Ir a documentos',
                    'route' => route('documents.index'),
                ]);

                return;
            }


            // Otros errores distintos de HTTP y no corregibles con 1032
            $mensajesSunat = [
                '1033' => 'Este comprobante ya fue registrado en SUNAT con datos distintos.',
                '1079' => 'Este comprobante es demasiado antiguo para enviarse individualmente a SUNAT.',
                '2800' => 'El número de RUC del cliente no está activo en SUNAT.',
                '2801' => 'El número de RUC del cliente no existe en SUNAT.',
                '2804' => 'La dirección del cliente no coincide con la registrada en SUNAT.',
                '3101' => 'El monto del IGV no corresponde al porcentaje declarado.',
                '3102' => 'El monto total del comprobante no es correcto.',
                '3106' => 'Uno o más productos no tienen la unidad de medida correcta.',
            ];

            $rawNote = $sunatResponse['notes'][0] ?? '';
            // Limpiar el "Detalle: ..." técnico que agrega SUNAT al final del mensaje
            $rawNote = preg_replace('/ - Detalle:.*$/s', '', $rawNote);

            if (isset($mensajesSunat[$code])) {
                $label = $mensajesSunat[$code] . " (Error SUNAT {$code})";
            } elseif (!empty($rawNote)) {
                $label = $rawNote;
            } else {
                $label = "No se pudo emitir el comprobante. Error SUNAT {$code}.";
            }

            $this->dispatch('error', ['label' => $label]);

            return;
        }

        // === 3) Caso OK (status == 1) ===
        $pdf_path = $sunat->generatePdf($invoice);

        $document = Document::create([
            'status'        => "enviado",
            'document_type' => $this->documentType,
            'serie'         => $this->serie,
            'correlative'   => $this->correlative,
            'date'          => $this->date,
            'currency'      => 'PEN',
            'payment_method'=> 'CONTADO',
            'subtotal'      => $this->granSubtotal,
            'tax'           => $this->granTax,
            'total'         => $this->granTotal,
            'xml_path'      => $xmlPath,
            'cdr_path'      => $sunatResponse['cdr'] ?? '',
            'pdf_path'      => $pdf_path,
            'status_sunat'  => $sunatResponse['status'] === 1 ? "aceptado" : "rechazado",
            'notes'         => $sunatResponse['notes'] ?? [],
            'sale_id'       => $this->id,
            'client_id'     => $this->client,
            'user_id'       => auth()->id(),
        ]);

        // Detalle del documento (enviados)
        $this->persistDocumentDetails($document);

        $this->dispatch('success', [
            'label' => 'La documento fue registrada con éxito.',
            'btn'   => 'Ir a documentos',
            'route' => route('documents.index'),
        ]);
    }

    public function searchClients($query)
    {
        return Client::query()
            ->where('name', 'like', '%'.$query.'%')
            ->orWhere('document_number', 'like', '%'.$query.'%')
            ->limit(10)
            ->get(['id', 'name', 'document_number'])
            ->map(fn ($c) => [
                'value' => $c->id,
                'text'  => $c->name . " - " . $c->document_number,
            ])
            ->toArray();
    }

    public function updatedDocumentType()
    {
        $this->serie       = Voucher::serie($this->documentType);
        $this->correlative = Voucher::nextCorrelativeById($this->documentType);
    }

    public function searchArticles($query)
    {
        $qb = Article::query()
            ->where('title', 'like', '%'.$query.'%')
            ->limit(10)
            ->get(['id', 'title', 'stock', 'sale_price', 'purchase_price']);

        $showPurchase = auth()->id() === 1;

        return $qb->map(function ($c) use ($showPurchase) {
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
                $this->articlesSelected[$index]['quantity']++;
                $this->articlesSelected[$index]['total'] =
                    $this->articlesSelected[$index]['quantity'] * $this->articlesSelected[$index]['price'];
            } else {
                $this->articlesSelected[] = [
                    'id'       => $article->id,
                    'category' => $article->category_id,
                    'brand'    => $article->brand_id,
                    'title'    => $article->title,
                    'price'    => $article->sale_price,
                    'quantity' => 1,
                    'total'    => $article->sale_price,
                ];
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
                $this->articlesSelected[$index]['quantity']++;
                $this->articlesSelected[$index]['total'] =
                    $this->articlesSelected[$index]['quantity'] * $this->articlesSelected[$index]['price'];
            } else {
                $this->articlesSelected[] = [
                    'id'       => $article->article->id,
                    'category' => $article->article->category_id,
                    'sku'      => $article->article->sku,
                    'brand'    => $article->brand_id,
                    'title'    => $article->article->title,
                    'price'    => $article->price,
                    'quantity' => $article->quantity,
                    'total'    => $article->price * $article->quantity,
                ];
            }

            $this->calculateTotals();
        }
    }

    public function calculateTotals()
    {
        $this->granSubtotal = collect($this->articlesSelected)->sum('total');
        $this->granTax      = $this->granSubtotal * 0.18;
        $this->granTotal    = $this->granSubtotal + $this->granTax;

        $formatter     = new NumeroALetras();
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

        $selected['total'] = (float)$selected['price'] * (int)$selected['quantity'];
        $this->calculateTotals();
    }

    public function render()
    {
        return view('livewire.documents.new-document');
    }
}
