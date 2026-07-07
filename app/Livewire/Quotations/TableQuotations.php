<?php

namespace App\Livewire\Quotations;

use App\Models\Article;
use App\Models\Contact;
use App\Models\PaymentMethod;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class TableQuotations extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';

    /** Modal de productos nuevos (manuales) de una cotización */
    public ?int $catalogQuotationId = null;
    public ?string $catalogQuotationNumber = null;
    public array $catalogItems = [];

    /** Modal de aceptar cotización → generar venta */
    public ?int $acceptQuotationId = null;
    public ?string $acceptQuotationNumber = null;
    public ?string $acceptQuotationClient = null;
    public $acceptQuotationTotal = 0;
    public $acceptContact;
    public $acceptPaymentMethod;
    public $acceptNumber;
    public $acceptDeliveryFee;
    public $contacts = [];
    public $paymentMethods = [];

    public function mount()
    {
        $this->contacts       = Contact::select('id', 'name')->get();
        $this->paymentMethods = PaymentMethod::select('id', 'name')->get();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function rendered()
    {
        $this->dispatch('reinit-tippy');
    }

    public function changeStatus($id, $status)
    {
        $quotation = Quotation::findOrFail($id);

        if (!in_array($status, [Quotation::STATUS_ACCEPTED, Quotation::STATUS_REJECTED, Quotation::STATUS_PENDING])) {
            return;
        }

        // Una cotización con venta generada ya no cambia de estado
        if ($quotation->sale_id) {
            $this->dispatch('errorNotRoute', ['label' => "La cotización {$quotation->number} ya tiene una venta generada: no se puede cambiar su estado."]);
            return;
        }

        // Aceptar pasa por el flujo de generación de venta
        if ($status === Quotation::STATUS_ACCEPTED) {
            $this->openAcceptModal($quotation->id);
            return;
        }

        $quotation->update(['status' => $status]);

        $this->dispatch('successNotRoute', ['label' => "La cotización {$quotation->number} pasó a estado " . strtoupper($status) . '.']);
    }

    /** Valida los productos y abre el modal con los datos faltantes para la venta */
    public function openAcceptModal($id)
    {
        $quotation = Quotation::with(['client:id,name', 'quotationDetails'])->findOrFail($id);

        if ($quotation->sale_id) {
            $this->dispatch('errorNotRoute', ['label' => "La cotización {$quotation->number} ya tiene una venta generada."]);
            return;
        }

        // 1) Todos los productos deben existir en el catálogo
        $pendientes = $quotation->quotationDetails->whereNull('article_id');

        if ($pendientes->isNotEmpty()) {
            $titulos = $pendientes->map(fn ($d) => $d->custom_product['title'] ?? '—')->implode(', ');
            $this->dispatch('errorNotRoute', [
                'label' => "No se puede aceptar: hay productos que aún no existen en el catálogo ({$titulos}). Guárdalos primero con el botón de productos nuevos.",
            ]);
            return;
        }

        $this->acceptQuotationId     = $quotation->id;
        $this->acceptQuotationNumber = $quotation->number;
        $this->acceptQuotationClient = $quotation->client->name ?? '—';
        $this->acceptQuotationTotal  = (float) $quotation->total;
        $this->reset(['acceptContact', 'acceptPaymentMethod', 'acceptNumber', 'acceptDeliveryFee']);
        $this->resetErrorBag();

        $this->dispatch('open-accept-modal');
    }

    /** Igual que en nueva venta: algunos contactos definen el método de pago */
    public function updatedAcceptContact()
    {
        if (in_array((int) $this->acceptContact, [2, 7, 8, 9, 10], true)) {
            $this->acceptPaymentMethod = 1;
        } elseif ((int) $this->acceptContact === 3) {
            $this->acceptPaymentMethod = 4;
        } else {
            $this->acceptPaymentMethod = '';
        }
    }

    /** Genera la venta desde la cotización y la marca como aceptada */
    public function generateSale()
    {
        $this->validate([
            'acceptContact'       => 'required',
            'acceptPaymentMethod' => 'required',
            'acceptDeliveryFee'   => 'nullable|numeric|min:0',
            'acceptNumber'        => [
                'nullable',
                Rule::unique('sales', 'number')->where(fn ($q) => $q->where('status', '<>', 0)),
            ],
        ], [], [
            'acceptContact'       => 'contacto',
            'acceptPaymentMethod' => 'método de pago',
            'acceptDeliveryFee'   => 'precio delivery',
            'acceptNumber'        => 'número de orden',
        ]);

        $quotation = Quotation::with('quotationDetails.article')->findOrFail($this->acceptQuotationId);

        if ($quotation->sale_id) {
            $this->dispatch('errorNotRoute', ['label' => 'Esta cotización ya tiene una venta generada.']);
            return;
        }

        try {
            $sale = null;

            DB::transaction(function () use ($quotation, &$sale) {
                $sale = Sale::create([
                    'number'            => trim((string) $this->acceptNumber) ?: $this->generarCodigo(),
                    'date'              => Carbon::now()->format('Y-m-d'),
                    'subtotal'          => $quotation->subtotal,
                    'tax'               => $quotation->tax,
                    'total'             => $quotation->total,
                    'delivery'          => empty($this->acceptDeliveryFee) ? 0 : 1,
                    'delivery_fee'      => $this->acceptDeliveryFee ?? 0,
                    'client_id'         => $quotation->client_id,
                    'user_id'           => auth()->id(),
                    'contact_id'        => $this->acceptContact,
                    'payment_method_id' => $this->acceptPaymentMethod,
                    'status'            => Sale::SALE_PENDING,
                ]);

                foreach ($quotation->quotationDetails as $detail) {
                    // Control de stock con lock, igual que en nueva venta
                    $article = Article::lockForUpdate()->findOrFail($detail->article_id);
                    $qty     = (int) $detail->quantity;

                    if ($article->stock < $qty) {
                        throw new \RuntimeException("Stock insuficiente para {$article->title} (disponible: {$article->stock}, requerido: {$qty})");
                    }

                    $sale->saleDetails()->create([
                        'price'       => $detail->price,
                        'quantity'    => $qty,
                        'tax'         => $detail->tax,
                        'total'       => $detail->total,
                        'subtotal'    => $detail->subtotal,
                        'article_id'  => $article->id,
                        'category_id' => $article->category_id,
                        'brand_id'    => $article->brand_id,
                    ]);

                    $article->decrement('stock', $qty);
                }

                $quotation->update([
                    'status'  => Quotation::STATUS_ACCEPTED,
                    'sale_id' => $sale->id,
                ]);
            });

            $this->reset(['acceptQuotationId', 'acceptQuotationNumber', 'acceptQuotationClient', 'acceptQuotationTotal', 'acceptContact', 'acceptPaymentMethod', 'acceptNumber', 'acceptDeliveryFee']);
            $this->dispatch('close-accept-modal');
            $this->dispatch('successNotRoute', ['label' => "La cotización {$quotation->number} fue aceptada y se generó la venta {$sale->number}."]);
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('errorNotRoute', ['label' => $e->getMessage()]);
        }
    }

    private function generarCodigo(): string
    {
        return Carbon::now()->format('ymds') . random_int(1000, 9999);
    }

    public function delete($id)
    {
        $this->dispatch('delete', [
            'label' => '¿Está seguro que desea eliminar la cotización?',
            'btn'   => 'Eliminar',
            'id'    => $id,
        ]);
    }

    #[On('destroy')]
    public function destroy($id)
    {
        Quotation::find($id)?->delete(); // detalles caen por cascade
    }

    /** Abre el modal con los productos manuales de la cotización */
    public function openCatalogModal($quotationId)
    {
        $quotation = Quotation::with(['quotationDetails' => fn ($q) => $q->whereNull('article_id')])
            ->findOrFail($quotationId);

        $this->catalogQuotationId     = $quotation->id;
        $this->catalogQuotationNumber = $quotation->number;
        $this->catalogItems = $quotation->quotationDetails->map(fn ($d) => [
            'detail_id' => $d->id,
            'title'     => $d->custom_product['title'] ?? '—',
            'detail'    => $d->custom_product['detail'] ?? null,
            'price'     => (float) $d->price,
            'saved'     => false,
        ])->values()->toArray();

        $this->dispatch('open-catalog-modal');
    }

    /** Pide confirmación antes de guardar el producto en el catálogo */
    public function confirmSaveToCatalog($detailId)
    {
        $item = collect($this->catalogItems)->firstWhere('detail_id', $detailId);

        if (!$item || $item['saved']) {
            return;
        }

        $this->dispatch('questionSaveToCatalog', [
            'label' => "¿Guardar «{$item['title']}» en el catálogo? Se creará con stock 0 y quedará disponible en almacén y para futuras cotizaciones.",
            'id'    => $detailId,
        ]);
    }

    /** Crea el artículo en el catálogo y lo enlaza al detalle de la cotización */
    #[On('processSaveToCatalog')]
    public function saveToCatalog($detailId)
    {
        $detail = QuotationDetail::whereNull('article_id')->find($detailId);

        if (!$detail || empty($detail->custom_product)) {
            $this->dispatch('errorNotRoute', ['label' => 'Este producto ya fue guardado en el catálogo.']);
            return;
        }

        $data = $detail->custom_product;

        try {
            DB::transaction(function () use ($detail, $data) {
                $article = Article::create([
                    'title'          => $data['title'],
                    'detail'         => $data['detail'] ?? '',
                    'description'    => '',
                    'sku'            => Article::generateSku(),
                    'stock'          => 0,
                    'brand_id'       => $data['brand_id'],
                    'category_id'    => $data['category_id'],
                    'purchase_price' => $data['purchase_price'] ?? 0,
                    'sale_price'     => (float) $detail->price,
                    'status'         => 'active',
                    'on_demand'      => 0,
                ]);

                $detail->update(['article_id' => $article->id]);
            });

            foreach ($this->catalogItems as &$item) {
                if ($item['detail_id'] == $detailId) {
                    $item['saved'] = true;
                }
            }
            unset($item);

            $this->dispatch('successNotRoute', ['label' => "«{$data['title']}» se guardó en el catálogo (stock 0)."]);
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('errorNotRoute', ['label' => 'Ocurrió un error guardando el producto en el catálogo.']);
        }
    }

    public function render()
    {
        $quotations = Quotation::with(['client:id,name', 'user:id,name'])
            ->withCount(['quotationDetails as custom_pending_count' => fn ($q) => $q->whereNull('article_id')])
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('number', 'like', "%{$this->search}%")
                      ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.quotations.table-quotations', compact('quotations'));
    }
}
