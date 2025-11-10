<?php

namespace App\Livewire\Articles;

use App\Models\Article;
use App\Models\ArticleMarketplace;
use App\Models\ArticleContactPrice;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Contact;
use Livewire\Component;

class EditArticle extends Component
{
    // ===== Campos base del artículo =====
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

    // ===== Marketplace (ya existente) =====
    public $rows = [];      // [{id?, contact_id, code}]
    public $contacts;       // lista para selects

    // ===== Precios por contacto (nuevo) =====
    public $priceRows = []; // [{id?, contact_id, price, active}]

    public function mount()
    {
        /** @var Article $article */
        $article = Article::findOrFail($this->id);

        // Campos base
        $this->title          = $article->title;
        $this->detail         = $article->detail;
        $this->description    = $article->description;
        $this->sku            = $article->sku;
        $this->stock          = $article->stock;
        $this->brand_id       = $article->brand_id;
        $this->category_id    = $article->category_id;
        $this->purchase_price = sprintf('%.2f', $article->purchase_price);
        $this->sale_price     = sprintf('%.2f', $article->sale_price);

        // Catálogo de contactos (para ambos bloques)
        $this->contacts = Contact::all();

        // Marketplace rows existentes
        $existingMarketplace = $article->marketplaceCodes()
            ->get(['id','contact_id','code'])
            ->toArray();

        $this->rows = count($existingMarketplace)
            ? $existingMarketplace
            : [['id' => null, 'contact_id' => null, 'code' => '']];

        // Price rows existentes
        $existingPrices = $article->contactPrices()
            ->get(['id','contact_id','price','active'])
            ->map(function ($r) {
                return [
                    'id'         => $r['id'],
                    'contact_id' => $r['contact_id'],
                    'price'      => $r['price'],
                    'active'     => (bool) $r['active'], // <- clave
                ];
            })
            ->toArray();

        $this->priceRows = count($existingPrices)
            ? $existingPrices
            : [['id' => null, 'contact_id' => null, 'price' => null, 'active' => true]];
    }

    // ===== Validación =====
    protected $rules = [
        'title'          => 'required|min:3',
        'detail'         => 'string|nullable|max:250',
        'description'    => 'string|nullable|max:500',
        'brand_id'       => 'required|integer|exists:brands,id',
        'category_id'    => 'required|integer|exists:categories,id',
        'purchase_price' => 'decimal:2|nullable',
        'sale_price'     => 'decimal:2|nullable',
        // Filas dinámicas: validaremos manual en save() para evitar ruido al tipear
    ];

    protected $validationAttributes = [
        'title'          => 'título',
        'detail'         => 'detalle',
        'description'    => 'descripción',
        'brand_id'       => 'marca',
        'category_id'    => 'categoría',
        'purchase_price' => 'precio de compra',
        'sale_price'     => 'precio de venta',
    ];

    // ===== Helpers filas (marketplace) =====
    public function addRow()
    {
        $this->rows[] = ['contact_id' => '', 'code' => ''];
    }

    public function removeRow($index)
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    // ===== Helpers filas (precios por contacto) =====
    public function addPriceRow()
    {
        $this->priceRows[] = ['contact_id' => null, 'price' => null, 'active' => true];
    }

    public function removePriceRow($index)
    {
        unset($this->priceRows[$index]);
        $this->priceRows = array_values($this->priceRows);
    }

    // ===== Guardar =====
    public function save()
    {
        $this->validate();

        /** @var Article $article */
        $article = tap(Article::findOrFail($this->id))->update([
            'title'          => $this->title,
            'detail'         => $this->detail,
            'description'    => $this->description,
            'brand_id'       => $this->brand_id,
            'category_id'    => $this->category_id,
            'purchase_price' => $this->purchase_price,
            'sale_price'     => $this->sale_price,
        ]);

        // ===== Persistir códigos marketplace (tal cual lo tenías) =====
        $submittedMarketplaceIds = collect($this->rows)->pluck('id')->filter()->toArray();
        $existingMarketplaceIds  = $article->marketplaceCodes()->pluck('id')->toArray();
        $toDeleteMarketplace     = array_diff($existingMarketplaceIds, $submittedMarketplaceIds);
        ArticleMarketplace::destroy($toDeleteMarketplace);

        foreach ($this->rows as $row) {
            if (isset($row['id'])) {
                $article->marketplaceCodes()
                    ->find($row['id'])
                    ->update([
                        'contact_id' => $row['contact_id'],
                        'code'       => $row['code'],
                    ]);
            } else {
                if (!is_null($row['contact_id'])) {
                    $article->marketplaceCodes()->create([
                        'contact_id' => $row['contact_id'],
                        'code'       => $row['code'],
                    ]);
                }
            }
        }

        // ===== Persistir precios por contacto (nuevo) =====
        // Sanitiza y valida mínimamente filas completas
        $cleanPrices = collect($this->priceRows)
            ->map(function ($row) {
                return [
                    'id'         => $row['id']         ?? null,
                    'contact_id' => $row['contact_id'] ?? null,
                    'price'      => isset($row['price']) ? (float)$row['price'] : null,
                    'active'     => (bool)($row['active'] ?? true),
                ];
            })
            // Filtrar líneas sin contacto ni precio
            ->filter(fn($r) => !is_null($r['contact_id']) && !is_null($r['price']))
            ->values();

        // Enforce unique (contact_id) en memoria para evitar choque con unique index
        $seen = [];
        $cleanPrices = $cleanPrices->filter(function ($r) use (&$seen) {
            if (isset($seen[$r['contact_id']])) return false;
            $seen[$r['contact_id']] = true;
            return true;
        })->values();

        // Diff contra BD
        $submittedPriceIds = $cleanPrices->pluck('id')->filter()->toArray();
        $existingPriceIds  = $article->contactPrices()->pluck('id')->toArray();
        $toDeletePrice     = array_diff($existingPriceIds, $submittedPriceIds);
        ArticleContactPrice::destroy($toDeletePrice);

        // Upsert (create/update)
        foreach ($cleanPrices as $row) {
            $payload = [
                'contact_id' => (int) $row['contact_id'],
                'price'      => (float) $row['price'],
                'active'     => (bool) $row['active'],
            ];
            if (!empty($row['id'])) {
                $article->contactPrices()->where('id', $row['id'])->update($payload);
            } else {
                $article->contactPrices()->create($payload);
            }
        }

        $this->dispatch('success', [
            'label' => 'Se editó el artículo con éxito.',
            'btn'   => 'Ir a artículos',
            'route' => route('articles.index'),
        ]);
    }

    public function render()
    {
        $categories = Category::all();
        $brands     = Brand::all();

        return view('livewire.articles.edit-article', compact('categories', 'brands'));
    }
}
