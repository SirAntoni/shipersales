<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Luecano\NumeroALetras\NumeroALetras;

class SaleController extends Controller implements hasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(\Spatie\Permission\Middleware\PermissionMiddleware::using('update'), only:['show']),
        ];
    }

    public function index()
    {
        return view('sales.index');
    }

    public function create()
    {
        return view('sales.create');
    }

    public function show(string $id)
    {
        return view('sales.show',compact('id'));
    }
    /** Ver PDF de la venta (nuevo diseno con wkhtmltopdf). */
    public function pdf($id){
        return $this->pdfV2($id);
    }

    /**
     * Datos compartidos por la nueva plantilla (PDF wkhtmltopdf y preview HTML).
     */
    private function invoiceV2Data($id): array
    {
        $sale = Sale::with(['client', 'user', 'paymentMethod', 'saleDetails.article'])->findOrFail($id);

        $company = Setting::first();

        $opGravadas = (float) ($sale->subtotal ?? ($sale->total - $sale->tax));
        $grandTotal = (float) $sale->total + (float) ($sale->delivery == 1 ? $sale->delivery_fee : 0);

        $amountInWords = (new NumeroALetras())->toInvoice($grandTotal, 2, 'SOLES');

        $logo = 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/logo.png')));

        return compact('sale', 'company', 'opGravadas', 'grandTotal', 'amountInWords', 'logo');
    }

    /** Nueva nota de venta renderizada con wkhtmltopdf (Snappy). */
    public function pdfV2($id)
    {
        $data = $this->invoiceV2Data($id);
        $data['pdfMode'] = true;

        // El footer se inyecta con --footer-html para que wkhtmltopdf lo ancle al pie de cada pagina.
        $footerFile = storage_path('app/snappy-footer-' . $id . '-' . uniqid() . '.html');
        file_put_contents($footerFile, view('pdf.invoice-v2-footer', $data)->render());

        try {
            $output = SnappyPdf::loadView('pdf.invoice-v2', $data)
                ->setOption('footer-html', $footerFile)
                ->setOption('margin-bottom', 30)   // mm: debe coincidir con height del footer (30mm)
                ->setOption('footer-spacing', 0)
                ->output();
        } finally {
            @unlink($footerFile);
        }

        return response($output, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="nota-venta-' . $data['sale']->number . '.pdf"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'              => 'no-cache',
        ]);
    }

    /** Preview HTML de la nueva plantilla (para iterar el diseño sin generar PDF). */
    public function pdfV2Preview($id)
    {
        $data = $this->invoiceV2Data($id);
        $data['pdfMode'] = false;
        return view('pdf.invoice-v2', $data);
    }

    public function commissions()
    {
        return view('sales.commissions');
    }

    public function returns()
    {
        return view('sales.returns');
    }
}
