<?php

namespace App\Livewire\Documents;

use App\Models\Article;
use App\Models\Document;
use App\Models\Sale;
use App\Services\PendingDocumentsService;
use App\Services\SunatService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class TableDocuments extends Component
{
    public $search;
    public $statusSunat = '';
    use WithPagination;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusSunat()
    {
        $this->resetPage();
    }

    public function rendered(){
        $this->dispatch('reinit-tippy');
    }

    #[On('document_destroy')]
    public function document_destroy(Document $document, $motive = null)
    {
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

            $ventaAnulada = $this->anularVentaPorBaja($document);

            $label = 'El comprobante fue anulado. Como SUNAT no lo había aceptado, no fue necesario comunicar la baja.';
            if ($ventaAnulada) {
                $label .= ' La venta asociada fue anulada y el stock repuesto.';
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

        // FC/BC = nota de crédito (tipoDoc 07); si no, factura (01) o boleta (03)
        $esNotaCredito = str_starts_with($document->serie, 'FC') || str_starts_with($document->serie, 'BC');
        $tipoDoc = $esNotaCredito ? '07' : (($document->document_type == 1) ? '01' : '03');

        try {
            // Facturas y sus notas → Comunicación de Baja (RA).
            // Boletas y sus notas → Resumen Diario (RC) con estado 3.
            if ($document->document_type == 1) {
                $this->anularConComunicacionDeBaja($document, $tipoDoc, $motive);
            } else {
                $this->anularConResumenDiario($document, $tipoDoc, $motive, $esNotaCredito);
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

    private function anularConComunicacionDeBaja(Document $document, string $tipoDoc, string $motive): void
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

        $this->finishAnulacion($document, $voided, $sunatResponse, 'voided', $motive);
    }

    private function anularConResumenDiario(Document $document, string $tipoDoc, string $motive, bool $esNotaCredito): void
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

        $this->finishAnulacion($document, $summary, $sunatResponse, 'summary', $motive);
    }

    private function finishAnulacion(Document $document, $comprobante, array $sunatResponse, string $type, string $motive): void
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
        $ventaAnulada = $this->anularVentaPorBaja($document);

        $label = 'El comprobante fue anulado y la baja fue aceptada por SUNAT.';
        if ($ventaAnulada) {
            $label .= ' La venta asociada fue anulada y el stock repuesto.';
        }

        $this->dispatch('successNotRoute', ['label' => $label]);
    }

    /**
     * La baja de un comprobante de venta anula también la venta asociada y
     * repone su stock, salvo que la venta ya esté anulada o una nota de
     * crédito ya lo haya repuesto. Devuelve true si anuló la venta.
     */
    private function anularVentaPorBaja(Document $document): bool
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

        DB::transaction(function () use ($sale, $document, $stockYaRepuesto) {
            if (!$stockYaRepuesto) {
                foreach ($sale->saleDetails as $item) {
                    Article::find($item->article_id)?->increment('stock', (int) $item->quantity);
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
        $this->dispatch('document_delete', ['label' => 'Esta seguro que desea anular el comprobante?.', 'btn' => 'Anular', 'id' => $id]);
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

    public
    function render()
    {
        $documents = Document::with('sale')
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->whereRaw("CONCAT(serie, '-', correlative) LIKE ?", ["%{$this->search}%"])
                      ->orWhere('serie', 'LIKE', "%{$this->search}%")
                      ->orWhere('correlative', 'LIKE', "%{$this->search}%")
                      ->orWhereHas('sale', fn($q) => $q->where('number', 'LIKE', "%{$this->search}%"));
                });
            })
            ->when($this->statusSunat, fn($q) => $q->where('status_sunat', $this->statusSunat))
            ->orderBy('id', 'desc')->paginate(10);
        return view('livewire.documents.table-documents', compact('documents'));
    }
}
