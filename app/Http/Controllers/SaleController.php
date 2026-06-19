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
    public function pdf($id){
        $sale = Sale::find($id);
        $pdf = Pdf::loadView('pdf.invoice',compact('sale'))->setPaper('A4', 'portrait')->setOption('defaultFont', 'DejaVu Sans');
        return $pdf->stream('invoice-' . sprintf('%06d', $id) .'.pdf');
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
        $pdf = SnappyPdf::loadView('pdf.invoice-v2', $data);
        return $pdf->stream('nota-venta-' . $data['sale']->number . '.pdf');
    }

    /** Preview HTML de la nueva plantilla (para iterar el diseño sin generar PDF). */
    public function pdfV2Preview($id)
    {
        return view('pdf.invoice-v2', $this->invoiceV2Data($id));
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
