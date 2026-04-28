<?php

namespace App\Livewire\OnDemandProducts;

use App\Livewire\Articles\EditArticle;
use App\Models\Brand;
use App\Models\Category;

class EditOnDemandProduct extends EditArticle
{
    public function render()
    {
        $categories = Category::all();
        $brands     = Brand::all();

        return view('livewire.on-demand-products.edit-on-demand-product', compact('categories', 'brands'));
    }
}
