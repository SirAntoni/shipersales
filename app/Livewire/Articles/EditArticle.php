<?php

namespace App\Livewire\Articles;

use App\Http\Controllers\ContactController;
use App\Models\Article;
use App\Models\ArticleMarketplace;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Contact;
use Livewire\Component;

class EditArticle extends Component
{
    public $id;
    public $title = '';
    public $detail = '';
    public $description = '';
    public $sku;
    public $stock;
    public $brand_id = '';
    public $category_id = '';
    public $purchase_price;
    public $sale_price;
    public $rows = [];

    public $contacts;

    public function mount(){
        $article = Article::find($this->id);
        $this->title = $article->title;
        $this->detail = $article->detail;
        $this->description = $article->description;
        $this->sku = $article->sku;
        $this->stock = $article->stock;
        $this->brand_id = $article->brand_id;
        $this->category_id = $article->category_id;
        $this->purchase_price = sprintf('%.2f', $article->purchase_price);
        $this->sale_price = sprintf('%.2f', $article->sale_price);

        $this->contacts = Contact::all();

        $existing = $article
            ->marketplaceCodes()
            ->get(['id','contact_id','code'])
            ->toArray();

        $this->rows = count($existing)
            ? $existing
            : [['id'=>null,'contact_id'=>null,'code'=>'']];
    }

    protected $rules = [
        'title' => 'required|min:3',
        'detail' => 'string|nullable|max:250',
        'description' => 'string|nullable|max:500',
        'brand_id' => 'required|integer|exists:brands,id',
        'category_id' => 'required|integer|exists:categories,id',
        'purchase_price' => 'decimal:2|nullable',
        'sale_price' => 'decimal:2|nullable',
    ];

    protected $validationAttributes = [
        'title' => 'titulo',
        'detail' => 'detalle',
        'description' => 'descripción',
        'brand_id' => 'marca',
        'category_id' => 'categoría',
        'purchase_price' => 'precio de compra',
        'sale_price' => 'precio de venta',
    ];

    public function addRow()
    {
        $this->rows[] = ['contact_id' => '', 'code' => ''];
    }

    public function removeRow($index)
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function save(){
        $this->validate();

        $article = tap(Article::find($this->id))->update([
            'title' => $this->title,
            'detail' => $this->detail,
            'description' => $this->description,
            'brand_id' => $this->brand_id,
            'category_id' => $this->category_id,
            'purchase_price' => $this->purchase_price,
            'sale_price' => $this->sale_price
        ]);

        // a) Array con los IDs que el usuario dejó
        $submittedIds = collect($this->rows)
            ->pluck('id')
            ->filter()   // quita nulls
            ->toArray();

        // b) IDs que hay en BD antes de guardar
        $existingIds = $article
            ->marketplaceCodes()
            ->pluck('id')
            ->toArray();

        // c) Eliminar los que no están en submittedIds
        $toDelete = array_diff($existingIds, $submittedIds);
        ArticleMarketplace::destroy($toDelete);

        // d) Recorre y crea o actualiza
        foreach ($this->rows as $row) {
            if (isset($row['id'])) {
                // actualizar
                $article
                    ->marketplaceCodes()
                    ->find($row['id'])
                    ->update([
                        'contact_id' => $row['contact_id'],
                        'code'       => $row['code'],
                    ]);
            } else {
                // crear nuevo
                if(!is_null($row['contact_id'])){
                    $article
                        ->marketplaceCodes()
                        ->create([
                            'contact_id' => $row['contact_id'],
                            'code'       => $row['code'],
                        ]);
                }

            }
        }

        $this->dispatch('success',['label' => 'Se edito el artículo con éxito.','btn' => 'Ir a artículos','route' => route('articles.index')]);
    }


    public function render()
    {
        $categories = Category::all();
        $brands = Brand::all();
        return view('livewire.articles.edit-article',compact('categories','brands'));
    }
}
