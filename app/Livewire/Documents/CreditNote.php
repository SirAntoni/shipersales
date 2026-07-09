<?php

namespace App\Livewire\Documents;

use App\Models\Article;
use App\Models\Client;
use App\Models\Document;
use App\Services\MigoApiService;
use App\Services\SunatService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Luecano\NumeroALetras\NumeroALetras;

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
        $document = Document::findOrFail($this->id);
        $this->token = env('MIGO_API_TOKEN');
        $this->serie = ($document->document_type == '1') ? "FC01" : "BC01";
        $this->correlative = $this->nextCorrelativeForSerie($this->serie);


        //Inicio Client
        $this->client = $document->client_id;
        $this->clientSelected = $document->client;
        //Fin Client

        $this->affected_document = $document->serie . '-' . $document->correlative;

        // Fecha de emisión de la nota de crédito (no la del documento afectado)
        $this->date = Carbon::now()->format('Y-m-d');
        $this->defaultClient = $document->client->id;
        $this->documentType = $document->document_type;

        // Precarga con los items del documento afectado (DocumentDetail, no SaleDetail)
        foreach ($document->documentDetails()->with('article')->get() as $detail) {
            $this->articlesSelected[] = [
                'id'       => $detail->article_id,
                'category' => $detail->category_id,
                'sku'      => $detail->article->sku ?? '',
                'brand'    => $detail->brand_id,
                'title'    => $detail->article->title ?? '',
                'price'    => $detail->price,
                'quantity' => $detail->quantity,
                'total'    => (float) $detail->price * (int) $detail->quantity,
            ];
        }

        $this->calculateTotals();
    }

    /** Siguiente correlativo para la serie de nota de crédito (FC01/BC01). */
    private function nextCorrelativeForSerie(string $serie): int
    {
        $max = Document::where('serie', $serie)
            ->selectRaw('MAX(CAST(correlative AS UNSIGNED)) as max_correlative')
            ->value('max_correlative');

        return ((int) $max) + 1;
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
        $maxDate = Carbon::now()->format('Y-m-d');

        // before_or_equal: SUNAT rechaza fechas futuras (error 2329)
        return [
            'date'             => ['required', 'date', "after_or_equal:{$minDate}", "before_or_equal:{$maxDate}"],
            'documentType'     => ['required'],
            'articlesSelected' => ['required', 'array', 'min:1'],
            'client'           => ['required'],
        ];
    }

    public function save(MigoApiService $api)
    {

        Log::info("inicio de emisión de nota de crédito");

        $this->validate();

        // Normalizar a ISO: si llegó un string tipeado (ej. "08/07/2026"), PHP y
        // MySQL lo interpretan distinto (mm/dd vs basura); así BD y XML coinciden
        $this->date = Carbon::parse($this->date)->format('Y-m-d');

        // El documento afectado debe estar vigente y aceptado por SUNAT
        $affectedDoc = Document::find($this->id);

        if (!$affectedDoc) {
            $this->dispatch('error', ['label' => 'No se encontró el documento afectado.']);
            return;
        }

        if ($affectedDoc->status == 'nota_credito') {
            $this->dispatch('error', ['label' => "El comprobante {$affectedDoc->serie}-{$affectedDoc->correlative} ya tiene una nota de crédito emitida."]);
            return;
        }

        if ($affectedDoc->status == 'anulado') {
            $this->dispatch('error', ['label' => "El comprobante {$affectedDoc->serie}-{$affectedDoc->correlative} está anulado: no puede recibir una nota de crédito."]);
            return;
        }

        if ($affectedDoc->status_sunat != 'aceptado') {
            $this->dispatch('error', ['label' => "El comprobante {$affectedDoc->serie}-{$affectedDoc->correlative} no está aceptado por SUNAT (estado: {$affectedDoc->status_sunat})."]);
            return;
        }

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

        // Solo DNI/RUC se validan contra MIGO (igual que NewDocument);
        // CE, pasaporte, etc. usan el nombre registrado del cliente.
        // Si MIGO falla, se usa el nombre registrado en lugar de abortar.
        $responseMigoApi = null;
        $config = $this->docConfig[$textoDoc] ?? null;

        if ($config !== null) {
            try {
                $responseMigoApi = $api->post(strtolower($client->document_type), [
                    $config['field'] => $client->document_number,
                    'token'          => $this->token,
                ]);
            } catch (\Throwable $e) {
                Log::warning("MIGO no disponible al emitir nota de crédito, se usa el nombre registrado del cliente: {$e->getMessage()}");
            }
        }

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
            // Datos de la nota de credito (Note)
            "tipDocAfectado" => ($this->documentType == '1') ? '01' : '03',
            "numDocAfectado" => $this->affected_document,
            "codMotivo" => '01', // Catalog. 09: Anulación de la operación
            "desMotivo" => 'Anulación de la operación',
            "subtotal" => $this->granSubtotal,
            "igv"=> $this->granTax,
            "total" => $this->granTotal,
            "client" => [
                "tipoDoc" => $tipoDoc,
                "numDoc" => $client->document_number,
                "name" => ($responseMigoApi === null)
                    ? $client->name
                    : ($responseMigoApi[$config['responseKey']] ?? $client->name),
                "address" => $client->address,
            ],
            "items"=> $items,
            "legend"=> $this->legends,
        ];

        try {
            $sunat = new SunatService();

            $see = $sunat->getSee();

            $invoice = $sunat->getNote($data);

            Log::info("nota de crédito data: " . json_encode($data));

            $result = $see->send($invoice);

            file_put_contents(storage_path('/xml_path/'.$invoice->getName().'.xml'),$see->getFactory()->getLastXml());

            $sunatResponse = $sunat->sunatResponse($invoice,$result);

            // Correlativo ya usado en SUNAT: reintentar una vez con el siguiente
            if ($sunatResponse['status'] != 1 && (string)($sunatResponse['code'] ?? '') === '1032') {
                Log::warning("SUNAT 1032 en nota de crédito: correlativo ya existe. Reintentando.");

                $this->correlative = $this->nextCorrelativeForSerie($this->serie);
                $data['correlative'] = $this->correlative;

                $invoice = $sunat->getNote($data);
                $result  = $see->send($invoice);

                file_put_contents(storage_path('/xml_path/'.$invoice->getName().'.xml'), $see->getFactory()->getLastXml());

                $sunatResponse = $sunat->sunatResponse($invoice, $result);
            }
        } catch (\Throwable $e) {
            Log::error('Error inesperado emitiendo nota de crédito', [
                'affected_document_id' => $this->id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            $this->dispatch('error', ['label' => 'Ocurrió un error inesperado al emitir la nota de crédito. Inténtelo nuevamente; si persiste, revise los logs.']);
            return;
        }

        if($sunatResponse['status'] != 1){
            $rawNote = (string) ($sunatResponse['notes'][0] ?? '');
            $rawNote = preg_replace('/ - Detalle:.*$/s', '', $rawNote);
            $label   = $rawNote !== ''
                ? "SUNAT rechazó la nota de crédito: {$rawNote}"
                : 'No se puede emitir la nota de crédito en estos momentos por fallos con SUNAT. Inténtelo más tarde.';

            $this->dispatch('error', ['label' => $label]);
            return;
        }

        // La NC ya fue ACEPTADA por SUNAT: registrar primero en BD y recién
        // después generar el PDF, para no perder una emisión aceptada.
        try {
            $sale = \App\Models\Sale::find($affectedDoc->sale_id);

            // Si la venta ya fue anulada (anulación de venta o devolución confirmada),
            // el stock ya se repuso por esa vía: la NC no debe reponerlo de nuevo.
            $stockYaRepuesto = $sale && (int) $sale->status === \App\Models\Sale::SALE_CANCELED;

            $document = Document::create([
                'status' => "enviado",
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
                'pdf_path' => null,
                'status_sunat'=> "aceptado",
                'notes'=> $sunatResponse['notes'],
                'sale_id' => $affectedDoc->sale_id,
                'affected_document_id' => $affectedDoc->id,
                'stock_restored' => !$stockYaRepuesto,
                'client_id' => $this->client,
                'user_id' => auth()->id() ?? $affectedDoc->user_id
            ]);

            foreach ($this->articlesSelected as $article) {
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

                // La nota de crédito devuelve los productos al inventario
                if (!$stockYaRepuesto) {
                    Article::find($article['id'])?->increment('stock', (int) $article['quantity']);
                }
            }

            // Marcar el documento afectado para bloquear nueva NC o anulación sobre él
            $affectedDoc->update(['status' => 'nota_credito']);

            // La NC revierte la operación completa: cierra la venta como anulada
            // (venga de una devolución o de una venta aún activa).
            if ($sale && (int) $sale->status !== \App\Models\Sale::SALE_CANCELED) {
                $motivo = ((int) $sale->status === \App\Models\Sale::SALE_RETURN)
                    ? "Devolución con nota de crédito {$this->serie}-{$this->correlative}"
                    : "Nota de crédito {$this->serie}-{$this->correlative}";

                $sale->update([
                    'status' => \App\Models\Sale::SALE_CANCELED,
                    'deletion_date' => now(),
                    'deletion_reason' => $motivo,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Nota de crédito ACEPTADA por SUNAT pero falló el registro en BD', [
                'comprobante' => "{$this->serie}-{$this->correlative}",
                'affected_document_id' => $this->id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            $this->dispatch('error', ['label' => "La nota de crédito {$this->serie}-{$this->correlative} fue ACEPTADA por SUNAT pero ocurrió un error interno al registrarla. NO la vuelva a emitir: contacte al administrador."]);
            return;
        }

        try {
            $document->update(['pdf_path' => $sunat->generatePDF($invoice, 'invoice', [
                'affected' => $this->affected_document,
                'reason'   => 'Anulación de la operación',
            ])]);
        } catch (\Throwable $e) {
            // El PDF puede regenerarse después; no invalida la emisión
            Log::error("Nota de crédito {$this->serie}-{$this->correlative} aceptada, pero falló la generación del PDF: {$e->getMessage()}");
        }

        $label = $stockYaRepuesto
            ? "La nota de crédito {$this->serie}-{$this->correlative} fue emitida con éxito. El stock no se modificó porque ya había sido devuelto al anular la venta."
            : "La nota de crédito {$this->serie}-{$this->correlative} fue emitida con éxito y el stock fue repuesto.";

        $this->dispatch('success', ['label' => $label, 'btn' => 'Ir a documentos', 'route' => route('documents.index')]);

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

            // Una nota de crédito devuelve productos ya vendidos:
            // el stock actual no limita qué puede incluirse.
            if ($index !== false) {
                $this->articlesSelected[$index]['quantity']++;
                $this->articlesSelected[$index]['total'] = $this->articlesSelected[$index]['quantity'] * $article->sale_price;
            } else {
                $this->articlesSelected[] = [
                    'id' => $article->id,
                    'category' => $article->category_id,
                    'sku' => $article->sku,
                    'brand' => $article->brand_id,
                    'title' => $article->title,
                    'price' => $article->sale_price,
                    'quantity' => 1,
                    'total' => $article->sale_price
                ];
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

        $selected['total'] = (float)$selected['price'] * (int)$selected['quantity'];
        $this->calculateTotals();
    }

    public function render()
    {
        return view('livewire.documents.credit-note');
    }
}
