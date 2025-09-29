<?php

namespace App\Livewire\Inventory;

use Livewire\Attributes\On;
use Livewire\Component;

class Index extends Component
{

    public string $search = '';

    public function render()
    {
        return view('livewire.inventory.index');
    }

    #[On('modal-search-input')]
    public function setSearch(string $value): void
    {
        $this->search = $value;
    }

    // Acción de "Send"
    #[On('modal-send')]
    public function send(): void
    {
        dd($this->search);
        // Aquí tu lógica (validar, buscar, guardar, etc.)
        // ejemplo:
        // $this->validate(['search' => 'required|string|min:2']);
        // event(new Algo($this->search));
        // $this->dispatch('toast', type: 'success', message: 'Enviado');

        // Si prefieres cerrar el modal SOLO tras éxito,
        // deja el botón "Send" sin data-tw-dismiss y aquí emite un evento
        // para que un pequeño script haga el close.
    }
}
