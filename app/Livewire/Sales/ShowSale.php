<?php

namespace App\Livewire\Sales;

use App\Models\Article;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Document;
use App\Models\PaymentMethod;
use App\Services\MigoApiService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Models\Sale;
use App\Models\SaleDetail;

class ShowSale extends Component
{
    const SALE_CANCELED = 0;
    const SALE_PENDING = 1;
    const SALE_APPROVED = 2;
    const SALE_OBSERVATION = 3;

    public $id;
    public $clients;
    public $client;
    public $defaultClient;
    public $defaultPaymentMethod;
    public $defaultContact;
    public $articles;
    public $contacts;
    public $tax;
    public $number;
    public $contact;
    public $paymentMethod;
    public $paymentMethods;
    public $delivery_fee;
    public $date;
    public $granSubtotal;
    public $granTax;
    public $granTotal;
    public $dateSelected;
    public $articleSelected;
    public $articlesSelected = [];
    public $observation = "";

    public $clientSelected;

    public $webhook_imported;

    public $sectionClient = false;
    public $status;

    /** Cache por request del comprobante vigente (no viaja al navegador). */
    private ?Document $comprobanteCache = null;
    private bool $comprobanteResuelto = false;

    public $name;
    public $document_number;
    public $document_type;
    public $address;
    public $phone;
    public $email;
    public $token;

    public $departments;
    public $provinces = [];
    public $districts = [];
    public $departmentSelect;
    public $districtSelect;
    public $provinceSelect;

    protected array $docConfig = [
        'DNI' => [
            'size'        => 8,
            'field'       => 'dni',
            'responseKey' => 'nombre',
        ],
        'RUC' => [
            'size'        => 11,
            'field'       => 'ruc',
            'responseKey' => 'nombre_o_razon_social',
        ],
    ];

    private function throwError(string $message)
    {
        $this->dispatch('error', ['label' => $message]);
    }

    public function mount()
    {

        $this->token = env('MIGO_API_TOKEN');
        $sale = Sale::find($this->id);
        $this->departments = DB::table('departments')->get();

        $contacts = Contact::select('id','name')->get();
        $paymentMethods = PaymentMethod::select('id','name')->get();

        //Inicio Client
        $this->client = $sale->client_id;
        $this->clientSelected = $sale->client;
        //Fin Client

        $this->contact = $sale->contact_id;
        $this->paymentMethod = $sale->payment_method_id;;
        $this->contacts = $contacts;
        $this->paymentMethods = $paymentMethods;
        $this->date = $sale->date;
        $this->defaultClient = $sale->client->id;
        $this->defaultPaymentMethod = $sale->paymentMethod->id;
        $this->defaultContact = $sale->contact->id;
        $this->number = $sale->number;
        $this->delivery_fee = $sale->delivery_fee;
        $this->granSubtotal = $sale->granSubtotal;
        $this->tax = ($sale->tax > 0) ? 1:0;
        $this->observation = $sale->observations;
        $this->status = $sale->status;
        $this->webhook_imported = $sale->webhook_imported;

        $this->loadDetails();

    }

    /**
     * Carga los detalles de la venta incluidos los eliminados por el
     * administrador (withTrashed), que se siguen mostrando deshabilitados.
     */
    private function loadDetails(): void
    {
        $this->articlesSelected = [];

        // El articulo se carga con withTrashed: hay ventas historicas de
        // articulos dados de baja y su linea tiene que seguir viendose.
        $details = SaleDetail::withTrashed()
            ->with(['article' => fn ($q) => $q->withTrashed(), 'deletedBy'])
            ->where('sale_id', $this->id)
            ->orderBy('id')
            ->get();

        foreach ($details as $detail) {
            if (! $detail->article) {
                continue;
            }

            $this->articlesSelected[] = [
                'id'         => $detail->article->id,
                'detail_id'  => $detail->id,
                'category'   => $detail->article->category_id,
                'brand'      => $detail->brand_id,
                'title'      => $detail->article->title,
                'price'      => $detail->price,
                'quantity'   => $detail->quantity,
                'total'      => $detail->price * $detail->quantity,
                'deleted_at' => $detail->deleted_at?->format('d/m/Y H:i'),
                'deleted_by' => $detail->deletedBy?->name,
            ];
        }

        $this->calculateTotals();
    }

