<?php

namespace App\Livewire\Quotations;

use App\Models\Article;
use App\Models\Client;
use App\Models\Quotation;
use App\Services\MigoApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class NewQuotation extends Component
{
    public $client;
    public $date;
    public $validDays = 7;
    public $notes;
    public $tax = false;

    public $name;
    public $document_number;
    public $document_type;
    public $address;
    public $phone = '990062896';
    public $email = 'info@shipersales.pe';
    public $token;

    public $departments;
    public $provinces = [];
    public $districts = [];
    public $departmentSelect;
    public $districtSelect;
    public $provinceSelect;

    protected array $docConfig = [
        'DNI' => ['size' => 8, 'field' => 'dni', 'responseKey' => 'nombre'],
        'RUC' => ['size' => 11, 'field' => 'ruc', 'responseKey' => 'nombre_o_razon_social'],
    ];

    public $granSubtotal = 0;
    public $granTax = 0;
    public $granTotal = 0;

    public $articleSelected;
    public array $articlesSelected = [];

    /** Modo de agregado de artículos: search | manual */
    public string $articleMode = 'search';

    /** Campos del producto nuevo (manual) */
    public $manualTitle;
    public $manualDetail;
    public $manualBrand;
    public $manualCategory;
    public $manualPurchasePrice;
    public $manualSalePrice;

    /** Catálogos para el formulario manual */
    public $brandsList = [];
    public $categoriesList = [];

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
        $this->date        = Carbon::now()->format('Y-m-d');
        $this->token       = env('MIGO_API_TOKEN');
        $this->departments = DB::table('departments')->get();
        $this->brandsList     = DB::table('brands')->select('id', 'name')->orderBy('name')->get();
        $this->categoriesList = DB::table('categories')->select('id', 'name')->orderBy('name')->get();
        $this->phone = '990062896';
        $this->email = 'info@shipersales.pe';
    }

    private function throwError(string $message)
    {
        $this->dispatch('error', ['label' => $message]);
    }

    public function updatedDepartmentSelect($value)
    {
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
            'name'             => 'required|string|min:3',
            'document_number'  => 'required|numeric|min:3|unique:clients,document_number',
            'document_type'    => 'required|string|min:2',
            'address'          => 'nullable|string|min:3',
            'phone'            => 'nullable|numeric|min:3',
            'email'            => 'nullable|email',
            'departmentSelect' => 'required|numeric|min:1',
            'provinceSelect'   => 'required|numeric|min:1',
            'districtSelect'   => 'required|numeric|min:1'
        ],[],[
            'name'             => 'nombre',
            'document_number'  => 'documento',
            'document_type'    => 'tipo de documento',
            'address'          => 'dirección',
            'phone'            => 'teléfono',
            'email'            => 'correo',
            'departmentSelect' => 'departamento',
            'provinceSelect'   => 'provincia',
            'districtSelect'   => 'distrito'
        ]);

        $client = Client::create([
            'name'          => $this->name,
            'document_number' => $this->document_number,
            'document_type' => $this->document_type,
            'address'       => $this->address,
            'phone'         => $this->phone,
            'email'         => $this->email,
            'department_id' => $this->departmentSelect,
            'province_id'   => $this->provinceSelect,
            'district_id'   => $this->districtSelect
        ]);

        $this->reset(['name','document_number','document_type','address','departmentSelect','provinceSelect','districtSelect']);
        $this->phone = '990062896';
        $this->email = 'info@shipersales.pe';
        $this->render();
        $this->dispatch('close-client-modal');
        $this->dispatch('clientCreated', value: $client->id, text: $client->name.' - '.$client->document_number);
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
            return $this->throwError("El {$this->document_type} debe tener exactamente {$config['size']} dígitos.");
        }

        $payload = [
            $config['field'] => $this->document_number,
            'token'          => $this->token,
        ];

        $response = $api->post(strtolower($this->document_type), $payload);

        if(!isset($response['success']) || $response['success'] === false ){
            return $this->throwError("Recurso no encontrado");
        }

        $this->name = $response[$config['responseKey']] ?? '';
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
                        'article_id'     => !empty($item['id']) ? (int) $item['id'] : null,
                        'custom_product' => $item['custom'] ?? null,
                        'price'          => (float) $item['price'],
                        'quantity'       => (int) $item['quantity'],
                        'subtotal'       => $subtotal,
                        'tax'            => $tax,
                        'total'          => $subtotal + $tax,
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

    public function addManualArticle()
    {
        $this->validate([
            'manualTitle'         => 'required|string|min:3|max:250',
            'manualDetail'        => 'nullable|string|max:250',
            'manualBrand'         => 'required|integer|exists:brands,id',
            'manualCategory'      => 'required|integer|exists:categories,id',
            'manualPurchasePrice' => 'nullable|numeric|min:0',
            'manualSalePrice'     => 'required|numeric|min:0',
        ], [], [
            'manualTitle'         => 'título',
            'manualDetail'        => 'detalle',
            'manualBrand'         => 'marca',
            'manualCategory'      => 'categoría',
            'manualPurchasePrice' => 'precio de compra',
            'manualSalePrice'     => 'precio de venta',
        ]);

        $price = (float) $this->manualSalePrice;

        $this->articlesSelected[] = [
            'id'       => null,
            'title'    => $this->manualTitle,
            'price'    => $price,
            'quantity' => 1,
            'total'    => $price,
            'custom'   => [
                'title'          => $this->manualTitle,
                'detail'         => $this->manualDetail,
                'brand_id'       => (int) $this->manualBrand,
                'category_id'    => (int) $this->manualCategory,
                'purchase_price' => $this->manualPurchasePrice !== null && $this->manualPurchasePrice !== ''
                    ? (float) $this->manualPurchasePrice : null,
            ],
        ];

        $title = $this->manualTitle;
        $this->reset(['manualTitle', 'manualDetail', 'manualBrand', 'manualCategory', 'manualPurchasePrice', 'manualSalePrice']);
        $this->calculateTotals();

        $this->dispatch('toast', ['label' => "«{$title}» se agregó a la cotización."]);
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
