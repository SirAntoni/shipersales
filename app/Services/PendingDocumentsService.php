<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Voucher;
use Illuminate\Support\Facades\Log;
use Luecano\NumeroALetras\NumeroALetras;

class PendingDocumentsService
{
    public function __construct(
        protected SunatService $sunat
    ) {}

    /**
     * Reenvía todos los documentos con status_sunat = 'pendiente'.
     */
    public function resendAll(): void
    {
        $pending = Document::with(['client', 'documentDetails.article'])
            ->where('status_sunat', 'pendiente')
            ->get();

        Log::info("Reenvío SUNAT: encontrados {$pending->count()} documentos pendientes.");

        foreach ($pending as $document) {
            try {
                $this->resendSingle($document);
            } catch (\Throwable $e) {
                Log::error("Error re-enviando documento ID {$document->id}: {$e->getMessage()}", [
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }

    /**
     * Reenvía un documento puntual.
     */
    protected function resendSingle(Document $document): void
    {
        $client = $document->client;

        if (!$client) {
            Log::warning("Documento ID {$document->id} sin cliente asociado. Se omite reenvío.");
            return;
        }

        // Mapeo igual que en NewDocument
        $tiposIdentidad = [
            '0' => 'NO DOMICILIADO',
            '1' => 'DNI',
            '4' => 'CE',
            '6' => 'RUC',
            '7' => 'PASAPORTE',
        ];
        $inverseTipos = array_flip($tiposIdentidad); // 'DNI' => '1', 'RUC' => '6', etc.

        $textoDoc          = $client->document_type;              // DNI / RUC / CE / PASAPORTE
        $tipoDocIdentidad  = $inverseTipos[$textoDoc] ?? '0';     // fallback NO DOMICILIADO

        // Legend (monto en letras) desde el total del documento
        $formatter = new NumeroALetras();
        $legend    = $formatter->toInvoice($document->total, 2, 'SOLES');

        // Fecha: puede venir como string o como Carbon
        $dateValue = $document->date;

        if ($dateValue instanceof \Carbon\CarbonInterface) {
            $dateString = $dateValue->format('Y-m-d');
        } else {
            // Si es string, nos quedamos con los primeros 10 caracteres (YYYY-MM-DD)
            $dateString = $dateValue ? substr((string) $dateValue, 0, 10) : now()->format('Y-m-d');
        }

        // Items desde document_details
        $items = $this->buildItemsFromDocument($document);

        $data = [
            'serie'       => $document->serie,
            'correlative' => $document->correlative,
            'date'        => $dateString,
            'tipoDoc'     => ($document->document_type == '1') ? '01' : '03',
            'subtotal'    => $document->subtotal,
            'igv'         => $document->tax,
            'total'       => $document->total,
            'client'      => [
                'tipoDoc' => $tipoDocIdentidad,
                'numDoc'  => $client->document_number,
                'name'    => $client->name,
                'address' => $client->address,
            ],
            'items'  => $items,
            'legend' => $legend,
        ];

        Log::info("Reenvío SUNAT documento ID {$document->id} - data: " . json_encode($data));

        $see     = $this->sunat->getSee();
        $invoice = $this->sunat->getInvoice($data);

        $result   = $see->send($invoice);
        $lastXml  = $see->getFactory()->getLastXml();
        $xmlPath  = '/xml_path/' . $invoice->getName() . '.xml';
        file_put_contents(storage_path($xmlPath), $lastXml);

        $sunatResponse = $this->sunat->sunatResponse($invoice, $result);

        // === 1) Manejo de 1032 también en reenvío ===
        if ($sunatResponse['status'] != 1 && (string)($sunatResponse['code'] ?? '') === '1032') {
            Log::warning("SUNAT 1032 en reenvío documento ID {$document->id}. Reintentando con nuevo correlativo.");

            // Nuevo correlativo y actualización en BD
            $newCorrelative        = Voucher::nextCorrelativeById($document->document_type);
            $document->correlative = $newCorrelative;
            $document->save();

            $data['correlative'] = $newCorrelative;

            $invoice = $this->sunat->getInvoice($data);
            $result  = $see->send($invoice);

            $lastXml = $see->getFactory()->getLastXml();
            $xmlPath = '/xml_path/' . $invoice->getName() . '.xml';
            file_put_contents(storage_path($xmlPath), $lastXml);

            $sunatResponse = $this->sunat->sunatResponse($invoice, $result);
        }

        // === 2) Si aún no es aceptado, seguimos dejándolo como pendiente ===
        if ($sunatResponse['status'] != 1) {
            Log::warning("Documento ID {$document->id} sigue pendiente/observado al reenviar.", [
                'code'  => $sunatResponse['code'] ?? null,
                'notes' => $sunatResponse['notes'] ?? null,
            ]);

            $document->update([
                'notes' => $sunatResponse['notes'] ?? [],
                // podrías cambiar status_sunat a algo tipo 'error' si quisieras diferenciar
                // 'status_sunat' => 'pendiente',
            ]);

            return;
        }

        // === 3) Caso OK en reenvío: lo marcamos como enviado/aceptado ===
        $pdfPath = $this->sunat->generatePdf($invoice);

        $document->update([
            'estado'       => 'enviado',
            'status_sunat' => 'aceptado',
            'xml_path'     => $xmlPath,
            'cdr_path'     => $sunatResponse['cdr'] ?? '',
            'pdf_path'     => $pdfPath,
            'notes'        => $sunatResponse['notes'] ?? [],
        ]);

        Log::info("Documento ID {$document->id} reenviado y marcado como aceptado por SUNAT.");
    }

    /**
     * Reconstruir items para Greenter a partir de document_details.
     */
    protected function buildItemsFromDocument(Document $document): array
    {
        $items = [];

        foreach ($document->documentDetails as $detail) {
            $article = $detail->article;

            if (!$article) {
                Log::warning("Detalle ID {$detail->id} sin artículo en documento ID {$document->id}");
                continue;
            }

            // En NewDocument, Greenter recibe 'total' como SUBTOTAL (sin IGV).
            // En document_details.total guardaste total con IGV.
            // Recuperamos el subtotal aproximado:
            $subtotal = round($detail->total / 1.18, 2);

            $items[] = [
                'sku'      => $article->sku ?? '',
                'quantity' => $detail->quantity,
                'price'    => $detail->price,
                'title'    => $article->title,
                'total'    => $subtotal,
            ];
        }

        return $items;
    }
}
