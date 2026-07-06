<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\Setting;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Luecano\NumeroALetras\NumeroALetras;

class QuotationController extends Controller
{
    public function index()
    {
        return view('quotations.index');
    }

    public function create()
    {
        return view('quotations.create');
    }

    /** Datos compartidos por la plantilla (PDF wkhtmltopdf y preview HTML). */
    private function quotationPdfData($id): array
    {
        $quotation = Quotation::with(['client', 'user', 'quotationDetails.article'])->findOrFail($id);

        $company = Setting::first();

        $amountInWords = (new NumeroALetras())->toInvoice((float) $quotation->total, 2, 'SOLES');

        $logo = 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/logo.png')));

        $fontFace = $this->fontFace();

        return compact('quotation', 'company', 'amountInWords', 'logo', 'fontFace');
    }

    /** @font-face con Poppins embebido en base64 (misma matriz que la nota de venta). */
    private function fontFace(): string
    {
        $dir = resource_path('fonts/pdf');
        $faces = [
            [400, 'Poppins-Regular.ttf'],
            [500, 'Poppins-Medium.ttf'],
            [600, 'Poppins-SemiBold.ttf'],
            [700, 'Poppins-Bold.ttf'],
        ];
        $css = '';
        foreach ($faces as [$weight, $file]) {
            $path = $dir . '/' . $file;
            if (!is_file($path)) {
                continue;
            }
            $b64 = base64_encode(file_get_contents($path));
            $css .= "@font-face{font-family:'Poppins';font-style:normal;font-weight:{$weight};"
                  . "src:url(data:font/truetype;base64,{$b64}) format('truetype');}";
        }
        return $css;
    }

    /** Cotización renderizada con wkhtmltopdf (Snappy), misma matriz que la nota de venta. */
    public function pdf($id)
    {
        $data = $this->quotationPdfData($id);

        $output = SnappyPdf::loadView('pdf.quotation', $data)
            ->setOption('page-size', 'A4')
            ->setOption('margin-top', 0)
            ->setOption('margin-bottom', 0)
            ->setOption('margin-left', 0)
            ->setOption('margin-right', 0)
            ->setOption('dpi', 96)
            ->setOption('disable-smart-shrinking', true)
            ->output();

        return response($output, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="cotizacion-' . $data['quotation']->number . '.pdf"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'              => 'no-cache',
        ]);
    }

    /** Preview HTML de la plantilla (para iterar el diseño sin generar PDF). */
    public function pdfPreview($id)
    {
        return view('pdf.quotation', $this->quotationPdfData($id));
    }
}
