<?php

namespace App\Livewire\Purchases;

use App\Models\Article;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use Livewire\Component;
use App\Models\Provider;
use Illuminate\Support\Facades\Auth;
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
    public $status;

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
        $this->status = $purchase->status;

        $this->loadDetails();

    }

    /**
     * Carga los detalles como array plano, incluidos los ya quitados
     * (withTrashed), que se siguen mostrando tachados. El articulo tambien va
     * con withTrashed: hay compras historicas de articulos dados de baja y sin
     * eso su linea desaparece (y antes reventaba la pantalla).
     */
    private function loadDetails(): void
    {
        $this->articlesSelected = [];

        $details = PurchaseDetail::withTrashed()
            ->with(['article' => fn ($q) => $q->withTrashed(), 'deletedBy'])
            ->where('purchase_id', $this->id)
            ->orderBy('id')
            ->get();

        foreach ($details as $detail) {
            $this->articlesSelected[] = [
                'detail_id'  => $detail->id,
                'article_id' => $detail->article_id,
                'title'      => $detail->article?->title ?? '(artículo dado de baja)',
                'quantity'   => (int) $detail->quantity,
                'price'      => (float) $detail->price,
                'subtotal'   => (float) $detail->subtotal,
                'total'      => (float) $detail->total,
                'stock'      => $detail->article?->stock,
                'eliminado'  => $detail->deleted_at !== null,
                'deleted_at' => $detail->deleted_at?->format('d/m/Y H:i'),
                'deleted_by' => $detail->deletedBy?->name,
                // Cambio pendiente de guardar: invierte el estado de arriba.
                'marcado'    => false,
            ];
        }
    }

    /** Hoy es solo por correo; se migrara a un rol de Spatie Permission. */
    protected function esSuperAdmin(): bool
    {
        return (bool) Auth::user()?->isSuperAdmin();
    }

    /** Una compra anulada ya devolvio su stock: no se le tocan las lineas. */
    protected function estaAnulada(): bool
    {
        return (int) $this->status === Purchase::PURCHASE_CANCELED;
    }

    protected function puedeQuitarProductos(): bool
    {
        return $this->esSuperAdmin() && ! $this->estaAnulada();
    }

    /** Estado que veria el usuario: el guardado, invertido si hay cambio pendiente. */
    private function estaQuitada(array $fila): bool
    {
        return $fila['marcado'] ? ! $fila['eliminado'] : $fila['eliminado'];
    }

    private function activasEnPantalla(): int
    {
        return collect($this->articlesSelected)
            ->reject(fn ($fila) => $this->estaQuitada($fila))
            ->count();
    }

    /**
     * Marca o desmarca una linea. No toca la base de datos: el efecto sobre el
     * stock se aplica al pulsar Guardar Compra.
     */
    public function toggleProducto($detailId)
    {
        if (! $this->puedeQuitarProductos()) {
            $this->dispatch('errorNotRoute', [
                'label' => $this->estaAnulada()
                    ? 'Esta compra esta anulada: sus productos ya no se pueden modificar.'
                    : 'Solo el administrador puede quitar productos de una compra registrada.'
            ]);
            return;
        }

        foreach ($this->articlesSelected as $i => $fila) {
            if ((int) $fila['detail_id'] === (int) $detailId) {
                $this->articlesSelected[$i]['marcado'] = ! $fila['marcado'];
                return;
            }
        }
    }

    public function save()
    {
        if (! $this->esSuperAdmin() && collect($this->articlesSelected)->contains('marcado', true)) {
            $this->dispatch('error', ['label' => 'Solo el administrador puede quitar productos de una compra registrada.']);
            return;
        }

        try {
            $estado = DB::transaction(function () {
                /** @var \App\Models\Purchase $purchase */
                $purchase = Purchase::lockForUpdate()->findOrFail($this->id);

                // Todo se revalida contra la BD y despues del lock:
                // articlesSelected es una propiedad publica que el navegador
                // puede alterar, y la compra pudo cambiar en otra pestaña.
                //
                // Una compra anulada no admite cambios de lineas, pero seguir
                // permitiendo editar su proveedor: es lo que hacia antes esta
                // pantalla y bloquearlo seria una regresion.
                $anulada = (int) $purchase->status === Purchase::PURCHASE_CANCELED;

                if ($anulada && collect($this->articlesSelected)->contains('marcado', true)) {
                    return 'anulada';
                }

                $enBd = PurchaseDetail::withTrashed()
                    ->where('purchase_id', $purchase->id)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                // Cambios pedidos desde la pantalla, cruzados con la BD real.
                $quitar = collect();
                $restaurar = collect();

                foreach ($this->articlesSelected as $fila) {
                    if (! $fila['marcado']) {
                        continue;
                    }

                    $detalle = $enBd->get((int) $fila['detail_id']);
                    if (! $detalle) {
                        continue; // id ajeno a esta compra o ya borrado fisicamente
                    }

                    // La intencion se deduce del estado que el usuario TENIA en
                    // pantalla, no del actual. Si otra pestaña ya cambio la
                    // linea, deducirlo de la BD haria lo contrario de lo pedido
                    // (pulsar Quitar acabaria restaurando y sumando stock).
                    if ($fila['eliminado'] !== ($detalle->deleted_at !== null)) {
                        return 'desincronizada';
                    }

                    if ($detalle->deleted_at === null) {
                        $quitar->push($detalle);
                    } else {
                        $restaurar->push($detalle);
                    }
                }

                // La compra no puede quedarse sin productos.
                $activasTrasCambio = $enBd
                    ->filter(fn ($d) => $d->deleted_at === null)
                    ->reject(fn ($d) => $quitar->contains('id', $d->id))
                    ->count() + $restaurar->count();

                if ($activasTrasCambio < 1) {
                    return 'sin-productos';
                }

                $articleIds = $quitar->merge($restaurar)->pluck('article_id')->unique()->values();
                $articles = Article::withTrashed()
                    ->whereIn('id', $articleIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                // Quitar una linea resta el stock que la compra habia sumado.
                // Si la mercaderia ya se vendio, la resta dejaria el stock en
                // negativo: se bloquea toda la operacion.
                //
                // Se valida el NETO por articulo, no linea a linea: una misma
                // compra puede tener varias lineas del mismo articulo (hay 10
                // asi) y validarlas por separado contra el stock inicial deja
                // pasar el conjunto y lo hunde en negativo. Las restauraciones
                // del mismo articulo suman a favor.
                $neto = [];
                foreach ($quitar as $detalle) {
                    $neto[$detalle->article_id] = ($neto[$detalle->article_id] ?? 0) - (int) $detalle->quantity;
                }
                foreach ($restaurar as $detalle) {
                    $neto[$detalle->article_id] = ($neto[$detalle->article_id] ?? 0) + (int) $detalle->quantity;
                }

                foreach ($neto as $articleId => $cambio) {
                    $article = $articles->get($articleId);
                    if (! $article) {
                        throw new \RuntimeException("Articulo no encontrado: {$articleId}.");
                    }

                    if ((int) $article->stock + $cambio < 0) {
                        throw new \RuntimeException(
                            "No se puede quitar \"{$article->title}\": habria que restar " . abs($cambio)
                            . " unidades y solo hay {$article->stock} en stock."
                        );
                    }
                }

                foreach ($quitar as $detalle) {
                    $articles[$detalle->article_id]->decrement('stock', (int) $detalle->quantity);
                    $detalle->deleted_by = Auth::id();
                    $detalle->save();
                    $detalle->delete();
                }

                foreach ($restaurar as $detalle) {
                    $articles[$detalle->article_id]->increment('stock', (int) $detalle->quantity);
                    $detalle->deleted_by = null;
                    $detalle->restore();
                }

                $purchase->provider_id = $this->provider;
                $this->ajustarCabecera($purchase, $quitar, $restaurar);
                $purchase->save();

                return 'ok';
            });
        } catch (\Throwable $e) {
            $this->dispatch('error', ['label' => 'No se pudo guardar la compra: ' . $e->getMessage()]);
            report($e);
            return;
        }

        if ($estado === 'anulada') {
            $this->dispatch('error', ['label' => 'Esta compra fue anulada: sus productos ya no se pueden modificar.']);
            return;
        }

        if ($estado === 'sin-productos') {
            $this->dispatch('error', ['label' => 'La compra debe conservar al menos un producto. Restaura alguno antes de guardar.']);
            return;
        }

        if ($estado === 'desincronizada') {
            $this->loadDetails();
            $this->dispatch('error', [
                'label' => 'Los productos de esta compra cambiaron desde que abriste la pantalla. Se actualizo la lista: revisa y vuelve a marcar lo que quieras cambiar.'
            ]);
            return;
        }

        $this->refrescarCabecera();
        $this->loadDetails();

        $this->dispatch('success', [
            'label' => 'La compra fue editada con éxito.',
            'btn'   => 'Ir a compras',
            'route' => route('purchases.index'),
        ]);

    }

    /**
     * Ajusta la cabecera por delta: resta lo quitado y suma lo restaurado.
     *
     * No se recalcula como SUM de los detalles a proposito. Hay 13 compras
     * historicas cuya cabecera no cuadra con sus lineas; recalcular les
     * cambiaria el total en silencio solo por abrir la compra y guardar.
     * Con el delta, quien no toque ninguna linea no ve alterada su cabecera.
     */
    private function ajustarCabecera(Purchase $purchase, $quitar, $restaurar): void
    {
        if ($quitar->isEmpty() && $restaurar->isEmpty()) {
            return;
        }

        // Sin clamp a cero: recortar el resultado romperia la reversibilidad
        // (quitar y volver a restaurar no devolveria el total original) en las
        // compras cuya cabecera ya venia descuadrada respecto a sus lineas.
        $delta = fn ($col) => $restaurar->sum(fn ($d) => (float) $d->{$col})
            - $quitar->sum(fn ($d) => (float) $d->{$col});

        $purchase->subtotal = round((float) $purchase->subtotal + $delta('subtotal'), 2);
        $purchase->tax = round((float) $purchase->tax + $delta('tax'), 2);
        $purchase->total = round((float) $purchase->total + $delta('total'), 2);
    }

    private function refrescarCabecera(): void
    {
        $purchase = DB::table('purchases')->find($this->id);
        $this->granSubtotal = $purchase->subtotal;
        $this->granTax = $purchase->tax;
        $this->granTotal = $purchase->total;
        $this->status = $purchase->status;
    }

    public function render()
    {
        return view('livewire.purchases.show-purchase', [
            'puedeQuitar'      => $this->puedeQuitarProductos(),
            'esSuperAdmin'     => $this->esSuperAdmin(),
            'anulada'          => $this->estaAnulada(),
            'activasEnPantalla' => $this->activasEnPantalla(),
            'hayCambios'       => collect($this->articlesSelected)->contains('marcado', true),
        ]);
    }
}
