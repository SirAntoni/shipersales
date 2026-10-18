<?php

namespace App\Livewire\Users;

use Livewire\Component;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Services\AuditLogger;
use Log;

class EditUser extends Component
{
    public $id;
    public $name;
    public $email;
    public $permissions;
    public $permissionsSelected;

    protected $rules = [
        'name' => 'required',
        'email' => 'required|email'
    ];

    public function mount(){
        $user = User::find($this->id);
        $this->name = $user->name;
        $this->email = $user->email;
        $this->permissions = Permission::all();
        $this->permissionsSelected = $user->permissions->pluck('id')->toArray();
    }

    public function save(){
        $this->validate();

        $user = User::find($this->id);
        $user->update([
            'name' => $this->name,
            'email' => $this->email
        ]);
        $user->refresh();

        AuditLogger::permisos($user, $user->permissions()->sync($this->permissionsSelected));

        $this->dispatch('success',['label' => 'Se edito el usuario con éxito.','btn' => 'Ir a usuarios','route' => route('users.index')]);
    }

    public function render()
    {
        return view('livewire.users.edit-user');
    }
}
