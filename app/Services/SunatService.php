<?php

namespace App\Services;

use DateTime;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Response\SummaryResult;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Model\Voided\Voided;
use Greenter\Model\Voided\VoidedDetail;
use Greenter\Report\XmlUtils;
use Greenter\See;
use Greenter\Ws\Services\ConsultCdrService;
use Greenter\Ws\Services\SoapClient;
use Greenter\Ws\Services\SunatEndpoints;
use Greenter\XMLSecLibs\Certificate\X509Certificate;
use Greenter\XMLSecLibs\Certificate\X509ContentType;
use Illuminate\Support\Facades\Log;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Luecano\NumeroALetras\NumeroALetras;
use App\Models\Setting;
use App\Models\Client as ClientModel;

class SunatService
{
    public function getSee()
    {
        $see = new See();

        Log::info("PATH CERTIFICATE: " . config('sunat.path_certificate'));
        Log::info("SUNAT RUC SOL: " . config('sunat.ruc_sol'));
        Log::info("RUC: : " . config('sunat.ruc'));

        // Si usas PFX:
        // $pfx = file_get_contents(storage_path(config('sunat.path_certificate')));
        // $password = '...';
        // $certificate = new X509Certificate($pfx, $password);
        // $see->setCertificate($certificate->export(X509ContentType::PEM));

        $see->setCertificate(file_get_contents(storage_path(config('sunat.path_certificate'))));
        $see->setService((env('APP_ENV') == "production") ? SunatEndpoints::FE_PRODUCCION : SunatEndpoints::FE_BETA);
        $see->setClaveSOL(config('sunat.ruc_sol'), config('sunat.usuario_sol'), config('sunat.clave_sol'));

        return $see;
    }

    public function getInvoice($data)
    {
        return (new Invoice())
            ->setUblVersion('2.1')
            ->setTipoOperacion('0101') // Venta - Catalog. 51
            ->setTipoDoc($data['tipoDoc']) // Factura/Boleta - Catalog. 01 (01/03)
            ->setSerie($data['serie'])
            ->setCorrelativo($data['correlative'])
            ->setFechaEmision(new DateTime($data['date']))
            ->setFormaPago(new FormaPagoContado())
            ->setTipoMoneda('PEN')
            ->setCompany($this->getCompany())
            ->setClient($this->getClient($data['client']))
            ->setMtoOperGravadas($data['subtotal'])
            ->setMtoIGV($data['igv'])
            ->setTotalImpuestos($data['igv'])
            ->setValorVenta($data['subtotal'])
            ->setSubTotal($data['total'])
            ->setMtoImpVenta($data['total'])
            ->setDetails($this->getDetails($data['items']))
            ->setLegends([$this->getLegends($data['legend'])]);
    }

    public function getVoided($data)
    {
        return (new Voided())
            ->setCorrelativo($data['correlative'])
            ->setFecGeneracion(new DateTime($data['date']))
            ->setFecComunicacion(new DateTime())
            ->setCompany($this->getCompany())
            ->setDetails($this->getVoidedDetails($data['details']));
    }

    public function getVoidedDetails($details = [])
    {
        $greenDetails = [];

        foreach ($details as $detail) {
            $greenDetails[] = (new VoidedDetail())
                ->setTipoDoc($detail['tipoDoc'])
                ->setSerie($detail['serie'])
                ->setCorrelativo($detail['correlative'])
                ->setDesMotivoBaja($detail['motivoBaja']);
        }

        return $greenDetails;
    }

    public function getClient($client)
    {
        return (new Client())
            ->setTipoDoc($client['tipoDoc'])
            ->setNumDoc($client['numDoc'])
            ->setRznSocial($client['name']);
    }

    public function getCompany()
    {
        return (new Company())
            ->setRuc(config('sunat.ruc'))
            ->setRazonSocial(config('sunat.razon_social'))
            ->setNombreComercial(config('sunat.nombre_comercial'))
            ->setAddress($this->getAddress());
    }

