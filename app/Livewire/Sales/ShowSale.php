<?php

namespace App\Livewire\Sales;

use App\Models\Article;
use App\Models\Client;
use App\Models\Contact;
use App\Models\PaymentMethod;
use App\Services\MigoApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
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

    public $clientSelected;

    public $webhook_imported;

    public $sectionClient = false;
    public $status;

    public $name;
    public $document_number;
    public $document_type;
    public $address;
    public $phone;
    public $email;
    public $token;

    public $departments;
    public $provinces = [];
    public $districts = [];
    public $departmentSelect;
    public $districtSelect;
    public $provinceSelect;

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

    private function throwError(string $message)
    {
        $this->dispatch('error', ['label' => $message]);
    }

    public function mount()
    {

        $this->token = env('MIGO_API_TOKEN');
        $sale = Sale::find($this->id);
        $this->departments = DB::table('departments')->get();

        $contacts = Contact::select('id','name')->get();
        $paymentMethods = PaymentMethod::select('id','name')->get();

        //Inicio Client
        $this->client = $sale->client_id;
        $this->clientSelected = $sale->client;
        //Fin Client

        $this->contact = $sale->contact_id;
        $this->paymentMethod = $sale->payment_method_id;;
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
        $this->status = $sale->status;
        $this->webhook_imported = $sale->webhook_imported;

        foreach ($sale->saleDetails as $detail) {
            $this->addToArticleSale($detail->id);
        }

    }

    public function updatedDepartmentSelect($value)
    {
        // Reiniciamos los selects dependientes
        $this->districtSelect = null;
        $this->provinceSelect = null;
        $this->districts = [];
        $this->provinces = DB::table('provinces')->where('department_id', $value)->get();

    }

    public function updatedProvinceSelect($value)
    {
        $this->districts = DB::table('districts')->where('province_id', $value)->get();
    }

    public function saveClient(){
        $this->validate([
            'name' => 'required|string|min:3',
            'document_number' => 'required|numeric|min:3|unique:clients,document_number',
            'document_type' => 'required|string|min:2',
            'address' => 'nullable|string|min:3',
            'phone' => 'nullable|numeric|min:3',
            'email' => 'nullable|email',
            'departmentSelect' => 'required|numeric|min:1',
            'provinceSelect' => 'required|numeric|min:1',
            'districtSelect' => 'required|numeric|min:1'
        ],[],[
            'name' => 'nombre',
            'document_number' => 'documento',
            'document_type' => 'tipo de documento',
            'address' => 'dirección',
            'phone' => 'teléfono',
            'email' => 'correo',
            'departmentSelect' => 'departamento',
            'provinceSelect' => 'provincia',
            'districtSelect' => 'distrito'
        ]);

        Client::create([
            'name' => $this->name,
            'document_number' => $this->document_number,
            'document_type' => $this->document_type,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'department_id' => $this->departmentSelect,
            'province_id' => $this->provinceSelect,
            'district_id' => $this->districtSelect
        ]);

        $this->reset(['name','document_number','document_type','address','phone','email','departmentSelect','provinceSelect','districtSelect']);
        $this->render();
        $this->sectionClient = false;
        $this->dispatch('successNotRoute', ['label' => 'Se agrego el cliente con éxito.']);

    }

    public function searchDocument(MigoApiService $api)
    {

        $this->validate([
            'document_number' => 'required|numeric|min:3|unique:clients,document_number',
        ]);

        if (!isset($this->docConfig[$this->document_type])) {
            return $this->throwError('Selecciona un tipo de documento válido.');
        }

        if ($this->document_type === 'CE') {
            return $this->throwError('Servicio no disponible para este tipo de documento.');
        }

        $config = $this->docConfig[$this->document_type];

        if (strlen($this->document_number) !== $config['size']) {
            return $this->throwError(
                "El {$this->document_type} debe tener exactamente {$config['size']} dígitos."
            );
        }

        // 5) Preparar payload y llamar al servicio
        $payload = [
            $config['field'] => $this->document_number,
            'token'          => $this->token,
        ];

        $response = $api->post(
            strtolower($this->document_type),
            $payload
        );

        if(!isset($response['success']) || $response['success'] === false ){
            return $this->throwError("Recurso no encontrado");
        }

        $this->name = $response[$config['responseKey']] ?? '';

    }

    public function rules()
    {
        return [
            'client'            => 'required',
            'date'              => 'required|date_format:Y-m-d',
            'contact'           => 'required',
            'paymentMethod'     => 'required',
            'delivery_fee'      => 'numeric|nullable',
            'articlesSelected'  => 'required|array|min:1'
        ];
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
        return Article::query()
            ->active()
            ->where(fn($q) => $q
                ->where('title', 'like', '%'.$query.'%')
                ->orWhereHas('brand', fn($qq) =>
                    $qq->where('name', 'like', '%'.$query.'%')
                )
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

    public function save()
    {
        $this->validate();

        try {
            DB::transaction(function () {
                // Bloquea la cabecera de la venta
                /** @var \App\Models\Sale $sale */
                $sale = \App\Models\Sale::lockForUpdate()->findOrFail($this->id);

                // Si la venta está cancelada (status = 0) NO debe afectar stock ni kardex
                $affectsStock = ($sale->status !== \App\Models\Sale::SALE_CANCELED);

                // Detalles actuales, bloqueados
                $currentDetails = $sale->saleDetails()->lockForUpdate()->get();

                // Conjunto de artículos a bloquear (anteriores + nuevos) si afecta stock
                $articles = collect();
                if ($affectsStock) {
                    $articleIds = $currentDetails->pluck('article_id')
                        ->merge(collect($this->articlesSelected)->pluck('id'))
                        ->unique()
                        ->values();

                    $articles = \App\Models\Article::whereIn('id', $articleIds)
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');
                }

                // 1) Revertir stock de los detalles actuales (solo si afecta stock)
                if ($affectsStock) {
                    foreach ($currentDetails as $d) {
                        $articles[$d->article_id]->increment('stock', (int)$d->quantity);
                    }
                }

                // 2) Eliminar detalles actuales
                //    (si usas SoftDeletes en sale_details y NO quieres que salgan en el Kardex,
                //     recuerda filtrar deleted_at IS NULL en el componente del Kardex)
                $sale->saleDetails()->delete();

                // 3) Validar stock para los nuevos (solo si afecta stock)
                if ($affectsStock) {
                    foreach ($this->articlesSelected as $row) {
                        $art = $articles[$row['id']] ?? null;
                        if (!$art) {
                            throw new \RuntimeException("Artículo no encontrado: {$row['id']}.");
                        }
                        if ($art->stock < (int)$row['quantity']) {
                            throw new \RuntimeException("Stock insuficiente para {$art->title} (disp. {$art->stock}, sol. {$row['quantity']}).");
                        }
                    }
                }

                // 4) Crear nuevos detalles + descontar stock si corresponde
                foreach ($this->articlesSelected as $row) {
                    $detail = $sale->saleDetails()->create([
                        'price'       => $row['price'],
                        'quantity'    => (int)$row['quantity'],
                        'tax'         => ($this->tax == 1) ? $row['total'] * 0.18 : 0,
                        'total'       => ($this->tax == 1) ? $row['total'] * 1.18 : $row['total'],
                        'article_id'  => $row['id'],
                        'category_id' => $row['category'],
                        'brand_id'    => $row['brand'],
                        'subtotal'    => $row['total'],
                    ]);

                    if ($affectsStock) {
                        $articles[$row['id']]->decrement('stock', (int)$row['quantity']);
                    }
                }

                // 5) Actualizar cabecera
                $sale->update([
                    'client_id'        => $this->client,
                    'subtotal'         => $this->granSubtotal,
                    'tax'              => $this->granTax,
                    'total'            => $this->granTotal,
                    'contact_id'       => $this->contact,
                    'webhook_imported' => null,
                ]);
            });

            $this->dispatch('success', [
                'label' => 'La venta fue editada con éxito.',
                'btn'   => 'Ir a ventas',
                'route' => route('sales.index'),
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('error', ['label' => 'No se pudo editar la venta: ' . $e->getMessage()]);
            report($e);
        }
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
                    $this->articlesSelected[$index]['total'] = $this->articlesSelected[$index]['quantity'] * $this->articlesSelected[$index]['price'];
                } else {
                    $this->dispatch('error', ['label' => '4No hay stock disponible para ' . $article->title]);
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
                    $this->dispatch('error', ['label' => '1No hay stock disponible para ' . $article->title]);
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
                    $this->articlesSelected[$index]['total'] = $this->articlesSelected[$index]['quantity'] * $article->purchase_price;
                } else {
                    $this->dispatch('error', ['label' => '2No hay stock disponible para ' . $article->title]);
                }

            } else {

                    $this->articlesSelected[] = [
                        'id' => $article->article->id,
                        'category' => $article->article->category_id,
                        'brand' => $article->brand_id,
                        'title' => $article->article->title,
                        'price' => $article->price,
                        'quantity' => $article->quantity,
                        'total' => $article->price * $article->quantity,
                    ];
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
