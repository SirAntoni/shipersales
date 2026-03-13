<?php

namespace App\Livewire\Articles;

use App\Models\Article;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AddArticle extends Component
{
    public $title = '';
    public $detail = '';
    public $description = '';
    public $barcode = '';
    public $stock;
    public $brand_id = '';
    public $category_id = '';
    public $purchase_price;
    public $sale_price;

    /** Precios por contacto (repeater) */
    public array $contactPrices = [
        // ['contact_id' => '', 'price' => '', 'active' => true]
    ];

    /** Catálogos */
    public $categories = [];
    public $brands = [];
    public $contacts = [];

    protected $rules = [
        'title'          => 'required|min:3',
        'detail'         => 'string|nullable|max:250',
        'description'    => 'string|nullable|max:500',
        'barcode'        => 'string|nullable|max:100',
        'brand_id'       => 'required|integer|exists:brands,id',
        'category_id'    => 'required|integer|exists:categories,id',
        'purchase_price' => 'nullable|numeric|min:0',
        'sale_price'     => 'nullable|numeric|min:0',
        // Reglas suaves: si se llena price, exigir contact_id y numeric
        'contactPrices.*.contact_id' => 'nullable|integer|exists:contacts,id',
        'contactPrices.*.price'      => 'nullable|numeric|min:0',
        'contactPrices.*.active'     => 'boolean',
    ];

    protected $validationAttributes = [
        'title'          => 'título',
        'detail'         => 'detalle',
        'description'    => 'descripción',
        'barcode'        => 'código de barras',
        'brand_id'       => 'marca',
        'category_id'    => 'categoría',
        'purchase_price' => 'precio de compra',
        'sale_price'     => 'precio de venta',
        'contactPrices.*.contact_id' => 'contacto',
        'contactPrices.*.price'      => 'precio por contacto',
        'contactPrices.*.active'     => 'activo',
    ];

    public function mount()
    {
        $this->categories = DB::table('categories')->get();
        $this->brands     = DB::table('brands')->get();
        $this->contacts   = DB::table('contacts')->get();

        // Inicial: 1 fila vacía
        $this->contactPrices = [
            ['contact_id' => '', 'price' => '', 'active' => true],
        ];
    }

    public function addContactPriceRow(): void
    {
        $this->contactPrices[] = ['contact_id' => '', 'price' => '', 'active' => true];
    }

    public function removeContactPriceRow(int $index): void
    {
        unset($this->contactPrices[$index]);
        $this->contactPrices = array_values($this->contactPrices);
    }

    public function generateSku()
    {
        $sku = DB::table('articles')
            ->select(DB::raw("CONCAT('A', LPAD(FLOOR(RAND() * 10000), 4, '0'), YEAR(NOW()), LPAD(MONTH(NOW()), 2, '0')) AS sku"))
            ->whereYear('created_at', now()->year)
            ->orderByDesc('sku')
            ->first();

        if (!$sku) {
            return 'A0001' . now()->year . now()->format('m');
        }
        return $sku->sku;
    }

    public function save()
    {
        $this->validate();

        // Validación adicional: si hay price, debe haber contact_id
        foreach ($this->contactPrices as $i => $row) {
            $hasPrice = ($row['price'] !== '' && $row['price'] !== null);
            $hasContact = ($row['contact_id'] !== '' && $row['contact_id'] !== null);
            if ($hasPrice && !$hasContact) {
                $this->addError("contactPrices.$i.contact_id", 'Selecciona un contacto.');
            }
        }
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        DB::transaction(function () {
            $articleId = DB::table('articles')->insertGetId([
                'title'          => $this->title,
                'detail'         => $this->detail ?? '',
                'description'    => $this->description ?? '',
                'sku'            => $this->generateSku(),
                'barcode'        => $this->barcode ?: null,
                'stock'          => 0,
                'brand_id'       => $this->brand_id,
                'category_id'    => $this->category_id,
                'purchase_price' => $this->purchase_price ?? 0,
                'sale_price'     => $this->sale_price ?? 0,
                'status'         => 'active',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // Insertar precios por contacto (opcional)
            $rowsToInsert = [];
            $seenContacts = [];
            foreach ($this->contactPrices as $row) {
                $cid = $row['contact_id'] ?? null;
                $price = $row['price'] ?? null;

                // Saltar filas vacías
                if (!$cid || $price === '' || $price === null) {
                    continue;
                }

                // Evitar duplicados de contacto en la misma alta
                if (in_array($cid, $seenContacts, true)) {
                    continue;
                }
                $seenContacts[] = $cid;

                $rowsToInsert[] = [
                    'article_id' => $articleId,
                    'contact_id' => (int)$cid,
                    'price'      => (float)$price,
                    'active'     => !empty($row['active']) ? 1 : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($rowsToInsert)) {
                DB::table('article_contact_prices')->insert($rowsToInsert);
            }
        });

        $this->dispatch('success', [
            'label' => 'El artículo se agregó con éxito.',
            'btn'   => 'Ir a articulos',
            'route' => route('articles.index')
        ]);
    }

    public function render()
    {
        return view('livewire.articles.add-article', [
            'categories' => $this->categories,
            'brands'     => $this->brands,
            'contacts'   => $this->contacts,
        ]);
    }
}