    public function getAddress()
    {
        return (new Address())
            ->setUbigueo('150101')
            ->setDepartamento('LIMA')
            ->setProvincia('LIMA')
            ->setDistrito('SANTIAGO DE SURCO')
            ->setUrbanizacion('-')
            ->setDireccion('CAL. ARTESANOS 150 URB. ALBORADA INT. 205 C.C LAS PLAZUELAS')
            ->setCodLocal('0000');
    }

    public function getDetails($details = [])
    {
        $greenDetails = [];

        foreach ($details as $detail) {
            $totalIgv = (float)$detail['total'] * 0.18;

            $greenDetails[] = (new SaleDetail())
                ->setCodProducto($detail['sku'] ?? '')
                ->setUnidad('NIU') // Unidad - Catalog. 03
                ->setCantidad($detail['quantity'])
                ->setMtoValorUnitario($detail['price'])
                ->setDescripcion($detail['title'])
                ->setMtoBaseIgv($detail['total'])
                ->setPorcentajeIgv(18.00)
                ->setIgv($totalIgv)
                ->setTipAfeIgv('10') // Gravado Op. Onerosa - Catalog. 07
                ->setTotalImpuestos($totalIgv)
                ->setMtoValorVenta($detail['total'])
                ->setMtoPrecioUnitario(((float)$detail['price'] * 0.18) + (float)$detail['price']);
        }

        return $greenDetails;
    }

    public function getLegends($legend)
    {
        return (new Legend())
            ->setCode('1000')
            ->setValue($legend);
    }

    /**
     * Maneja respuesta de SUNAT (invoice o voided).
     * Devuelve:
     *  - status: 1 (aceptado), 2 (observado), 0 (error)
     *  - code: código SUNAT / error (incluye 1032, HTTP, etc.)
     *  - cdr: ruta del CDR, si aplica
     *  - notes: array de notas / mensajes
     */
    public function sunatResponse($invoice, $result, $type = "invoice")
    {
        // ==============================
        // SIMULACIÓN DE ERROR HTTP
        // ==============================
        if (env('SUNAT_SIMULATE_HTTP_PENDING', false)) {
            Log::warning("Simulando error HTTP para pruebas (SUNAT_SIMULATE_HTTP_PENDING=true)");

            return [
                'status' => 0,
                'code'   => 'HTTP_SIMULATED',               // contiene 'HTTP' => entra en tu stripos(...)
                'cdr'    => null,
                'notes'  => ['Error HTTP simulado para pruebas de documento pendiente.'],
            ];
        }

        $response = [];

        // Error a nivel de envío (HTTP, credenciales, etc.)
        if (!$result->isSuccess()) {
            $error = $result->getError();
            $code  = $error ? $error->getCode() : null;
            $msg   = $error ? $error->getMessage() : null;

            Log::info("--- START: Log comprobante (ERROR) " . $invoice->getName() . " ---");
            Log::info("FACTURA ERROR: " . $code);
            Log::info("MENSAJE ERROR: " . $msg);
            Log::info("--- END: log comprobante (ERROR) " . $invoice->getName() . " ---");

            $response['status'] = 0;
            $response['code']   = $code; // Puede ser 'HTTP', 1032, etc.
            $response['cdr']    = null;
            $response['notes']  = $msg ? [$msg] : [];

            return $response;
        }

        // Si es un Voided o Summary, obtener estado vía ticket
        if ($type !== "invoice") {
            $see    = $this->getSee();
            $ticket = $result->getTicket();
            $result = $see->getStatus($ticket);

            if (!$result->isSuccess()) {
                $error = $result->getError();

                Log::info("ERROR AL ANULAR COMPROBANTE: " . json_encode($error));

                $response['status'] = 0;
                $response['code']   = $error ? $error->getCode() : null;
                $response['cdr']    = null;
                $response['notes']  = [$error ? $error->getMessage() : null];

                return $response;
            }
        }

        $cdr = $result->getCdrResponse();

        if ($type === "invoice") {
            file_put_contents(
                storage_path('/cdr_path/R-' . $invoice->getName() . '.zip'),
                $result->getCdrZip()
            );
        } else {
            file_put_contents(
                storage_path('/cdr_path_anulled/R-' . $invoice->getName() . '.zip'),
                $result->getCdrZip()
            );
        }

        $code = (int)$cdr->getCode();

        Log::info("CODE: " . $code);

        if ($code === 0) {
            if (count($cdr->getNotes()) > 0) {
                Log::info("--- START: Log comprobante notes " . $invoice->getName() . " ---");
                Log::info($cdr->getNotes());
                Log::info("--- END: Log comprobante notes " . $invoice->getName() . " ---");
            }

            $response['status'] = 1;
            $response['code']   = $code;
            $response['cdr']    = '/cdr_path/R-' . $invoice->getName() . '.zip';
            $response['notes']  = (count($cdr->getNotes()) > 0) ? $cdr->getNotes() : [];

            Log::info("--- START: Log comprobante 0 " . json_encode($response) . " ---");
        } elseif ($code >= 2000 && $code <= 3999) {
            Log::info("--- START: Log comprobante >= 2000 && <= 3999 " . $invoice->getName() . " ---");
            Log::info("Code: " . $code);
            Log::info($cdr->getNotes());
            Log::info("--- END: Log comprobante >= 2000 && <= 3999 " . $invoice->getName() . " ---");

            $response['status'] = 2;
            $response['code']   = $code;
            $response['cdr']    = '/cdr_path/R-' . $invoice->getName() . '.zip';
            $response['notes']  = (count($cdr->getNotes()) > 0) ? $cdr->getNotes() : [];
        } else {
            Log::info("--- START: Log comprobante ELSE " . $invoice->getName() . " ---");
            Log::info("Code: " . $code);
            Log::info($cdr->getNotes());
            Log::info("--- END: Log comprobante ELSE " . $invoice->getName() . " ---");

            $response['status'] = 0;
            $response['code']   = $code;
            $response['cdr']    = '/cdr_path/R-' . $invoice->getName() . '.zip';
            $response['notes']  = (count($cdr->getNotes()) > 0) ? $cdr->getNotes() : [];
        }

        return $response;
    }

