<?php

namespace App\Livewire\Users;

use Livewire\Component;
use App\Models\User;
use App\Services\AuditLogger;
use Spatie\Permission\Models\Permission;
use Log;

class AddUser extends Component
{
    public $name;
    public $email;
    public $password;
    public $permissions;
    public $permissionsSelected = [];


    public function mount(){
        $this->permissions = Permission::all();
    }

    protected $rules = [
        'name' => 'required',
        'email' => 'required|email',
        'password' => 'required|min:8',
    ];

    public function save(){
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => bcrypt($this->password)
        ]);

        AuditLogger::permisos($user, $user->permissions()->sync($this->permissionsSelected));

        $this->dispatch('success',['label' => 'Se agrego el usuario con éxito.','btn' => 'Ir a usuarios','route' => route('users.index')]);
    }

    public function render()
    {
        return view('livewire.users.add-user');
    }
}
