<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('documents.index');
    }

    public function creditNote(string $id)
    {
        return view('documents.credit-note', compact('id'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function download(string $path)
    {
        $fullPath = storage_path($path);
        return response()->download($fullPath);
    }

    /**
     * Reintenta el envío a SUNAT de un documento pendiente.
     * Delegado a PendingDocumentsService, que maneja 1032, consulta de CDR y
     * actualización del documento (el flujo completo del reenvío masivo).
     */
    public function retry(Document $document, \App\Services\PendingDocumentsService $service)
    {
        if ($document->status_sunat !== 'pendiente') {
            return response()->json([
                'status'  => 'ignorado',
                'message' => "El documento {$document->serie}-{$document->correlative} no está pendiente (estado: {$document->status_sunat}).",
            ], 422);
        }

        try {
            $service->resend($document);
        } catch (\Throwable $e) {
            Log::error("Error en retry del documento ID {$document->id}: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Ocurrió un error reenviando el documento a SUNAT.',
            ], 500);
        }

        $document->refresh();

        return response()->json([
            'status'      => $document->status_sunat,
            'comprobante' => "{$document->serie}-{$document->correlative}",
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return view('documents.show', compact('id'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
