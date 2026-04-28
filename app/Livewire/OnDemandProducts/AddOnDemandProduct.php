<?php

namespace App\Livewire\OnDemandProducts;

use App\Livewire\Articles\AddArticle;

class AddOnDemandProduct extends AddArticle
{
    public function mount()
    {
        parent::mount();
        $this->onDemand = true;
    }

    public function render()
    {
        return view('livewire.on-demand-products.add-on-demand-product', [
            'categories' => $this->categories,
            'brands'     => $this->brands,
            'contacts'   => $this->contacts,
        ]);
    }
}
