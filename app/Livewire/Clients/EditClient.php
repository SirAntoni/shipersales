<?php

namespace App\Livewire\Clients;

use App\Models\Department;
use App\Models\District;
use App\Models\Province;
use App\Services\MigoApiService;
use Illuminate\Validation\Rule;
use Livewire\Component;
use App\Models\Client;


class EditClient extends Component
{
    public $id;
    public $name;
    public $document_type;
    public $document_number;
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
    public function mount(){
        $this->token = env('MIGO_API_TOKEN');
        $departments = Department::all();
        $this->departments = $departments;

        $client = Client::find($this->id);
        $this->name = $client->name;
        $this->document_type = $client->document_type;
        $this->document_number = $client->document_number;
        $this->address = $client->address;
        $this->phone = $client->phone;
        $this->email = $client->email;

        $this->provinces = Province::where('department_id', $client->department_id)->get();
        $this->districts = District::where('province_id', $client->province_id)->get();

        $this->departmentSelect = $client->department_id;
        $this->provinceSelect = $client->province_id;
        $this->districtSelect = $client->district_id;


    }

    public function searchDocument(MigoApiService $api)
    {
        $this->limpiarCampos();

        $this->validate([
            'document_number' => [
                Rule::unique('clients', 'document_number')->ignore($this->id),
            ],
        ]);

        if (! isset($this->docConfig[$this->document_type])) {
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
        $this->provinces = Province::where('department_id', $value)->get();

    }

    public function updatedProvinceSelect($value)
    {
        $this->districts = District::where('province_id', $value)->get();
    }
    public function render()
    {
        return view('livewire.clients.edit-client');
    }

    protected $rules = [
        'name' => 'required|string|min:3',
        'document_number' => 'required|numeric|min:3',
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

    public function save(){
        $this->limpiarCampos();
        $this->validate();

        // Un RUC mal escrito se detecta aquí y no como rechazo 2017 de SUNAT al facturar
        if ($this->document_type === 'RUC' && ! Client::rucValido($this->document_number)) {
            return $this->throwError('El RUC no es válido: debe tener 11 dígitos, empezar con 10, 15, 16, 17 o 20 y tener el dígito verificador correcto.');
        }
        $client = Client::find($this->id)->update([
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
        $this->dispatch('success',['label' => 'Se edito el cliente con éxito.','btn' => 'Ir a clientes','route' => route('clients.index')]);
    }

}
