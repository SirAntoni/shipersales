<?php

namespace App\Livewire\Clients;

use App\Models\Department;
use Livewire\Component;
use App\Models\Client;
use Log;
use App\Models\Province;
use App\Models\District;
use Illuminate\Support\Facades\DB;
use App\Services\MigoApiService;

class AddClient extends Component
{
    public $name;
    public $document_number;
    public $document_type;
    public $address;
    public $phone;
    public $email;
    public $departments;
    public $provinces = [];
    public $districts = [];

    public $departmentSelect = null;
    public $provinceSelect = null;
    public $districtSelect = null;
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

    private function throwError(string $message)
    {
        $this->dispatch('error', ['label' => $message]);
    }

    /** Espacios, tabuladores y espacios duros pegados junto al dato rompen la validación y a SUNAT. */
    private function limpiarCampos(): void
    {
        $this->document_number = Client::normalizarNumeroDocumento((string) $this->document_number);
        $this->name            = Client::normalizarNombre((string) $this->name);
    }

    public function mount()
    {

        $this->token = env('MIGO_API_TOKEN');
        $departments = DB::table('departments')->get();
        $this->departments = $departments;
        $this->email = "info@shipersales.pe";
        $this->phone = "990062896";
    }

    public function searchDocument(MigoApiService $api)
    {
        $this->limpiarCampos();


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

    protected $rules = [
        'name' => 'required|string|min:3',
        'document_number' => 'required|numeric|min:3|unique:clients,document_number',
        'document_type' => 'required|string|min:2',
        'address' => 'nullable|string|min:3',
        'phone' => 'nullable|numeric|min:3',
        'email' => 'nullable|email',
        'departmentSelect' => 'required|numeric|min:1',
        'provinceSelect' => 'required|numeric|min:1',
        'districtSelect' => 'required|numeric|min:1'
    ];

    protected $validationAttributes = [
        'name' => 'nombre',
        'document_number' => 'documento',
        'document_type' => 'tipo de documento',
        'address' => 'dirección',
        'phone' => 'teléfono',
        'email' => 'correo',
        'departmentSelect' => 'departamento',
        'provinceSelect' => 'provincia',
        'districtSelect' => 'distrito'
    ];

    public function save()
    {
        $this->limpiarCampos();
        $this->validate();

        // Un RUC mal escrito se detecta aquí y no como rechazo 2017 de SUNAT al facturar
        if ($this->document_type === 'RUC' && ! Client::rucValido($this->document_number)) {
            return $this->throwError('El RUC no es válido: debe tener 11 dígitos, empezar con 10, 15, 16, 17 o 20 y tener el dígito verificador correcto.');
        }
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

        $this->dispatch('success', ['label' => 'Se agrego el cliente con éxito.', 'btn' => 'Ir a clientes', 'route' => route('clients.index')]);
    }

    public function render()
    {
        return view('livewire.clients.add-client');
    }
}
