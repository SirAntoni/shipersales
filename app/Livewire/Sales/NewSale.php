<?php

namespace App\Livewire\Sales;

use App\Models\Article;
use App\Services\MigoApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
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


    public function validateNumber(){
        if(empty($this->number)){
            $this->dispatch('questionNumber', [
                'label' => 'Esta seguro que desea agregar una venta sin agregar el número de orden?',
                'btn' => 'Guardar Venta'
            ]);
        }else{
            $this->save();
        }
    }

    #[On('save')]
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
        $this->token = env('MIGO_API_TOKEN');
        $contacts = Contact::select('id','name')->get();
        $paymentMethods = PaymentMethod::select('id','name')->get();
        $this->contacts = $contacts;
        $this->paymentMethods = $paymentMethods;
        $this->date = Carbon::now()->format('Y-m-d');
        $this->userId = auth()->id() ?? 0;
        $this->departments = DB::table('departments')->get();
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

    public function updatedContact(){
        if($this->contact == 2 || $this->contact == 7 || $this->contact == 8 || $this->contact == 9 || $this->contact == 10){
            $this->paymentMethod = 1;
        }elseif ($this->contact == 3){
            $this->paymentMethod = 4;
        }else{
            $this->paymentMethod = "";
        }
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
