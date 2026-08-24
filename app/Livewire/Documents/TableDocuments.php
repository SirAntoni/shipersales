<?php

namespace App\Livewire\Documents;

use App\Models\Article;
use App\Models\Document;
use App\Models\InventoryAdjustment;
use App\Models\Sale;
use App\Services\PendingDocumentsService;
use App\Services\SunatService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TableDocuments extends Component
{
    public $search;
    public $statusSunat = '';
    public $anio = '';
    public $mes = '';
    public $tipo = '';

    /**
     * Comprobantes por pagina. Se guarda en la URL para que la eleccion
     * sobreviva a un refresco y al volver desde el detalle de un documento.
     */
    #[Url(as: 'porPagina', except: self::POR_PAGINA_DEFECTO)]
    public $perPage = self::POR_PAGINA_DEFECTO;

    use WithPagination;

    public const POR_PAGINA_DEFECTO = 40;

    /** Opciones del selector. Cualquier otro valor cae al de por defecto. */
    public const OPCIONES_POR_PAGINA = [15, 30, 40, 60, 100, 200];

    /** Series de las notas de credito: restan del total en vez de sumar. */
    public const SERIES_NOTA_CREDITO = Document::SERIES_NOTA_CREDITO;

    /**
     * Tipos que ofrece el filtro. Se distinguen por PREFIJO DE SERIE, nunca por
     * document_type: hay comprobantes historicos con serie de boleta (B001) y
     * document_type de factura, y filtrar por ese campo los manda al grupo
     * equivocado y descuadra el pie de totales.
     */
    public const TIPOS = [
        'boleta'        => 'Boletas',
        'factura'       => 'Facturas',
        'nota_credito'  => 'Notas de crédito',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingTipo()
    {
        $this->resetPage();
    }

    public function updatingStatusSunat()
    {
        $this->resetPage();
    }

    public function updatingAnio()
    {
        $this->resetPage();
    }

    public function updatingMes()
    {
        $this->resetPage();
    }

    /** Al cambiar el tamano de pagina la actual puede ya no existir. */
    public function updatingPerPage()
    {
        $this->resetPage();
    }

    /**
     * El valor llega del cliente (select y query string), asi que se acota a
     * las opciones ofrecidas: evita un ?porPagina=100000 contra 3 mil registros.
     */
    public function porPagina(): int
    {
        return in_array((int) $this->perPage, self::OPCIONES_POR_PAGINA, true)
            ? (int) $this->perPage
            : self::POR_PAGINA_DEFECTO;
    }

    public function rendered(){
        $this->dispatch('reinit-tippy');
    }

    #[On('document_destroy')]
    public function document_destroy(Document $document, $motive = null, $reponerStock = true)
    {
        // El diálogo pregunta "¿reponer stock?" solo en boletas; para facturas
        // siempre llega true y el stock se repone como hasta ahora. La regla se
        // impone también aquí: un dispatch manipulado desde la consola podría
        // mandar false para una factura.
        $reponerStock = (bool) $reponerStock || ! $document->esBoleta();

        if ($document->status == 'anulado') {
            return;
        }

        // Las notas de crédito NO se anulan (decisión 2026-07-07): es un caso muy
        // atípico y dejaba estado inconsistente (stock re-descontado + venta anulada
        // + comprobante afectado vigente). El botón está oculto en la vista; este
        // guard cubre invocaciones directas. La lógica de reversión sigue mapeada
        // en finishAnulacion (rama FC/BC) por si algún día se reactiva.
        if (str_starts_with($document->serie, 'FC') || str_starts_with($document->serie, 'BC')) {
            $this->dispatch('error', ['label' => 'Las notas de crédito no se anulan desde el sistema. Si la NC fue emitida por error, contacte al administrador.']);
            return;
        }

        // Si ya tiene nota de crédito, la operación ya fue revertida
        if ($document->status == 'nota_credito') {
            $this->dispatch('error', ['label' => 'Este comprobante ya tiene una nota de crédito emitida: no corresponde darlo de baja.']);
            return;
        }

        // Un documento pendiente aún no existe en SUNAT: no hay nada que dar de baja
        if ($document->status_sunat == 'pendiente') {
            $this->dispatch('error', ['label' => 'Este comprobante está pendiente de envío a SUNAT. Reenvíelo primero o espere a que sea aceptado antes de anularlo.']);
            return;
        }

        // Un documento rechazado nunca fue aceptado por SUNAT: solo anulación local
        if ($document->status_sunat != 'aceptado') {
            $document->update([
                'status' => 'anulado',
                'status_sunat' => 'anulado',
            ]);

            $ventaAnulada = $this->anularVentaPorBaja($document, $reponerStock);

            $label = 'El comprobante fue anulado. Como SUNAT no lo había aceptado, no fue necesario comunicar la baja.';
            if ($ventaAnulada) {
                $label .= $reponerStock
                    ? ' La venta asociada fue anulada y el stock repuesto.'
                    : ' La venta asociada fue anulada sin reponer stock, según lo indicado.';
            }

            $this->dispatch('successNotRoute', ['label' => $label]);
            return;
        }

        // SUNAT solo acepta bajas dentro de los 7 días siguientes a la emisión
        if (Carbon::parse($document->date)->diffInDays(Carbon::now()) > 7) {
            $this->dispatch('error', ['label' => 'Han pasado más de 7 días desde la emisión: SUNAT ya no acepta la baja. Debe emitir una nota de crédito.']);
            return;
        }

        $motive = trim((string) $motive) ?: 'ERROR EN LA EMISIÓN';

        // FC/BC = nota de crédito (tipoDoc 07); si no, factura (01) o boleta (03).
        // Siempre por PREFIJO DE SERIE, no por document_type: hay históricos con
        // serie de boleta y document_type de factura, y elegir RA/RC por ese
        // campo mandaría la baja por el canal equivocado ante SUNAT.
        $esNotaCredito = str_starts_with($document->serie, 'FC') || str_starts_with($document->serie, 'BC');
        $tipoDoc = $esNotaCredito ? '07' : ($document->esBoleta() ? '03' : '01');

        try {
            // Series B* (boletas y sus notas BC) → Resumen Diario (RC) estado 3.
            // Series F* (facturas y sus notas FC) → Comunicación de Baja (RA).
            if (str_starts_with($document->serie, 'B')) {
                $this->anularConResumenDiario($document, $tipoDoc, $motive, $esNotaCredito, $reponerStock);
            } else {
                $this->anularConComunicacionDeBaja($document, $tipoDoc, $motive, $reponerStock);
            }
        } catch (\Throwable $e) {
            Log::error('Error anulando comprobante en SUNAT', [
                'document_id' => $document->id,
                'message'     => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);

            $this->dispatch('error', ['label' => 'Ocurrió un error comunicando la anulación a SUNAT. Inténtelo más tarde.']);
        }
    }

    private function anularConComunicacionDeBaja(Document $document, string $tipoDoc, string $motive, bool $reponerStock = true): void
    {
        $sunat = new SunatService();

        $data = [
            'correlative' => $this->nextAnulacionCorrelative('RA'),
            'date'        => $document->date,
            'details'     => [
                [
                    'tipoDoc'     => $tipoDoc,
                    'serie'       => $document->serie,
                    'correlative' => $document->correlative,
                    'motivoBaja'  => $motive,
                ],
            ],
        ];

        $voided = $sunat->getVoided($data);
        $see    = $sunat->getSee();
        $result = $see->send($voided);

        file_put_contents(storage_path('/xml_path_anulled/' . $voided->getName() . '.xml'), $see->getFactory()->getLastXml());

        $sunatResponse = $sunat->sunatResponse($voided, $result, 'voided');

        $this->finishAnulacion($document, $voided, $sunatResponse, 'voided', $motive, $reponerStock);
    }

    private function anularConResumenDiario(Document $document, string $tipoDoc, string $motive, bool $esNotaCredito, bool $reponerStock = true): void
    {
        $client = $document->client;

        $clienteTipo = [
            'NO DOMICILIADO' => '0',
            'DNI'            => '1',
            'CE'             => '4',
            'RUC'            => '6',
            'PASAPORTE'      => '7',
        ][$client->document_type ?? ''] ?? '1';

        $detail = [
            'tipoDoc'     => $tipoDoc,
            'serie'       => $document->serie,
            'correlative' => $document->correlative,
            'estado'      => '3', // Catalog. 19: anulado
            'clienteTipo' => $clienteTipo,
            'clienteNro'  => $client->document_number ?? '',
            'total'       => (float) $document->total,
            'subtotal'    => (float) $document->subtotal,
            'igv'         => (float) $document->tax,
        ];

        if ($esNotaCredito) {
            // El resumen exige la referencia a la boleta afectada por la nota
            $afectado = Document::find($document->affected_document_id);

            if (!$afectado || !str_starts_with($afectado->serie, 'B')) {
                $this->dispatch('error', ['label' => 'No se pudo determinar la boleta afectada por esta nota de crédito. No es posible comunicar la anulación a SUNAT.']);
                return;
            }

            $detail['docReferencia'] = [
                'tipoDoc' => '03',
                'nroDoc'  => $afectado->serie . '-' . $afectado->correlative,
            ];
        }

        $sunat = new SunatService();

        $data = [
            'correlative' => $this->nextAnulacionCorrelative('RC'),
            'date'        => $document->date,
            'details'     => [$detail],
        ];

        $summary = $sunat->getSummary($data);
        $see     = $sunat->getSee();
        $result  = $see->send($summary);

        file_put_contents(storage_path('/xml_path_anulled/' . $summary->getName() . '.xml'), $see->getFactory()->getLastXml());

        $sunatResponse = $sunat->sunatResponse($summary, $result, 'summary');

        $this->finishAnulacion($document, $summary, $sunatResponse, 'summary', $motive, $reponerStock);
    }

    private function finishAnulacion(Document $document, $comprobante, array $sunatResponse, string $type, string $motive, bool $reponerStock = true): void
    {
        if ($sunatResponse['status'] != 1) {
            $code = (string) ($sunatResponse['code'] ?? '');

            // Error de conexión/autorización: no es un rechazo de SUNAT
            if (stripos($code, 'HTTP') !== false) {
                $label = 'No se pudo conectar con SUNAT para comunicar la anulación (error de conexión o autorización). Inténtelo más tarde.';

                if (!app()->environment('production')) {
                    $label .= ' Nota: en el ambiente de pruebas (beta), SUNAT no autoriza el resumen diario de boletas con el RUC configurado; en producción este servicio sí está habilitado.';
                }

                $this->dispatch('error', ['label' => $label]);
                return;
            }

            $nota  = (string) ($sunatResponse['notes'][0] ?? '');
            $nota  = preg_replace('/ - Detalle:.*$/s', '', $nota);
            $label = $nota !== ''
                ? "SUNAT rechazó la anulación: {$nota}"
                : 'No se pudo comunicar la anulación a SUNAT en estos momentos. Inténtelo más tarde.';

            $this->dispatch('error', ['label' => $label]);
            return;
        }

        $pdf_path = (new SunatService())->generatePdf($comprobante, $type, ['motivo' => $motive]);

        $document->update([
            'status'           => 'anulado',
            'status_sunat'     => 'anulado',
            'xml_path_anulled' => '/xml_path_anulled/' . $comprobante->getName() . '.xml',
            'cdr_path_anulled' => $sunatResponse['cdr'] ?? '',
            'pdf_path_anulled' => $pdf_path,
        ]);

        // Si lo anulado es una nota de crédito, revertir sus efectos:
        // descontar el stock solo si la NC lo repuso, y liberar el documento afectado.
        if (str_starts_with($document->serie, 'FC') || str_starts_with($document->serie, 'BC')) {
            if ($document->stock_restored) {
                foreach ($document->documentDetails as $detail) {
                    Article::find($detail->article_id)?->decrement('stock', (int) $detail->quantity);
                }

                $document->update(['stock_restored' => false]);
            }

            Document::where('id', $document->affected_document_id)
                ->where('status', 'nota_credito')
                ->update(['status' => 'enviado']);

            $this->dispatch('successNotRoute', ['label' => 'El comprobante fue anulado y la baja fue aceptada por SUNAT.']);
            return;
        }

        // Comprobante de venta dado de baja: anular también la venta y reponer stock
        $ventaAnulada = $this->anularVentaPorBaja($document, $reponerStock);

        $label = 'El comprobante fue anulado y la baja fue aceptada por SUNAT.';
        if ($ventaAnulada) {
            $label .= $reponerStock
                ? ' La venta asociada fue anulada y el stock repuesto.'
                : ' La venta asociada fue anulada sin reponer stock, según lo indicado.';
        }

        $this->dispatch('successNotRoute', ['label' => $label]);
    }

    /**
     * La baja de un comprobante de venta anula también la venta asociada y
     * repone su stock, salvo que la venta ya esté anulada, una nota de
     * crédito ya lo haya repuesto, o el usuario pidiera no reponerlo (opción
     * solo para boletas); en ese último caso se registra un ajuste negativo
     * en el kardex para que siga cuadrando con el almacén. Devuelve true si
     * anuló la venta.
     */
    private function anularVentaPorBaja(Document $document, bool $reponerStock = true): bool
    {
        // Las notas de crédito tienen su propia reversión en finishAnulacion
        if (str_starts_with($document->serie, 'FC') || str_starts_with($document->serie, 'BC')) {
            return false;
        }

        $sale = Sale::with('saleDetails')->find($document->sale_id);

        if (!$sale || (int) $sale->status === Sale::SALE_CANCELED) {
            return false;
        }

        $stockYaRepuesto = Document::where('sale_id', $sale->id)
            ->where('status', 'nota_credito')
            ->exists();

        DB::transaction(function () use ($sale, $document, $stockYaRepuesto, $reponerStock) {
            if (!$stockYaRepuesto) {
                if ($reponerStock) {
                    foreach ($sale->saleDetails as $item) {
                        Article::find($item->article_id)?->increment('stock', (int) $item->quantity);
                    }
                } else {
                    InventoryAdjustment::registrarSalidaSinReposicion(
                        $sale,
                        "Baja del comprobante {$document->serie}-{$document->correlative} sin reposición de stock"
                    );
                }
            }

            $sale->update([
                'status'          => Sale::SALE_CANCELED,
                'deletion_date'   => now(),
                'deletion_reason' => "Baja del comprobante {$document->serie}-{$document->correlative}",
            ]);
        });

        return true;
    }

    /**
     * Correlativo del día para RA/RC basado en los XML ya generados,
     * para no reutilizar un nombre que SUNAT pudo haber recibido.
     */
    private function nextAnulacionCorrelative(string $prefix): int
    {
        // Los XML se guardan como {RUC}-RA-YYYYMMDD-N.xml / {RUC}-RC-YYYYMMDD-N.xml
        $pattern = storage_path('xml_path_anulled/*-' . $prefix . '-' . now()->format('Ymd') . '-*.xml');

        return count(glob($pattern)) + 1;
    }

    public function delete($id)
    {
        $document = Document::find($id);

        if (! $document) {
            return;
        }

        // Solo boletas cuya baja repondría stock (venta asociada aún activa)
        // reciben la pregunta "¿reponer stock?"; facturas y ventas ya anuladas
        // siguen el flujo de siempre. Tampoco se pregunta si document_destroy
        // va a rechazar la baja por los 7 días de SUNAT: sería pedir una
        // decisión de inventario para una operación que no se ejecutará.
        $sale = Sale::find($document->sale_id);
        $preguntarReposicion = $document->esBoleta()
            && $sale
            && (int) $sale->status !== Sale::SALE_CANCELED
            && ! ($document->status_sunat == 'aceptado'
                && Carbon::parse($document->date)->diffInDays(Carbon::now()) > 7);

        $this->dispatch('document_delete', [
            'label' => 'Esta seguro que desea anular el comprobante?.',
            'btn' => 'Anular',
            'id' => $id,
            'preguntarReposicion' => $preguntarReposicion,
        ]);
    }

    public function deletePending($id)
    {
        $document = Document::find($id);

        if (!$document) {
            $this->dispatch('error', ['label' => 'No se encontró el comprobante.']);
            return;
        }

        if ($document->status_sunat !== 'pendiente') {
            $this->dispatch('error', ['label' => 'Solo se pueden eliminar comprobantes pendientes de envío a SUNAT.']);
            return;
        }

        if (str_starts_with($document->serie, 'FC') || str_starts_with($document->serie, 'BC')) {
            $this->dispatch('error', ['label' => 'Las notas de crédito no se eliminan desde el sistema.']);
            return;
        }

        $this->dispatch('document_delete_pending', [
            'id'     => $document->id,
            'number' => $document->serie . '-' . $document->correlative,
        ]);
    }

    /**
     * Elimina definitivamente un comprobante pendiente (nunca aceptado por
     * SUNAT). La venta asociada queda intacta: puede emitirse uno nuevo.
     */
    #[On('document_destroy_pending')]
    public function destroyPending(Document $document)
    {
        if ($document->status_sunat !== 'pendiente') {
            $this->dispatch('error', ['label' => 'Solo se pueden eliminar comprobantes pendientes de envío a SUNAT.']);
            return;
        }

        if (str_starts_with($document->serie, 'FC') || str_starts_with($document->serie, 'BC')) {
            $this->dispatch('error', ['label' => 'Las notas de crédito no se eliminan desde el sistema.']);
            return;
        }

        $numero = $document->serie . '-' . $document->correlative;

        // Constancia en logs: el registro desaparece de la tabla
        Log::warning('Comprobante pendiente eliminado', [
            'document'   => $document->only(['id', 'serie', 'correlative', 'date', 'subtotal', 'tax', 'total', 'sale_id', 'client_id', 'notes']),
            'deleted_by' => auth()->id(),
        ]);

        DB::transaction(function () use ($document) {
            $document->documentDetails()->delete();
            $document->delete();
        });

        $this->dispatch('successNotRoute', ['label' => "El comprobante {$numero} fue eliminado. La venta asociada no se modificó: puede emitir uno nuevo."]);
    }

    /**
     * Un documento que SUNAT nunca aceptó (pendiente/rechazado) puede corregir
     * su fecha de emisión y reenviarse: es la salida al error 2329 (fecha fuera
     * del plazo de envío individual). Devuelve null si el documento califica.
     */
    private function motivoFechaNoEditable(Document $document): ?string
    {
        if (str_starts_with($document->serie, 'FC') || str_starts_with($document->serie, 'BC')) {
            return 'Las notas de crédito no admiten cambio de fecha desde el sistema.';
        }

        if ($document->status == 'anulado') {
            return 'Este comprobante está anulado: no corresponde cambiar su fecha.';
        }

        if (!in_array($document->status_sunat, ['pendiente', 'rechazado'], true)) {
            return 'Solo se puede cambiar la fecha de comprobantes que SUNAT no ha aceptado (pendientes o rechazados).';
        }

        return null;
    }

    public function editDate($id)
    {
        $document = Document::find($id);

        if (!$document) {
            $this->dispatch('error', ['label' => 'No se encontró el comprobante.']);
            return;
        }

        if ($motivo = $this->motivoFechaNoEditable($document)) {
            $this->dispatch('error', ['label' => $motivo]);
            return;
        }

        $this->dispatch('document_edit_date', [
            'id'     => $document->id,
            'number' => $document->serie . '-' . $document->correlative,
            'date'   => $document->date ? Carbon::parse($document->date)->format('Y-m-d') : now()->format('Y-m-d'),
            // Misma ventana que NewDocument::rules(): SUNAT rechaza fechas más antiguas (2329)
            'min'    => Carbon::now()->subDays(5)->format('Y-m-d'),
            'max'    => now()->format('Y-m-d'),
        ]);
    }

    #[On('document_change_date')]
    public function changeDate(Document $document, $date)
    {
        if ($motivo = $this->motivoFechaNoEditable($document)) {
            $this->dispatch('error', ['label' => $motivo]);
            return;
        }

        $min = Carbon::now()->subDays(5)->format('Y-m-d');
        $max = now()->format('Y-m-d');
        $date = trim((string) $date);

        $esFechaValida = \DateTime::createFromFormat('Y-m-d', $date) !== false;
        if (!$esFechaValida || $date < $min || $date > $max) {
            $this->dispatch('error', ['label' => "La fecha debe estar entre {$min} y {$max} (plazo que SUNAT acepta para envío individual)."]);
            return;
        }

        $document->update([
            'date'         => $date,
            'status_sunat' => 'pendiente',
            'notes'        => [],
        ]);

        try {
            app(PendingDocumentsService::class)->resend($document);
        } catch (\Throwable $e) {
            Log::error("Error reenviando documento ID {$document->id} tras cambio de fecha: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);

            $this->dispatch('error', ['label' => 'La fecha se actualizó, pero ocurrió un error al reenviar a SUNAT. Puede reintentar con "Reenviar pendientes".']);
            return;
        }

        $document->refresh();

        $numero = $document->serie . '-' . $document->correlative;

        match ($document->status_sunat) {
            'aceptado'  => $this->dispatch('successNotRoute', ['label' => "Fecha actualizada y comprobante {$numero} aceptado por SUNAT."]),
            'rechazado' => $this->dispatch('error', ['label' => "SUNAT rechazó el comprobante {$numero}: " . (($document->notes[0] ?? null) ?: 'revise los datos e intente nuevamente.')]),
            default     => $this->dispatch('error', ['label' => "La fecha se actualizó, pero no se pudo confirmar el envío del comprobante {$numero} (quedó pendiente). Se reintentará automáticamente."]),
        };
    }

    public
    function creditNote($id)
    {
        return redirect()->route('documents.credit-note.blade.php', $id);
    }


    public function resendPending(PendingDocumentsService $service)
    {
        try {
            $stats = $service->resendAll();

            if ($stats['total'] === 0) {
                $this->dispatch('successNotRoute', ['label' => 'No hay documentos pendientes por reenviar.']);
                return;
            }

            $label = "Reenvío ejecutado: {$stats['total']} procesados — {$stats['aceptados']} aceptados"
                . ($stats['rechazados'] > 0 ? ", {$stats['rechazados']} rechazados" : '')
                . ($stats['pendientes'] > 0 ? ", {$stats['pendientes']} siguen pendientes" : '')
                . '.';

            $this->dispatch('success', ['label' => $label, 'btn' => 'Entendido', 'route' => route('documents.index')]);
        } catch (\Throwable $e) {
            Log::error('Error reenviando pendientes a SUNAT', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            $this->dispatch('error', [
                'label' => 'Ocurrió un error enviando a SUNAT. Revisa los logs.',
            ]);
        }
    }

    /** Condicion SQL que identifica una nota de credito por su serie. */
    private static function sqlNotaCredito(): string
    {
        return collect(self::SERIES_NOTA_CREDITO)
            ->map(fn($serie) => "serie LIKE '{$serie}%'")
            ->implode(' OR ');
    }

    /** Normaliza los filtros para que ningun call site dependa del orden. */
    public static function filtrosPorDefecto(array $filtros = []): array
    {
        return array_merge([
            'search'      => null,
            'statusSunat' => null,
            'anio'        => null,
            'mes'         => null,
            'tipo'        => null,
        ], $filtros);
    }

    /**
     * Filtros compartidos por la tabla, el pie de totales y el Excel: los tres
     * tienen que ver exactamente el mismo conjunto de documentos.
     */
    public static function filtrar($query, array $filtros)
    {
        $f = self::filtrosPorDefecto($filtros);
        $nc = self::sqlNotaCredito();

        return $query
            ->when($f['search'], function ($q) use ($f) {
                $search = $f['search'];
                $q->where(function ($q) use ($search) {
                    $q->whereRaw("CONCAT(serie, '-', correlative) LIKE ?", ["%{$search}%"])
                      ->orWhere('serie', 'LIKE', "%{$search}%")
                      ->orWhere('correlative', 'LIKE', "%{$search}%")
                      ->orWhereHas('sale', fn($q) => $q->where('number', 'LIKE', "%{$search}%"));
                });
            })
            ->when($f['statusSunat'], fn($q) => $q->where('status_sunat', $f['statusSunat']))
            // Se filtra por la fecha de emision (la que va en el XML de SUNAT),
            // no por created_at: en 588 documentos no coinciden.
            ->when($f['anio'], fn($q) => $q->whereYear('date', $f['anio']))
            ->when($f['mes'], fn($q) => $q->whereMonth('date', $f['mes']))
            // Por prefijo de serie, no por document_type: ver el comentario de TIPOS.
            ->when($f['tipo'] === 'nota_credito', fn($q) => $q->whereRaw("({$nc})"))
            ->when($f['tipo'] === 'boleta', fn($q) => $q->where('serie', 'LIKE', 'B%')->whereRaw("NOT ({$nc})"))
            ->when($f['tipo'] === 'factura', fn($q) => $q->where('serie', 'LIKE', 'F%')->whereRaw("NOT ({$nc})"));
    }

    /**
     * Totales de TODO el filtro, al margen de la pagina que se este viendo.
     * Solo entran los aceptados por SUNAT; las notas de credito restan.
     */
    public static function resumen(array $filtros): array
    {
        $aceptados = self::filtrar(Document::query(), $filtros)
            ->where('status_sunat', 'aceptado');

        // documents.total es DOUBLE y casi todas las filas traen mas de 2
        // decimales. Se suman los importes YA redondeados para que el pie
        // cuadre con lo que se ve en la tabla y con lo que sale en el Excel.
        $like = self::sqlNotaCredito();

        $totales = function ($query, bool $notasCredito) use ($like) {
            return $query
                ->whereRaw($notasCredito ? "({$like})" : "NOT ({$like})")
                ->selectRaw('COALESCE(SUM(ROUND(total, 2)), 0) as importe, COUNT(*) as cantidad')
                ->first();
        };

        $comprobantes = $totales(clone $aceptados, false);
        $notasCredito = $totales(clone $aceptados, true);

        $importeComprobantes = (float) $comprobantes->importe;
        $importeNotasCredito = (float) $notasCredito->importe;

        return [
            'comprobantes'             => round($importeComprobantes, 2),
            'notas_credito'            => round($importeNotasCredito, 2),
            'neto'                     => round($importeComprobantes - $importeNotasCredito, 2),
            'cantidad_comprobantes'    => (int) $comprobantes->cantidad,
            'cantidad_notas_credito'   => (int) $notasCredito->cantidad,
            'cantidad'                 => (int) $comprobantes->cantidad + (int) $notasCredito->cantidad,
            'excluidos'                => self::filtrar(Document::query(), $filtros)
                ->where(fn($q) => $q->where('status_sunat', '!=', 'aceptado')->orWhereNull('status_sunat'))
                ->count(),
        ];
    }

    /** Etiqueta del periodo filtrado, para el titulo y el nombre del Excel. */
    public static function etiquetaPeriodo(?string $anio, ?string $mes): string
    {
        $nombreMes = $mes ? (self::MESES[$mes] ?? null) : null;

        if ($anio && $nombreMes) {
            return "{$nombreMes} {$anio}";
        }

        if ($anio) {
            return "año {$anio}";
        }

        // Un mes sin año filtra ese mes en todos los años: hay que decirlo.
        if ($nombreMes) {
            return "{$nombreMes} de todos los años";
        }

        return 'todos los periodos';
    }

    /** Anios con documentos emitidos, para el selector. */
    public function getAniosProperty()
    {
        return Document::selectRaw('YEAR(date) as anio')
            ->whereNotNull('date')
            ->distinct()
            ->orderByDesc('anio')
            ->pluck('anio');
    }

    public function limpiarFiltros()
    {
        $this->reset(['search', 'statusSunat', 'anio', 'mes', 'tipo']);
        $this->resetPage();
    }

    /**
     * Salta al otro extremo del par comprobante <-> nota de credito.
     *
     * Limpia los demas filtros a proposito. El de tipo es imprescindible: los
     * grupos son disjuntos y el destino esta siempre en el grupo contrario, asi
     * que sin esto la tabla queda vacia SIEMPRE. El periodo y el estado se
     * limpian porque una nota de credito puede emitirse en otro mes que su
     * comprobante, y el enlace debe cumplir lo que promete.
     */
    public function verComprobante(string $referencia)
    {
        $this->reset(['tipo', 'anio', 'mes', 'statusSunat']);
        $this->search = $referencia;
        $this->resetPage();
    }

    /** Los filtros activos en pantalla, en el formato que esperan filtrar() y resumen(). */
    private function filtrosActivos(): array
    {
        return [
            'search'      => $this->search,
            'statusSunat' => $this->statusSunat,
            'anio'        => $this->anio,
            'mes'         => $this->mes,
            'tipo'        => $this->tipo,
        ];
    }

    public function exportar()
    {
        $url = route('documents.export', array_filter(
            $this->filtrosActivos(),
            fn($v) => $v !== null && $v !== ''
        ));

        $this->dispatch('abrir-nueva-pestania', ['url' => $url]);
    }

    public
    function render()
    {
        // affectedDocument y creditNotes se cargan aqui para poder pintar el par
        // comprobante <-> nota de credito sin una consulta por fila.
        $documents = self::filtrar(
            Document::with(['sale', 'sale.contact:id,name', 'client:id,name', 'affectedDocument', 'creditNotes']),
            $this->filtrosActivos()
        )->orderBy('id', 'desc')->paginate($this->porPagina());

        return view('livewire.documents.table-documents', [
            'documents'         => $documents,
            'resumen'           => self::resumen($this->filtrosActivos()),
            'anios'             => $this->anios,
            'meses'             => self::MESES,
            'tipos'             => self::TIPOS,
            'opcionesPorPagina' => self::OPCIONES_POR_PAGINA,
        ]);
    }

    public const MESES = [
        '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
        '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
        '09' => 'Setiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
    ];
}