    public function updatedDepartmentSelect($value)
    {
        // Reiniciamos los selects dependientes
        $this->districtSelect = null;
        $this->provinceSelect = null;
        $this->districts = [];
        $this->provinces = DB::table('provinces')->where('department_id', $value)->get();

    }

    public function updatedProvinceSelect($value)
    {
        $this->districts = DB::table('districts')->where('province_id', $value)->get();
    }

    public function saveClient(){
        $this->validate([
            'name' => 'required|string|min:3',
            'document_number' => 'required|numeric|min:3|unique:clients,document_number',
            'document_type' => 'required|string|min:2',
            'address' => 'nullable|string|min:3',
            'phone' => 'nullable|numeric|min:3',
            'email' => 'nullable|email',
            'departmentSelect' => 'required|numeric|min:1',
            'provinceSelect' => 'required|numeric|min:1',
            'districtSelect' => 'required|numeric|min:1'
        ],[],[
            'name' => 'nombre',
            'document_number' => 'documento',
            'document_type' => 'tipo de documento',
            'address' => 'dirección',
            'phone' => 'teléfono',
            'email' => 'correo',
            'departmentSelect' => 'departamento',
            'provinceSelect' => 'provincia',
            'districtSelect' => 'distrito'
        ]);

        Client::create([
            'name' => $this->name,
            'document_number' => $this->document_number,
            'document_type' => $this->document_type,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'department_id' => $this->departmentSelect,
            'province_id' => $this->provinceSelect,
            'district_id' => $this->districtSelect
        ]);

        $this->reset(['name','document_number','document_type','address','phone','email','departmentSelect','provinceSelect','districtSelect']);
        $this->render();
        $this->sectionClient = false;
        $this->dispatch('successNotRoute', ['label' => 'Se agrego el cliente con éxito.']);

    }

    public function searchDocument(MigoApiService $api)
    {

        $this->validate([
            'document_number' => 'required|numeric|min:3|unique:clients,document_number',
        ]);

        if (!isset($this->docConfig[$this->document_type])) {
            return $this->throwError('Selecciona un tipo de documento válido.');
        }

        if ($this->document_type === 'CE') {
            return $this->throwError('Servicio no disponible para este tipo de documento.');
        }

        $config = $this->docConfig[$this->document_type];

        if (strlen($this->document_number) !== $config['size']) {
            return $this->throwError(
                "El {$this->document_type} debe tener exactamente {$config['size']} dígitos."
            );
        }

        // 5) Preparar payload y llamar al servicio
        $payload = [
            $config['field'] => $this->document_number,
            'token'          => $this->token,
        ];

        $response = $api->post(
            strtolower($this->document_type),
            $payload
        );

        if(!isset($response['success']) || $response['success'] === false ){
            return $this->throwError("Recurso no encontrado");
        }

        $this->name = $response[$config['responseKey']] ?? '';

    }

    public function rules()
    {
        return [
            'client'            => 'required',
            'date'              => 'required|date_format:Y-m-d',
            'contact'           => 'required',
            'paymentMethod'     => 'required',
            'delivery_fee'      => 'numeric|nullable',
            'articlesSelected'  => 'required|array|min:1'
        ];
    }

    public function searchClients($query)
    {
        return Client::query()
            ->where('name', 'like', '%'.$query.'%')
            ->orWhere('document_number', 'like', '%'.$query.'%')
            ->limit(10)
            ->get(['id', 'name','document_number'])
            ->map(fn($c) => [
                'value' => $c->id,
                'text'  => $c->name . " - ".  $c->document_number,
            ])
            ->toArray();
    }


