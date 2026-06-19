<?php

namespace App\Livewire\Documents;

use App\Models\Document;
use App\Services\PendingDocumentsService;
use App\Services\SunatService;
use DateTime;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

use Greenter\Model\Response\SummaryResult;
use Greenter\Model\Voided\Voided;
use Greenter\Model\Voided\VoidedDetail;
use Greenter\Ws\Services\SunatEndpoints;

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
    public function document_destroy(Document $document)
    {

        if($document->document_type == 2){
            $document->update([
                'status' => 'anulado',
                'status_sunat' => 'anulado'
            ]);

            return;
        }

        $sunat = new SunatService();


        $anuladosCount = Document::where('status', 'anulado')->count();
        $nextCorrelativo = $anuladosCount + 1;

        $data = [
            "correlative" => $nextCorrelativo,
             'date' => $document->date,
            "details" => [
                [
                    "tipoDoc" => ($document->document_type == '1') ? '01' : '03',
                    "serie" => $document->serie,
                    "correlative" => $document->correlative,
                    "motivoBaja" => 'PRUEBAS DE INTEGRACIÓN',
                ]
            ]
        ];



        $see = $sunat->getSee();

        $voided = $sunat->getVoided($data);

        Log::info("VOIDED: ". json_encode($voided));

        $result = $see->send($voided);

        file_put_contents(storage_path('/xml_path_anulled/' . $voided->getName() . '.xml'), $see->getFactory()->getLastXml());

        $sunatResponse = $sunat->sunatResponse($voided, $result, "voided");

        $pdf_path = "";
        if ($sunatResponse['status'] == 1) {
            $pdf_path = $sunat->generatePDF($voided,'voided');
        }

        if ($sunatResponse['status'] != 1) {
            $this->dispatch('error', ['label' => 'No se puede emitir un comprobante en estos momentos por fallos con sunat, Intentarlo mas tarde.']);
            return;
        }

        Document::find($document->id)->update([
            'status' => "anulado",
            'status_sunat' => ($sunatResponse['status'] == "1") ? "anulado" : "aceptado",
            'xml_path_anulled' => '/xml_path/' . $voided->getName() . '.xml',
            'cdr_path_anulled' => $sunatResponse['cdr'] ?? '',
            'pdf_path_anulled' => $pdf_path,
        ]);

        return;

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
            // Opcional: si quieres validar permisos aquí también
            // abort_unless(auth()->user()?->can('sunat-resend-pending'), 403);

            $service->resendAll();

            $this->dispatch('success', [
                'label' => 'Reenvío de documentos pendientes ejecutado.',
            ]);

            $this->dispatch('success',['label' => 'Reenvío de documentos pendientes ejecutado.','btn' => 'Genial!','route' => route('documents.index')]);
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
