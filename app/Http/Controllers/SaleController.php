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

        $fontFace = $this->fontFace();

        $qr = $this->qrDataUri(implode('|', [
            $company->ruc ?? '',
            'NV',
            $sale->number,
            number_format($grandTotal, 2, '.', ''),
            $sale->date,
            $sale->client->document_number ?? '',
        ]));

        return compact('sale', 'company', 'opGravadas', 'grandTotal', 'amountInWords', 'logo', 'fontFace', 'qr');
    }

    /** Genera un QR (SVG) embebible como data URI usando bacon/bacon-qr-code. */
    private function qrDataUri(string $content): string
    {
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(220, 1),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $svg = (new \BaconQrCode\Writer($renderer))->writeString($content);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /** @font-face con Poppins embebido en base64 (para que wkhtmltopdf use la tipografia del modelo). */
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
            $css .= "@font-face{font-family:'Poppins';font-style:normal;font-weight:{$weight};"
                  . "src:url('file://{$path}') format('truetype');}";
        }
        return $css;
    }

    /** Nueva nota de venta renderizada con wkhtmltopdf (Snappy). */
    public function pdfV2($id)
    {
        $data = $this->invoiceV2Data($id);
        $data['pdfMode'] = true;

        // Pagina A4 unica con el footer integrado (posicion absoluta).
        // disable-smart-shrinking + dpi 96 => ocupa todo el ancho a escala real.
        $output = SnappyPdf::loadView('pdf.invoice-v2', $data)
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
