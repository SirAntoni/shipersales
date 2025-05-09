<?php

namespace App\Livewire\Auth;
use Illuminate\Support\Facades\Auth;

use Livewire\Component;

class Login extends Component
{
    public $email;
    public $password;

    public $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    public function login(){

        $this->validate();

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            session()->regenerate();
            return redirect()->intended('/');
        } else {
            session()->flash('error', 'Credenciales incorrectas.');
        }

    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
