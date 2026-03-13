<?php

namespace App\Livewire\Sales;

use App\Models\Article;
use App\Services\MigoApiService;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
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
    public $square;
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
    public $sectionClient = false;
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
        'DNI' => ['size' => 8, 'field' => 'dni', 'responseKey' => 'nombre'],
        'RUC' => ['size' => 11, 'field' => 'ruc', 'responseKey' => 'nombre_o_razon_social'],
    ];

    private function throwError(string $message)
    {
        $this->dispatch('error', ['label' => $message]);
    }

    public function generarCodigo()
    {
        $f = Carbon::now();
        return $f->format('ymds') . random_int(1000, 9999);
    }

    public function updatedNumber($value)
    {
        $this->number = trim($value);
    }

    public function rules()
    {
        return [
            'client'           => 'required',
            'date'             => 'required|date_format:Y-m-d',
            'contact'          => 'required',
            'paymentMethod'    => 'required',
            'delivery_fee'     => 'numeric|nullable',
            'articlesSelected' => 'required|array|min:1',
            'number'           => [
                'nullable',
                Rule::unique('sales', 'number')->where(fn($q) => $q->where('status', '<>', 0)),
            ],
        ];
    }

    public function validateNumber(){
        if(empty($this->number)){
            $this->dispatch('questionNumber', [
                'label' => 'Esta seguro que desea agregar una venta sin agregar el número de orden?',
                'btn'   => 'Guardar Venta'
            ]);
        } else {
            $this->save();
        }
    }

    #[On('save')]
    public function save()
    {
        $this->validate();

        try {
            DB::transaction(function () {
                // 1) Crear venta (cabecera)
                $sale = Sale::create([
                    'number'            => empty($this->number) ? $this->generarCodigo() : $this->number,
                    'date'              => $this->date,
                    'subtotal'          => $this->granSubtotal,
                    'tax'               => $this->granTax,
                    'total'             => $this->granTotal,
                    'delivery'          => empty($this->delivery_fee) ? 0 : 1,
                    'delivery_fee'      => $this->delivery_fee ?? 0,
                    'client_id'         => $this->client,
                    'user_id'           => auth()->id(),
                    'contact_id'        => $this->contact,
                    'payment_method_id' => $this->paymentMethod,
                    'status'            => ($this->square == 1) ? Sale::SALE_SQUARE : Sale::SALE_PENDING,
                ]);

                // 2) Detalles + control de stock con lock
                foreach ($this->articlesSelected as $item) {
                    $articleId = (int)$item['id'];
                    $qty       = (int)$item['quantity'];
                    $price     = (float)$item['price'];
                    $subtotal  = (float)$item['total'];

                    // Bloquea registro para concurrencia
                    $art = Article::lockForUpdate()->findOrFail($articleId);

                    // Validación de stock en tiempo real
                    if ($art->stock < $qty) {
                        throw new \RuntimeException("Stock insuficiente para {$art->title} (disponible: {$art->stock}, requerido: {$qty})");
                    }

                    // Crear detalle (respetando tu lógica de impuestos)
                    $tax = ($this->tax == 1) ? $subtotal * 0.18 : 0.0;
                    $total = ($this->tax == 1) ? $subtotal + $tax : $subtotal;

                    $sale->saleDetails()->create([
                        'price'       => $price,
                        'quantity'    => $qty,
                        'tax'         => $tax,
                        'total'       => $total,
                        'article_id'  => $articleId,
                        'category_id' => $item['category'],
                        'brand_id'    => $item['brand'],
                        'subtotal'    => $subtotal,
                    ]);

                    // Descontar stock con el registro bloqueado
                    $art->decrement('stock', $qty);
                }
            });

            // 3) Limpieza y UI
            $this->reset([
                'client','date','contact','paymentMethod',
                'articlesSelected','granTotal','granTax','granSubtotal',
                'number','delivery_fee'
            ]);
            $this->date = Carbon::now()->format('Y-m-d');
            $this->dispatch('reinitTomSelectClients');
            $this->dispatch('success_sale', ['label' => 'La venta fue registrada con éxito.']);

        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('error', ['label' => $e->getMessage()]);
        }
    }

    protected $messages = [
        'articlesSelected.required' => 'Debe seleccionar al menos 1 artículo'
    ];

    public function mount()
    {
        $this->token        = env('MIGO_API_TOKEN');
        $this->contacts     = Contact::select('id','name')->get();
        $this->paymentMethods = PaymentMethod::select('id','name')->get();
        $this->date         = Carbon::now()->format('Y-m-d');
        $this->userId       = auth()->id() ?? 0;
        $this->departments  = DB::table('departments')->get();
        $this->email = "info@shipersales.pe";
        $this->phone = "990062896";
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

    public function updatedContact(){
        // Tu lógica existente de método de pago
        if(in_array((int)$this->contact, [2,7,8,9,10], true)){
            $this->paymentMethod = 1;
        } elseif ((int)$this->contact === 3){
            $this->paymentMethod = 4;
        } else{
            $this->paymentMethod = "";
        }

        // Refrescar precios efectivos por contacto para artículos ya agregados
        $cid = (int) ($this->contact ?? 0);
        if (empty($this->articlesSelected)) return;

        $ids = collect($this->articlesSelected)->pluck('id')->all();

        $rows = DB::table('articles as a')
            ->leftJoin('article_contact_prices as acp', function($j) use ($cid) {
                $j->on('acp.article_id','=','a.id')
                    ->where('acp.contact_id','=',$cid)
                    ->where('acp.active','=',1);
            })
            ->whereIn('a.id',$ids)
            ->selectRaw('a.id, a.stock, COALESCE(acp.price, a.sale_price) as effective_price')
            ->get()
            ->keyBy('id');

        foreach ($this->articlesSelected as &$item) {
            $row = $rows[$item['id']] ?? null;
            if (!$row) continue;

            $item['price'] = (float) $row->effective_price;

            if ($row->stock < $item['quantity']) {
                $item['quantity'] = (int) $row->stock;
            }

            $item['total'] = (float) $item['price'] * (int) $item['quantity'];
        }
        unset($item);

        $this->calculateTotals();
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

        Client::create([
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

        $this->reset(['name','document_number','document_type','address','phone','email','departmentSelect','provinceSelect','districtSelect']);
        $this->render();
        $this->sectionClient = false;
        $this->dispatch('successNotRoute', ['label' => 'Se agrego el cliente con éxito.']);
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

    public function searchArticles($query)
    {
        $cid = (int) ($this->contact ?? 0);

        $rows = DB::table('articles as a')
            ->leftJoin('brands as b', 'b.id', '=', 'a.brand_id')
            ->leftJoin('article_contact_prices as acp', function($join) use ($cid) {
                $join->on('acp.article_id', '=', 'a.id')
                    ->where('acp.contact_id', '=', $cid)
                    ->where('acp.active', '=', 1);
            })
            ->where('a.status', 'active')
            ->where(function($q) use ($query) {
                $q->where('a.title', 'like', '%'.$query.'%')
                    ->orWhere('a.sku', 'like', '%'.$query.'%')
                    ->orWhereExists(function($bq) use ($query) {
                        $bq->from('brands as b2')
                            ->whereColumn('b2.id', 'a.brand_id')
                            ->where('b2.name', 'like', '%'.$query.'%');
                    });
            })
            ->select([
                'a.id','a.title','a.stock','a.category_id','a.brand_id',
                'b.name as brand_name',
                DB::raw('COALESCE(acp.price, a.sale_price) as effective_price'),
            ])
            ->limit(10)
            ->get();

        return $rows->map(function($a){
            $txt = trim(
                $a->title
                . ($a->brand_name ? " ({$a->brand_name})" : '')
                . " | stock: {$a->stock}"
                . " | precio: S/. {$a->effective_price}"
            );
            return ['value' => $a->id, 'text' => $txt];
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
        $cid = (int) ($this->contact ?? 0);

        $article = DB::table('articles as a')
            ->leftJoin('article_contact_prices as acp', function($j) use ($cid) {
                $j->on('acp.article_id', '=', 'a.id')
                    ->where('acp.contact_id', '=', $cid)
                    ->where('acp.active', '=', 1);
            })
            ->where('a.id', $id)
            ->selectRaw('a.id, a.title, a.stock, a.category_id, a.brand_id, COALESCE(acp.price, a.sale_price) as effective_price')
            ->first();

        if ($article) {
            $index = collect($this->articlesSelected)->search(fn ($item) => $item['id'] == $article->id);

            if ($index !== false) {
                if ($this->articlesSelected[$index]['quantity'] < $article->stock) {
                    $this->articlesSelected[$index]['quantity']++;
                    $p = (float) $this->articlesSelected[$index]['price']; // conserva precio vigente del item
                    $q = (int)   $this->articlesSelected[$index]['quantity'];
                    $this->articlesSelected[$index]['total'] = $p * $q;
                } else {
                    $this->dispatch('error', ['label' => 'No hay stock disponible para ' . $article->title]);
                }
            } else {
                if ($article->stock > 0) {
                    $price = (float) $article->effective_price;
                    $this->articlesSelected[] = [
                        'id'       => $article->id,
                        'category' => $article->category_id,
                        'brand'    => $article->brand_id,
                        'title'    => $article->title,
                        'price'    => $price,
                        'quantity' => 1,
                        'total'    => $price
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
        if (!isset($this->articlesSelected[$index])) return;

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
            $this->granTax   = $this->granSubtotal * 0.18;
            $this->granTotal = $this->granSubtotal + $this->granTax;
        } else {
            $this->granTax   = 0;
            $this->granTotal = $this->granSubtotal;
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