    /**
     * Consulta en SUNAT si un comprobante ya existe y devuelve su CDR.
     * Usa el endpoint de consulta separado (FE_CONSULTA_CDR).
     */
    public function consultarCdr(string $tipoDoc, string $serie, int $correlativo): \Greenter\Model\Response\StatusCdrResult
    {
        $client = new SoapClient();
        $client->setService(
            (env('APP_ENV') === 'production')
                ? SunatEndpoints::FE_CONSULTA_CDR
                : 'https://e-beta.sunat.gob.pe/ol-it-wsconscpegem/billConsultService'
        );
        $client->setCredentials(
            config('sunat.ruc_sol') . config('sunat.usuario_sol'),
            config('sunat.clave_sol')
        );

        $consulta = new ConsultCdrService();
        $consulta->setClient($client);

        Log::info("Consultando CDR en SUNAT: tipo={$tipoDoc} serie={$serie} correlativo={$correlativo}");

        return $consulta->getStatusCdr(config('sunat.ruc'), $tipoDoc, $serie, $correlativo);
    }

    /**
     * Genera el PDF del comprobante con la matriz de diseno (SnappyPdf + blade).
     * Mantiene la firma usada por todos los call sites: generatePdf($invoice, $type).
     *
     * @param mixed  $invoice  Objeto Greenter (Invoice/Note) o Voided.
     * @param string $type     'invoice' (factura/boleta/nota de credito) o 'voided'.
     * @param array  $extra    Datos opcionales (p.ej. para nota de credito: affected, reason).
     */
    public function generatePdf($invoice, $type = 'invoice', array $extra = [])
    {
        if ($type === 'voided') {
            return $this->renderVoidedPdf($invoice);
        }

        $data = $this->buildComprobanteData($invoice, $extra);
        $html = view('pdf.sunat-document', $data)->render();

        $output = SnappyPdf::loadHTML($html)
            ->setOption('page-size', 'A4')
            ->setOption('margin-top', 0)
            ->setOption('margin-bottom', 0)
            ->setOption('margin-left', 0)
            ->setOption('margin-right', 0)
            ->setOption('dpi', 96)
            ->setOption('disable-smart-shrinking', true)
            ->output();

        $path = '/pdf_path/' . $invoice->getName() . '.pdf';
        file_put_contents(storage_path($path), $output);

        return $path;
    }