    public function searchArticles($query)
    {
        return Article::query()
            ->active()
            ->where(fn($q) => $q
                ->where('title', 'like', '%'.$query.'%')
                ->orWhereHas('brand', fn($qq) =>
                    $qq->where('name', 'like', '%'.$query.'%')
                )
            )
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'value' => $c->id,
                'text'  => trim(
                    $c->title
                    // si existe brand, lo pinto entre paréntesis
                    . ($c->brand?->name ? " ({$c->brand->name})" : '')
                    . " | stock: {$c->stock}"
                    . " | precio: S/. {$c->sale_price}"
                ),
            ])
            ->toArray();
    }

    public function save()
    {
        $this->validate();

        try {
            $estado = DB::transaction(function () {
                // Bloquea la cabecera de la venta
                /** @var \App\Models\Sale $sale */
                $sale = \App\Models\Sale::lockForUpdate()->findOrFail($this->id);

                // Si la venta está cancelada (status = 0) NO debe afectar stock ni kardex
                $affectsStock = ($sale->status !== \App\Models\Sale::SALE_CANCELED);

                // Detalles actuales, bloqueados
                $currentDetails = $sale->saleDetails()->lockForUpdate()->get();

                // Que un detalle este eliminado lo decide la BD, no el navegador:
                // articlesSelected es una propiedad publica que el cliente puede
                // alterar. Se lee DESPUES del lock para que la foto sea la misma
                // que usa la recreacion (si no, una eliminacion simultanea de otra
                // pestaña se desharia recreando la linea y descontando su stock).
                $eliminados = SaleDetail::onlyTrashed()
                    ->where('sale_id', $sale->id)
                    ->pluck('id')
                    ->all();

                // Los detalles eliminados por el administrador no se reescriben:
                // conservan su deleted_at y quedan fuera de stock, kardex y totales.
                $activos = collect($this->articlesSelected)
                    ->reject(fn ($row) => ! empty($row['detail_id']) && in_array((int) $row['detail_id'], $eliminados, true))
                    ->values()
                    ->all();

                if (empty($activos)) {
                    return 'sin-productos';
                }

                // Quitar un producto vigente solo puede hacerse por deleteDetail(),
                // que valida permiso y comprobante. Si la pantalla trae menos lineas
                // persistidas de las que siguen vigentes, se aborta: save() les
                // revertiria el stock y las borraria sin dejar rastro.
                $enPantalla = collect($activos)->pluck('detail_id')->filter()->map(fn ($v) => (int) $v)->all();
                $faltantes  = $currentDetails
                    ->reject(fn ($d) => in_array((int) $d->id, $enPantalla, true))
                    ->count();

                if ($faltantes > 0) {
                    return 'desincronizada';
                }

                // Conjunto de artículos a bloquear (anteriores + nuevos) si afecta stock
                $articles = collect();
                if ($affectsStock) {
                    $articleIds = $currentDetails->pluck('article_id')
                        ->merge(collect($activos)->pluck('id'))
                        ->unique()
                        ->values();

                    // withTrashed: una venta historica puede tener articulos de baja.
                    $articles = \App\Models\Article::withTrashed()
                        ->whereIn('id', $articleIds)
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');
                }

                // 1) Revertir stock de los detalles actuales (solo si afecta stock)
                if ($affectsStock) {
                    foreach ($currentDetails as $d) {
                        $articles[$d->article_id]->increment('stock', (int)$d->quantity);
                    }
                }

                // 2) Eliminar fisicamente los detalles vigentes (se recrean abajo).
                //    Se listan por id a proposito: un ->delete() sobre la relacion
                //    con SoftDeletes activo tocaria tambien los ya eliminados por
                //    el administrador y perderiamos su marca.
                if ($currentDetails->isNotEmpty()) {
                    \App\Models\SaleDetail::whereIn('id', $currentDetails->pluck('id'))->forceDelete();
                }

                // 3) Validar stock para los nuevos (solo si afecta stock)
                if ($affectsStock) {
                    foreach ($activos as $row) {
                        $art = $articles[$row['id']] ?? null;
                        if (!$art) {
                            throw new \RuntimeException("Artículo no encontrado: {$row['id']}.");
                        }
                        if ($art->stock < (int)$row['quantity']) {
                            throw new \RuntimeException("Stock insuficiente para {$art->title} (disp. {$art->stock}, sol. {$row['quantity']}).");
                        }
                    }
                }

                // 4) Crear nuevos detalles + descontar stock si corresponde
                $subtotal = 0.0;

                foreach ($activos as $row) {
                    // El neto se recalcula aqui: 'total' viene del navegador.
                    $neto = round((float) $row['price'] * (int) $row['quantity'], 2);
                    $subtotal += $neto;

                    $detail = $sale->saleDetails()->create([
                        'price'       => $row['price'],
                        'quantity'    => (int)$row['quantity'],
                        'tax'         => ($this->tax == 1) ? $neto * 0.18 : 0,
                        'total'       => ($this->tax == 1) ? $neto * 1.18 : $neto,
                        'article_id'  => $row['id'],
                        'category_id' => $row['category'],
                        'brand_id'    => $row['brand'],
                        'subtotal'    => $neto,
                    ]);

                    if ($affectsStock) {
                        $articles[$row['id']]->decrement('stock', (int)$row['quantity']);
                    }
                }

                // 5) Actualizar cabecera con lo que REALMENTE se acaba de grabar.
                //    Antes se usaban granSubtotal/granTax/granTotal, que se calculan
                //    en pantalla y excluyen las filas segun el deleted_at que manda
                //    el navegador: bastaba marcar una fila viva para descuadrar la
                //    cabecera sin tocar los detalles.
                $tax = ($this->tax == 1) ? round($subtotal * 0.18, 2) : 0.0;

                $sale->update([
                    'client_id'        => $this->client,
                    'subtotal'         => round($subtotal, 2),
                    'tax'              => $tax,
                    'total'            => round($subtotal + $tax, 2),
                    'contact_id'       => $this->contact,
                    'webhook_imported' => null,
                ]);

                return 'ok';
            });

            if ($estado === 'sin-productos') {
                $this->dispatch('error', ['label' => 'La venta debe tener al menos un producto activo.']);
                return;
            }

            if ($estado === 'desincronizada') {
                $this->loadDetails();
                $this->dispatch('error', [
                    'label' => 'La lista de productos cambio desde que abriste la venta. Se actualizo la pantalla: revisa y vuelve a guardar.'
                ]);
                return;
            }

            // Los detalles se recrearon: refrescamos los detail_id en pantalla.
            $this->loadDetails();

            $this->dispatch('success', [
                'label' => 'La venta fue editada con éxito.',
                'btn'   => 'Ir a ventas',
                'route' => route('sales.index'),
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('error', ['label' => 'No se pudo editar la venta: ' . $e->getMessage()]);
            report($e);
        }
    }

    public function saveObservation(){
        $this->validate([
            'observation' => 'required|string',
        ]);

        $sale = Sale::find($this->id);
        $sale->update([
            'observations' => $this->observation,
            'status' => self::SALE_OBSERVATION
        ]);

        $this->dispatch('notification');

    }

    public function deleteObservation(){

        $sale = Sale::find($this->id);
        $sale->update([
            'observations' => "",
            'status' => self::SALE_PENDING
        ]);

        $this->observation = null;

        $this->dispatch('notification');

    }

    protected $messages = [
        'articlesSelected.required' => 'Debe seleccionar al menos 1 artículo'
    ];

    public function updatedArticleSelected($id)
    {

        if ($id) {
            $this->addToArticle($id);
            $this->articleSelected = null;
        }
    }

    public function addToArticle($id)
    {

        $article = Article::find($id);

        if ($article) {

            // Solo se agrupa con una fila vigente: si el articulo esta tachado,
            // volver a seleccionarlo tiene que crear una linea nueva.
            $index = collect($this->articlesSelected)->search(function ($item) use ($article) {
                return $item['id'] == $article->id && empty($item['deleted_at']);
            });

            if ($index !== false) {

                if ($this->articlesSelected[$index]['quantity'] < $article->stock) {
                    $this->articlesSelected[$index]['quantity']++;
                    $this->articlesSelected[$index]['total'] = $this->articlesSelected[$index]['quantity'] * $this->articlesSelected[$index]['price'];
                } else {
                    $this->dispatch('error', ['label' => '4No hay stock disponible para ' . $article->title]);
                }

            } else {

                if ($article->stock > 0) {

                    $this->articlesSelected[] = [
                        'id' => $article->id,
                        'detail_id' => null,
                        'category' => $article->category_id,
                        'brand' => $article->brand_id,
                        'title' => $article->title,
                        'price' => $article->sale_price,
                        'quantity' => 1,
                        'total' => $article->sale_price,
                        'deleted_at' => null,
                        'deleted_by' => null
                    ];

                } else {
                    $this->dispatch('error', ['label' => '1No hay stock disponible para ' . $article->title]);
                }
            }

            $this->calculateTotals();
        }
    }

    // addToArticleSale() y remove() se eliminaron: los detalles ahora se cargan
    // en loadDetails() y quitar un producto pasa por deleteDetail(), que valida
    // permisos y comprobante. Eran metodos publicos (invocables desde el
    // navegador) que modificaban la venta sin ninguna de esas validaciones.

    /* ------------------------------------------------------------------
     | Eliminar / restaurar productos de una venta ya registrada
     |------------------------------------------------------------------*/

    /**
     * Hoy es solo por correo; se migrara a un rol de Spatie Permission.
     * Estos tres metodos son protected a proposito: en Livewire todo metodo
     * publico es invocable desde el navegador, y comprobanteVigente devolveria
     * la fila completa de documents al cliente.
     */
    protected function esSuperAdmin(): bool
    {
        return (bool) Auth::user()?->isSuperAdmin();
    }

    /**
     * Comprobante vigente de la venta: la boleta/factura original que no fue
     * anulada, ni rechazada por SUNAT, ni sustituida por una nota de credito.
     * Mismo criterio que usa NewDocument para impedir la doble emision.
     * Se memoriza por request (propiedad privada, no viaja al navegador).
     */
    protected function comprobanteVigente(): ?Document
    {
        if (! $this->comprobanteResuelto) {
            $this->comprobanteCache = Document::where('sale_id', $this->id)
                ->whereNull('affected_document_id')
                ->whereNotIn('status', ['anulado', 'nota_credito'])
                ->where(fn ($q) => $q->whereNull('status_sunat')
                    ->orWhereNotIn('status_sunat', ['rechazado', 'anulado']))
                ->orderBy('id')
                ->first();

            $this->comprobanteResuelto = true;
        }

        return $this->comprobanteCache;
    }

    protected function puedeEliminarProductos(): bool
    {
        return $this->esSuperAdmin() && ! $this->comprobanteVigente();
    }

    private function findRowIndex(int $detailId): ?int
    {
        foreach ($this->articlesSelected as $index => $row) {
            if ((int) ($row['detail_id'] ?? 0) === $detailId) {
                return $index;
            }
        }

        return null;
    }

    private function countActiveRows(): int
    {
        return collect($this->articlesSelected)
            ->filter(fn ($row) => empty($row['deleted_at']))
            ->count();
    }

    /** Devuelve el motivo por el que NO se puede operar, o null si se puede. */
    private function guardDetailChange(int $detailId, bool $restoring = false): ?string
    {
        if (! $this->esSuperAdmin()) {
            return 'Solo el administrador puede modificar los productos de una venta registrada.';
        }

        if ($comprobante = $this->comprobanteVigente()) {
            return "Esta venta tiene el comprobante {$comprobante->serie}-{$comprobante->correlative} emitido. "
                . 'Anulelo o emita una nota de credito antes de modificar sus productos.';
        }

        $index = $this->findRowIndex($detailId);
        if ($index === null) {
            return 'Ese producto ya no forma parte de esta venta.';
        }

        $estaEliminado = ! empty($this->articlesSelected[$index]['deleted_at']);

        if ($restoring && ! $estaEliminado) {
            return 'Ese producto ya esta activo en la venta.';
        }

        if (! $restoring) {
            if ($estaEliminado) {
                return 'Ese producto ya estaba eliminado.';
            }

            if ($this->countActiveRows() <= 1) {
                return 'La venta debe conservar al menos un producto. Si quieres dejarla sin productos, anula la venta.';
            }
        }

        return null;
    }

    public function deleteDetail($detailId)
    {
        $detailId = (int) $detailId;

        if ($motivo = $this->guardDetailChange($detailId)) {
            $this->dispatch('errorNotRoute', ['label' => $motivo]);
            return;
        }

        $row = $this->articlesSelected[$this->findRowIndex($detailId)];

        $this->dispatch('questionDeleteSaleDetail', [
            'label' => "Se quitara \"{$row['title']}\" de la venta y su stock volvera al inventario. "
                . 'La fila seguira visible, marcada como eliminada.',
            'id'    => $detailId,
        ]);
    }

    #[On('confirmDeleteSaleDetail')]
    public function confirmDeleteSaleDetail($id)
    {
        $detailId = (int) $id;

        if ($motivo = $this->guardDetailChange($detailId)) {
            $this->dispatch('errorNotRoute', ['label' => $motivo]);
            return;
        }

        try {
            $marca = DB::transaction(function () use ($detailId) {
                /** @var \App\Models\Sale $sale */
                $sale = Sale::lockForUpdate()->findOrFail($this->id);

                // Se relee dentro de la transaccion: si otra pestaña ya lo
                // elimino, el scope de SoftDeletes hace que no lo encuentre.
                /** @var \App\Models\SaleDetail $detail */
                $detail = SaleDetail::where('sale_id', $sale->id)
                    ->lockForUpdate()
                    ->findOrFail($detailId);

                // El invariante se revalida contra la BD, no contra la pantalla.
                if (SaleDetail::where('sale_id', $sale->id)->lockForUpdate()->count() <= 1) {
                    throw new \RuntimeException(
                        'La venta debe conservar al menos un producto. Si quieres dejarla sin productos, anula la venta.'
                    );
                }

                // Una venta anulada ya devolvio su stock: no se repone dos veces.
                if ($sale->status != Sale::SALE_CANCELED) {
                    // withTrashed: hay ventas historicas de articulos dados de baja.
                    $article = Article::withTrashed()->lockForUpdate()->find($detail->article_id);

                    if (! $article) {
                        throw new \RuntimeException("Articulo no encontrado: {$detail->article_id}.");
                    }

                    $article->increment('stock', (int) $detail->quantity);
                }

                $detail->deleted_by = Auth::id();
                $detail->save();
                $detail->delete();

                $this->adjustSaleTotals($sale, $detail, -1);

                return $detail->fresh(['deletedBy']);
            });
        } catch (ModelNotFoundException $e) {
            // Otra pestaña o usuario ya lo elimino entre el clic y la confirmacion.
            $this->loadDetails();
            $this->dispatch('errorNotRoute', ['label' => 'Ese producto ya no esta vigente en la venta. Se actualizo la pantalla.']);
            return;
        } catch (\Throwable $e) {
            $this->dispatch('error', ['label' => 'No se pudo eliminar el producto: ' . $e->getMessage()]);
            report($e);
            return;
        }

        $index = $this->findRowIndex($detailId);
        if ($index !== null) {
            $this->articlesSelected[$index]['deleted_at'] = $marca?->deleted_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i');
            $this->articlesSelected[$index]['deleted_by'] = $marca?->deletedBy?->name ?? Auth::user()?->name;
        }

        $this->calculateTotals();

        $this->dispatch('successNotRoute', [
            'label' => 'Producto eliminado de la venta. El stock volvio al inventario.'
        ]);
    }

    public function restoreDetail($detailId)
    {
        $detailId = (int) $detailId;

        if ($motivo = $this->guardDetailChange($detailId, true)) {
            $this->dispatch('errorNotRoute', ['label' => $motivo]);
            return;
        }

        $row = $this->articlesSelected[$this->findRowIndex($detailId)];

        $this->dispatch('questionRestoreSaleDetail', [
            'label' => "Se volvera a incluir \"{$row['title']}\" en la venta y se descontara su stock del inventario.",
            'id'    => $detailId,
        ]);
    }

    #[On('confirmRestoreSaleDetail')]
    public function confirmRestoreSaleDetail($id)
    {
        $detailId = (int) $id;

        if ($motivo = $this->guardDetailChange($detailId, true)) {
            $this->dispatch('errorNotRoute', ['label' => $motivo]);
            return;
        }

        try {
            DB::transaction(function () use ($detailId) {
                /** @var \App\Models\Sale $sale */
                $sale = Sale::lockForUpdate()->findOrFail($this->id);

                // whereNotNull es imprescindible: sin el, restaurar dos veces
                // (dos pestañas) descontaria el stock dos veces.
                /** @var \App\Models\SaleDetail $detail */
                $detail = SaleDetail::onlyTrashed()
                    ->where('sale_id', $sale->id)
                    ->lockForUpdate()
                    ->findOrFail($detailId);

                if ($sale->status != Sale::SALE_CANCELED) {
                    $article = Article::withTrashed()->lockForUpdate()->find($detail->article_id);

                    if (! $article) {
                        throw new \RuntimeException("Articulo no encontrado: {$detail->article_id}.");
                    }

                    if ($article->stock < (int) $detail->quantity) {
                        throw new \RuntimeException(
                            "Stock insuficiente para {$article->title} (disp. {$article->stock}, requiere {$detail->quantity})."
                        );
                    }

                    $article->decrement('stock', (int) $detail->quantity);
                }

                $detail->deleted_by = null;
                $detail->restore();

                $this->adjustSaleTotals($sale, $detail, 1);
            });
        } catch (ModelNotFoundException $e) {
            // Otra pestaña ya lo restauro: sin esto se descontaria el stock dos veces.
            $this->loadDetails();
            $this->dispatch('errorNotRoute', ['label' => 'Ese producto ya estaba activo en la venta. Se actualizo la pantalla.']);
            return;
        } catch (\Throwable $e) {
            $this->dispatch('error', ['label' => 'No se pudo restaurar el producto: ' . $e->getMessage()]);
            report($e);
            return;
        }

        $index = $this->findRowIndex($detailId);
        if ($index !== null) {
            $this->articlesSelected[$index]['deleted_at'] = null;
            $this->articlesSelected[$index]['deleted_by'] = null;
        }

        $this->calculateTotals();

        $this->dispatch('successNotRoute', [
            'label' => 'Producto restaurado en la venta. Su stock volvio a descontarse.'
        ]);
    }

    /**
     * Ajusta la cabecera restando (-1) o sumando (+1) una linea.
     *
     * Se aplica el delta en vez de recalcular la suma de detalles porque
     * sales.total no siempre es esa suma: en las ventas con delivery incluye
     * ademas el flete. Con el delta la cabecera conserva la convencion que ya
     * tuviera la venta. detail.total es el importe con IGV y detail.tax su IGV.
     */
    private function adjustSaleTotals(Sale $sale, SaleDetail $detail, int $signo): void
    {
        $total = $signo * (float) $detail->total;
        $tax   = $signo * (float) $detail->tax;

        $sale->update([
            'subtotal' => round(max(0, (float) $sale->subtotal + ($total - $tax)), 2),
            'tax'      => round(max(0, (float) $sale->tax + $tax), 2),
            'total'    => round(max(0, (float) $sale->total + $total), 2),
        ]);
    }

    public function updateTotal($index)
    {

        if (!isset($this->articlesSelected[$index])) {
            return;
        }

        if (! empty($this->articlesSelected[$index]['deleted_at'])) {
            return;
        }

        $selected = &$this->articlesSelected[$index];

        $article = Article::find($selected['id']);
        if (!$article) {
            $this->dispatch('error', ['label' => 'Artículo no encontrado']);
            return;
        }

        $selected['total'] = (float)$selected['price'] * (int)$selected['quantity'];
        $this->calculateTotals();
    }

    public function calculateTotals()
    {
        // Los productos eliminados siguen en la tabla pero no suman.
        $this->granSubtotal = collect($this->articlesSelected)
            ->filter(fn ($row) => empty($row['deleted_at']))
            ->sum('total');

        if ($this->tax == 1) {
            $this->granTotal = $this->granSubtotal + ($this->granSubtotal * 0.18);
            $this->granTax = $this->granSubtotal * 0.18;
        } else {
            $this->granTotal = $this->granSubtotal;
            $this->granTax = 0;
        }
    }

    public function updateTax()
    {
        $this->calculateTotals();
    }

    public function render()
    {
        return view('livewire.sales.show-sale', [
            'esSuperAdmin'           => $this->esSuperAdmin(),
            'comprobanteVigente'     => $this->comprobanteVigente(),
            'puedeEliminarProductos' => $this->puedeEliminarProductos(),
        ]);
    }
}
