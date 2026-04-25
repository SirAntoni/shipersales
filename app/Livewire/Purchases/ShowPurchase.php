<?php

namespace App\Livewire\Purchases;

use App\Models\Article;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use Livewire\Component;
use App\Models\Provider;
use Log;
use DB;

class ShowPurchase extends Component
{
    public $id;
    public $provider;
    public $voucher_type;
    public $document;
    public $passenger;
    public $granSubtotal = 0;
    public $granTotal = 0;
    public $granTax = 0;
    public $articlesSelected = [];
    public $number;
    public $providers;

    public function mount()
    {

        $this->providers = Provider::ordered();

        $purchase = DB::table('purchases')->find($this->id);
        $this->number = $purchase->number;
        $this->provider = $purchase->provider_id;
        $this->voucher_type = $purchase->voucher_type;
        $this->document = $purchase->document;
        $this->passenger = $purchase->passenger;
        $this->granSubtotal = $purchase->subtotal;
        $this->granTax = $purchase->tax;
        $this->granTotal = $purchase->total;

        $this->articlesSelected = PurchaseDetail::with('article')->where('purchase_id', $this->id)->get();

    }

    public function save()
    {
        Purchase::find($this->id)->update(['provider_id' => $this->provider]);
        $this->dispatch('success', ['label' => 'La compra fue editada con éxito.', 'btn' => 'Ir a compras', 'route' => route('purchases.index')]);

    }

    public function render()
    {
        return view('livewire.purchases.show-purchase');
    }
}