    /** Construye el array normalizado para la plantilla a partir del objeto Greenter. */
    private function buildComprobanteData($invoice, array $extra = []): array
    {
        $serie       = $invoice->getSerie();
        $correlativo = $invoice->getCorrelativo();
        $tipoDoc     = $invoice->getTipoDoc();
        $gCompany    = $invoice->getCompany();
        $gClient     = $invoice->getClient();

        // Titulo segun tipo (FC/BC = nota de credito)
        $isNote = str_starts_with($serie, 'FC') || str_starts_with($serie, 'BC');
        if ($isNote) {
            $title   = 'Nota de crédito electrónica';
            $repText = 'NOTA DE CRÉDITO ELECTRÓNICA';
        } elseif ($tipoDoc === '01') {
            $title   = 'Factura electrónica';
            $repText = 'FACTURA ELECTRÓNICA';
        } else {
            $title   = 'Boleta de venta electrónica';
            $repText = 'BOLETA DE VENTA ELECTRÓNICA';
        }

        $number    = $serie . '-' . str_pad((string) $correlativo, 8, '0', STR_PAD_LEFT);
        $fecha     = $invoice->getFechaEmision();
        $fechaYmd  = $fecha->format('Y-m-d');
        $fechaTxt  = \Carbon\Carbon::parse($fechaYmd)->locale('es')->isoFormat('D [de] MMMM [de] YYYY');

        // Totales
        $opGravadas = (float) $invoice->getMtoOperGravadas();
        $igv        = (float) $invoice->getMtoIGV();
        $total      = (float) $invoice->getMtoImpVenta();

        // Items
        $items = [];
        foreach ($invoice->getDetails() as $det) {
            $items[] = [
                'desc'  => $det->getDescripcion(),
                'code'  => $det->getCodProducto(),
                'qty'   => (float) $det->getCantidad(),
                'price' => (float) $det->getMtoValorUnitario(),
                'total' => (float) $det->getMtoValorVenta(),
            ];
        }

        // Monto en letras (desde legend de Greenter o recalculado)
        $legends = $invoice->getLegends();
        $words   = (is_array($legends) && count($legends) && $legends[0]->getValue())
            ? $legends[0]->getValue()
            : (new NumeroALetras())->toInvoice($total, 2, 'SOLES');

        // Hash real del XML firmado (para el QR SUNAT)
        $xmlPath = storage_path('/xml_path/' . $invoice->getName() . '.xml');
        $hash    = is_file($xmlPath) ? (new XmlUtils())->getHashSignFromFile($xmlPath) : '';

        // QR formato SUNAT: RUC|tipoDoc|serie|correlativo|IGV|Total|fecha|tipoDocCli|numDocCli|hash
        $qrString = implode('|', [
            $gCompany->getRuc(),
            $tipoDoc,
            $serie,
            $correlativo,
            number_format($igv, 2, '.', ''),
            number_format($total, 2, '.', ''),
            $fechaYmd,
            $gClient->getTipoDoc(),
            $gClient->getNumDoc(),
            $hash,
        ]);

        // Empresa: RUC/razon social/direccion del comprobante; contacto desde Setting
        $setting = Setting::first();
        $addr    = $gCompany->getAddress();
        $company = [
            'name'    => $gCompany->getRazonSocial(),
            'ruc'     => $gCompany->getRuc(),
            'address' => $addr ? $addr->getDireccion() : ($setting->address ?? ''),
            'city'    => $addr ? $addr->getDistrito() : ($setting->city ?? ''),
            'country' => 'Perú',
            'phone'   => $setting->phone ?? '',
            'email'   => $setting->email ?? '',
        ];

        // Cliente: direccion desde el modelo (el objeto Greenter no la trae)
        $clientModel = ClientModel::where('document_number', $gClient->getNumDoc())->first();
        $client = [
            'name'         => $gClient->getRznSocial(),
            'docTypeLabel' => $this->docTypeLabel($gClient->getTipoDoc()),
            'docNumber'    => $gClient->getNumDoc(),
            'address'      => $clientModel->address ?? '',
        ];

        return [
            'logo'          => $this->pdfLogo(),
            'fontFace'      => $this->pdfFontFace(),
            'qr'            => $this->qrDataUri($qrString),
            'title'         => $title,
            'repText'       => $repText,
            'number'        => $number,
            'fechaTexto'    => $fechaTxt,
            'currencyLabel' => 'SOLES',
            'c'             => $company,
            'client'        => $client,
            'items'         => $items,
            'opGravadas'    => $opGravadas,
            'igv'           => $igv,
            'total'         => $total,
            'amountInWords' => $words,
            'payCondition'  => 'Contado',
            'affected'      => $extra['affected'] ?? null,
            'reason'        => $extra['reason'] ?? null,
        ];
    }

    private function docTypeLabel(?string $code): string
    {
        return [
            '1' => 'DNI',
            '6' => 'RUC',
            '4' => 'Carnet de extranjería',
            '7' => 'Pasaporte',
            '0' => 'Doc. no domiciliado',
        ][$code] ?? 'Documento';
    }

    /** Logo embebido (data URI). */
    private function pdfLogo(): string
    {
        return 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/logo.png')));
    }

    /** QR (SVG) embebible con bacon/bacon-qr-code. */
    private function qrDataUri(string $content): string
    {
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(220, 1),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $svg = (new \BaconQrCode\Writer($renderer))->writeString($content);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /** @font-face Poppins por file:// (igual que la nota de venta). */
    private function pdfFontFace(): string
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

    /** Comunicacion de baja renderizada con la matriz de diseno. */
    private function renderVoidedPdf($voided): string
    {
        $gCompany = $voided->getCompany();
        $setting  = Setting::first();
        $addr     = $gCompany->getAddress();

        $details = [];
        foreach ($voided->getDetails() as $det) {
            $tipo = $det->getTipoDoc() === '01' ? 'Factura' : ($det->getTipoDoc() === '03' ? 'Boleta' : $det->getTipoDoc());
            $details[] = [
                'tipo'      => $tipo,
                'documento' => $det->getSerie() . '-' . str_pad((string) $det->getCorrelativo(), 8, '0', STR_PAD_LEFT),
                'motivo'    => $det->getDesMotivoBaja(),
            ];
        }

        $data = [
            'logo'     => $this->pdfLogo(),
            'fontFace' => $this->pdfFontFace(),
            'title'    => 'Comunicación de baja',
            'number'   => $voided->getName(),
            'fechaGen' => \Carbon\Carbon::parse($voided->getFecGeneracion()->format('Y-m-d'))->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
            'fechaCom' => \Carbon\Carbon::parse($voided->getFecComunicacion()->format('Y-m-d'))->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
            'c'        => [
                'name'    => $gCompany->getRazonSocial(),
                'ruc'     => $gCompany->getRuc(),
                'address' => $addr ? $addr->getDireccion() : ($setting->address ?? ''),
                'city'    => $addr ? $addr->getDistrito() : ($setting->city ?? ''),
                'country' => 'Perú',
                'phone'   => $setting->phone ?? '',
                'email'   => $setting->email ?? '',
            ],
            'details'  => $details,
        ];

        $html = view('pdf.sunat-voided', $data)->render();

        $output = SnappyPdf::loadHTML($html)
            ->setOption('page-size', 'A4')
            ->setOption('margin-top', 0)
            ->setOption('margin-bottom', 0)
            ->setOption('margin-left', 0)
            ->setOption('margin-right', 0)
            ->setOption('dpi', 96)
            ->setOption('disable-smart-shrinking', true)
            ->output();

        $path = '/pdf_path_anulled/' . $voided->getName() . '.pdf';
        file_put_contents(storage_path($path), $output);

        return $path;
    }
}
