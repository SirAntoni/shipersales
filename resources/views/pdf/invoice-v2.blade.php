<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8" />
<title>Nota de venta - {{ $sale->number }}</title>
<style>
    {!! $fontFace ?? '' !!}

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: 'Poppins', Arial, Helvetica, sans-serif;
        color: #11102a;
        font-size: 11px;
        background: #ffffff;
        -webkit-print-color-adjust: exact;
    }

    .invoice {
        position: relative;
        width: 210mm;
        height: 296mm;
        background: #fff;
        padding: 16mm 9mm 0;
        overflow: hidden;
    }

    /* ---- barras decorativas (sin gradientes: wkhtmltopdf no los soporta bien) ---- */
    .topbar {
        position: absolute; top: 0; left: 0; right: 0;
        height: 15px;
        background: #3d079d;
        border-bottom-left-radius: 14px;
        border-bottom-right-radius: 14px;
        z-index: 2;
    }
    .topbar .orange {
        position: absolute; top: 0; right: 0;
        width: 13%; height: 15px;
        background: #ff7900;
        border-bottom-right-radius: 14px;
    }

    .footer-strip {
        position: absolute; left: 0; right: 0; bottom: 0;
        height: 32px;
        background: #3d079d;
        color: #fff;
        text-align: center;
        line-height: 32px;
        font-weight: 700;
        font-size: 13px;
        border-top-left-radius: 14px;
        border-top-right-radius: 14px;
        z-index: 2;
    }
    .footer-strip .orange {
        position: absolute; top: 0; right: 0;
        width: 13%; height: 32px;
        background: #ff7900;
        border-top-right-radius: 14px;
    }

    /* ---- header ---- */
    table.header { width: 100%; border-collapse: collapse; }
    table.header > tbody > tr > td { vertical-align: top; }
    .seller-col { padding-right: 10mm; }
    .doc-col { width: 100mm; }

    .brand td { vertical-align: middle; }
    .brand .logo-img { width: 46px; height: 46px; }
    .brand-name {
        padding-left: 9px;
        font-size: 24px;
        font-weight: 700;
        letter-spacing: -1.2px;
        color: #3d079d;
        white-space: nowrap;
    }
    .brand-name b { color: #ff7900; font-weight: 700; }

    .seller-name { font-weight: 700; font-size: 13px; margin: 12px 0 9px; color: #11102a; }

    table.seller-list { border-collapse: collapse; }
    table.seller-list td { vertical-align: top; padding: 3.5px 0; font-size: 11.5px; color: #2c2942; line-height: 1.3; }
    table.seller-list td.ic { width: 24px; }
    .ic svg { width: 16px; height: 16px; }

    .ruc { margin-top: 13px; color: #3d079d; font-weight: 700; font-size: 13px; }

    /* ---- doc box ---- */
    .doc-box { border: 1px solid #dedbea; border-radius: 14px; padding: 16px 18px; }
    .doc-title { color: #3d079d; font-size: 15px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; }
    .doc-number {
        color: #3d079d; font-size: 25px; font-weight: 700; line-height: 1.05;
        margin: 3px 0 11px; padding-bottom: 11px;
        border-bottom: 2px solid #3d079d;
        word-break: break-all;
    }
    table.doc-info { width: 100%; border-collapse: collapse; }
    table.doc-info > tbody > tr > td { vertical-align: top; }
    .qr-col { width: 92px; text-align: center; }
    .qr-col .qr { width: 80px; height: 80px; }
    .qr-col .qr-cap { font-size: 8px; color: #3d079d; line-height: 1.25; margin-top: 4px; }

    table.meta { border-collapse: collapse; margin-bottom: 13px; }
    table.meta td { vertical-align: top; }
    table.meta td.ic { width: 23px; }
    .meta-label { color: #3d079d; font-weight: 700; font-size: 11.5px; margin-bottom: 2px; }
    .meta-value { font-size: 12px; color: #2c2942; }

    /* ---- client ---- */
    table.client-card {
        width: 100%; border-collapse: collapse; margin-top: 12px;
    }
    .client-wrap {
        border: 1px solid #dedbea; border-radius: 14px;
        background: #fbfaff;
        padding: 15px 20px;
    }
    .client-card td { vertical-align: top; }
    .round-icon-col { width: 46px; }
    .round-icon {
        width: 32px; height: 32px; background: #3d079d; border-radius: 50%;
        text-align: center; line-height: 32px;
    }
    .round-icon svg { width: 18px; height: 18px; vertical-align: middle; }
    .client-title { color: #3d079d; font-weight: 700; font-size: 14px; text-transform: uppercase; margin-bottom: 12px; letter-spacing: .3px; }

    table.client-grid { width: 100%; border-collapse: collapse; }
    table.client-grid > tbody > tr > td { vertical-align: top; width: 50%; }
    .cg-left { padding-right: 22px; }
    .cg-right { border-left: 1px solid #dedbea; padding-left: 22px; }
    .field-label { font-size: 11px; font-weight: 700; color: #24203d; margin-bottom: 4px; }
    .field-value { font-size: 12.5px; font-weight: 700; line-height: 1.35; color: #11102a; }
    .field-muted { font-size: 12px; font-weight: 400; color: #11102a; }
    table.addr { border-collapse: collapse; }
    table.addr td { vertical-align: top; }
    table.addr td.ic { width: 23px; }

    /* ---- items ---- */
    .items { margin-top: 12px; border: 1px solid #dedbea; border-radius: 10px; overflow: hidden; }
    table.items-tbl { width: 100%; border-collapse: collapse; table-layout: fixed; }
    table.items-tbl thead th {
        background: #3d079d; color: #fff; padding: 11px 11px;
        font-size: 10.5px; text-transform: uppercase; letter-spacing: .3px;
        border-right: 1px solid rgba(255,255,255,.25); font-weight: 600;
    }
    table.items-tbl thead th.c1 { width: 44px; }
    table.items-tbl thead th.c3 { width: 72px; }
    table.items-tbl thead th.c4 { width: 110px; }
    table.items-tbl thead th.c5 { width: 110px; border-right: 0; }
    table.items-tbl tbody td {
        padding: 11px 14px 13px; vertical-align: top; font-size: 12px;
        border-right: 1px solid #dedbea; border-top: 1px solid #dedbea; color: #2c2942;
    }
    table.items-tbl tbody td.center { text-align: center; white-space: nowrap; }
    table.items-tbl tbody td:last-child { border-right: 0; }
    .product-code { margin-top: 8px; font-size: 10px; color: #77738b; }

    /* ---- bottom ---- */
    table.bottom { width: 100%; border-collapse: collapse; margin-top: 11px; }
    table.bottom > tbody > tr > td { vertical-align: top; }
    .bottom .left { padding-right: 14px; }
    .bottom .right { width: 51%; }

    .card { border: 1px solid #dedbea; border-radius: 10px; background: #fff; }

    table.son { width: 100%; border-collapse: collapse; }
    table.son td { vertical-align: top; padding: 12px 16px; }
    table.son td.ic { width: 36px; }
    .son .amount-label { font-weight: 700; font-size: 11px; margin-bottom: 6px; }
    .son .amount-text { font-weight: 700; color: #16122f; font-size: 12.5px; line-height: 1.2; text-transform: uppercase; }

    .extra { margin-top: 11px; padding: 12px 16px; }
    .extra-title { color: #3d079d; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 10px; }
    .extra-title .ic { display: inline-block; vertical-align: middle; margin-right: 7px; }
    .extra-title .ic svg { width: 15px; height: 15px; }
    .info-row { font-size: 11px; margin-bottom: 8px; line-height: 1.25; color: #2c2942; }
    .info-row strong { color: #3d079d; display: block; margin-bottom: 2px; font-weight: 700; }

    .totals { padding: 0; overflow: hidden; }
    .totals-inner { padding: 13px 18px; }
    table.trow { width: 100%; border-collapse: collapse; }
    table.trow td { font-size: 12.5px; padding-bottom: 11px; color: #2c2942; }
    table.trow td.amt { text-align: right; }
    .tdivider { border-top: 1px solid #dedbea; margin: 5px 0 13px; }
    table.sprice { width: 100%; border-collapse: collapse; }
    table.sprice td { font-weight: 700; }
    table.sprice td.lbl { font-size: 12.5px; text-transform: uppercase; }
    table.sprice td.amt { text-align: right; font-size: 17px; color: #120b33; }
    .pay-total { background: #3d079d; color: #fff; padding: 13px 18px; }
    table.pay { width: 100%; border-collapse: collapse; }
    table.pay td.label { font-size: 14px; font-weight: 700; text-transform: uppercase; }
    table.pay td.value { text-align: right; font-size: 22px; font-weight: 700; white-space: nowrap; }

    /* ---- footer ---- */
    .footer {
        position: absolute; left: 11mm; right: 11mm; bottom: 22mm;
        border-top: 2px solid #3d079d; padding-top: 16px;
    }
    table.footgrid { width: 100%; border-collapse: collapse; }
    table.footgrid > tbody > tr > td { vertical-align: top; }
    .secure-col { width: 38%; padding-right: 20px; border-right: 1px solid #dedbea; }
    .social-col { padding-left: 22px; }
    .wm-col { width: 90px; text-align: right; }

    table.secure { border-collapse: collapse; }
    table.secure td { vertical-align: top; }
    table.secure td.ic { width: 46px; }
    table.secure td.ic svg { width: 38px; height: 38px; }
    .secure-title { color: #3d079d; font-weight: 700; font-size: 13px; margin-bottom: 7px; }
    .secure-text { font-size: 11.5px; line-height: 1.35; color: #2c2942; }

    table.social { border-collapse: collapse; }
    table.social td { vertical-align: middle; padding: 4px 0; font-size: 13px; color: #2c2942; }
    table.social td.ic { width: 24px; }
    table.social td.ic svg { width: 17px; height: 17px; }

    .watermark { width: 76px; height: 76px; opacity: .12; }

    .icp svg { fill: #3d079d; }
    .icw svg { fill: #ffffff; }

    /* ---- footer (absoluto dentro de la pagina) ---- */
    .footer {
        position: absolute;
        left: 9mm; right: 9mm;
        bottom: 11mm;
        border-top: 2px solid #3d079d;
        padding-top: 11px;
    }
    table.footgrid { width: 100%; border-collapse: collapse; }
    table.footgrid > tbody > tr > td { vertical-align: top; }
    .secure-col { width: 38%; padding-right: 18px; border-right: 1px solid #dedbea; }
    .social-col { padding-left: 20px; }
    .wm-col { width: 72px; text-align: right; }
    table.secure { border-collapse: collapse; }
    table.secure td { vertical-align: top; }
    table.secure td.ic { width: 40px; }
    table.secure td.ic svg { width: 30px; height: 30px; }
    .secure-title { color: #3d079d; font-weight: 700; font-size: 12px; margin-bottom: 5px; }
    .secure-text { font-size: 10px; line-height: 1.35; color: #2c2942; }
    table.social { border-collapse: collapse; }
    table.social td { vertical-align: middle; padding: 2.5px 0; font-size: 11.5px; color: #2c2942; }
    table.social td.ic { width: 22px; }
    table.social td.ic svg { width: 15px; height: 15px; }
    .watermark { width: 60px; height: 60px; opacity: .12; }

    .footbar {
        position: absolute;
        left: 0; right: 0; bottom: 0;
        height: 8mm;
        background: #3d079d;
        color: #fff;
        text-align: center;
        line-height: 8mm;
        font-weight: 700;
        font-size: 12px;
        border-top-left-radius: 14px;
        border-top-right-radius: 14px;
    }
    .footbar .orange {
        position: absolute; top: 0; right: 0;
        width: 13%; height: 8mm;
        background: #ff7900;
        border-top-right-radius: 14px;
    }
</style>
</head>
<body>
<div class="invoice">
    <div class="topbar"><span class="orange"></span></div>

    <!-- HEADER -->
    <table class="header">
        <tr>
            <td class="seller-col">
                <table class="brand">
                    <tr>
                        <td><img class="logo-img" src="{{ $logo }}" alt="logo"></td>
                        <td class="brand-name">Shiper<b>Sales</b></td>
                    </tr>
                </table>

                <p class="seller-name">{{ $company->company ?? 'SHIPERSALES' }}</p>

                <table class="seller-list">
                    <tr>
                        <td class="ic icp"><svg viewBox="0 0 24 24"><path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7Zm0 9.8A2.8 2.8 0 1 1 12 6.2a2.8 2.8 0 0 1 0 5.6Z"/></svg></td>
                        <td>{{ $company->address ?? '' }}<br>{{ trim(($company->city ?? '').', '.($company->country ?? ''), ', ') }}</td>
                    </tr>
                    <tr>
                        <td class="ic icp"><svg viewBox="0 0 24 24"><path d="M6.6 10.8c1.5 3 3.6 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.3 1.2.4 2.4.6 3.7.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.7 21 3 13.3 3 3.8c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.7.1.4 0 .8-.3 1.1l-2.2 2.2Z"/></svg></td>
                        <td>{{ $company->phone ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="ic icp"><svg viewBox="0 0 24 24"><path d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm9 7.2L4.6 7H4v.8l8 5.6 8-5.6V7h-.6L12 12.2Z"/></svg></td>
                        <td>{{ $company->email ?? '' }}</td>
                    </tr>
                </table>

                <div class="ruc">RUC: {{ $company->ruc ?? '' }}</div>
            </td>

            <td class="doc-col">
                <div class="doc-box">
                    <div class="doc-title">Nota de venta</div>
                    <div class="doc-number">{{ $sale->number }}</div>

                    <table class="doc-info">
                        <tr>
                            <td>
                                <table class="meta">
                                    <tr>
                                        <td class="ic icp"><svg viewBox="0 0 24 24"><path d="M7 2h2v3h6V2h2v3h2a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2V2Zm12 9H5v9h14v-9ZM5 7v2h14V7H5Z"/></svg></td>
                                        <td>
                                            <div class="meta-label">Fecha de emisión</div>
                                            <div class="meta-value">{{ \Carbon\Carbon::parse($sale->date)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</div>
                                        </td>
                                    </tr>
                                </table>
                                <table class="meta">
                                    <tr>
                                        <td class="ic icp"><svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 .01 0ZM13 17.5V19h-2v-1.5a4 4 0 0 1-3-2.2l1.8-.9A2.2 2.2 0 0 0 12 15.8c1 0 1.7-.5 1.7-1.2 0-.8-.6-1.1-2.2-1.6-1.8-.6-3.2-1.3-3.2-3.1 0-1.5 1.1-2.7 2.7-3.1V5h2v1.7a3.6 3.6 0 0 1 2.7 2l-1.7 1a2 2 0 0 0-1.9-1.2c-.9 0-1.5.5-1.5 1.2 0 .7.5 1 2.1 1.5 1.8.6 3.3 1.3 3.3 3.3 0 1.6-1.2 2.8-3 3Z"/></svg></td>
                                        <td>
                                            <div class="meta-label">Moneda</div>
                                            <div class="meta-value">SOLES</div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td class="qr-col">
                                <img class="qr" src="{{ $qr }}" alt="QR">
                                <div class="qr-cap">Verifica tu<br>comprobante</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- CLIENT -->
    <div class="client-wrap">
        <table class="client-card">
            <tr>
                <td class="round-icon-col">
                    <div class="round-icon icw"><svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-4.4 0-8 2.2-8 5v1h16v-1c0-2.8-3.6-5-8-5Z"/></svg></div>
                </td>
                <td>
                    <div class="client-title">Datos del cliente</div>
                    <table class="client-grid">
                        <tr>
                            <td class="cg-left">
                                <div class="field-label">Razón Social / Nombres y Apellidos</div>
                                <div class="field-value">{{ $sale->client->name }}</div>
                                <div style="height:12px"></div>
                                <div class="field-label">{{ $sale->client->document_type ?? 'Documento' }}</div>
                                <div class="field-muted">{{ $sale->client->document_number }}</div>
                            </td>
                            <td class="cg-right">
                                <table class="addr">
                                    <tr>
                                        <td class="ic icp"><svg viewBox="0 0 24 24"><path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7Zm0 9.8A2.8 2.8 0 1 1 12 6.2a2.8 2.8 0 0 1 0 5.6Z"/></svg></td>
                                        <td>
                                            <div class="field-label">Dirección</div>
                                            <div class="field-muted">{{ $sale->client->address ?: '—' }}</div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- ITEMS -->
    <div class="items">
        <table class="items-tbl">
            <thead>
                <tr>
                    <th class="c1">#</th>
                    <th>Descripción</th>
                    <th class="c3">Cant.</th>
                    <th class="c4">Precio unitario</th>
                    <th class="c5">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->saleDetails as $i => $item)
                    <tr>
                        <td class="center">{{ $i + 1 }}</td>
                        <td>
                            {{ $item->article->title }}
                            @if($item->article->sku)
                                <div class="product-code">Código: {{ $item->article->sku }}</div>
                            @endif
                        </td>
                        <td class="center">{{ number_format($item->quantity, 2) }}</td>
                        <td class="center">S/ {{ number_format($item->price, 2) }}</td>
                        <td class="center">S/ {{ number_format($item->price * $item->quantity, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- BOTTOM -->
    <table class="bottom">
        <tr>
            <td class="left">
                <div class="card">
                    <table class="son">
                        <tr>
                            <td class="ic icp" style="padding-right:0;"><svg viewBox="0 0 24 24"><path d="M6 2h9l5 5v15H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm8 1.5V8h4.5L14 3.5ZM8 12h8v2H8v-2Zm0 4h8v2H8v-2Zm0-8h4v2H8V8Z"/></svg></td>
                            <td>
                                <div class="amount-label">SON:</div>
                                <div class="amount-text">{{ $amountInWords }}</div>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="card extra">
                    <div class="extra-title">
                        <span class="ic icp"><svg viewBox="0 0 24 24"><path d="M11 17h2v-6h-2v6Zm0-8h2V7h-2v2Zm1 13A10 10 0 1 1 12 2a10 10 0 0 1 0 20Z"/></svg></span>Información adicional
                    </div>
                    <div class="info-row"><strong>Condición de pago:</strong>{{ $sale->paymentMethod->name ?? 'Efectivo' }}</div>
                    <div class="info-row"><strong>Vendedor:</strong>{{ $sale->user->name ?? '' }}</div>
                    <div class="info-row"><strong>Representación impresa de la</strong>NOTA DE VENTA</div>
                </div>
            </td>

            <td class="right">
                <div class="card totals">
                    <div class="totals-inner">
                        <table class="trow">
                            <tr><td>Op. Gravadas</td><td class="amt">S/ {{ number_format($opGravadas, 2) }}</td></tr>
                            <tr><td>I.G.V. (18%)</td><td class="amt">S/ {{ number_format($sale->tax, 2) }}</td></tr>
                            @if($sale->delivery == 1 && $sale->delivery_fee > 0)
                            <tr><td>Delivery</td><td class="amt">S/ {{ number_format($sale->delivery_fee, 2) }}</td></tr>
                            @endif
                        </table>
                        <div class="tdivider"></div>
                        <table class="sprice">
                            <tr><td class="lbl">Precio de venta</td><td class="amt">S/ {{ number_format($grandTotal, 2) }}</td></tr>
                        </table>
                    </div>
                    <div class="pay-total">
                        <table class="pay">
                            <tr><td class="label">Total a pagar</td><td class="value">S/ {{ number_format($grandTotal, 2) }}</td></tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- FOOTER -->
    <div class="footer">
        <table class="footgrid">
            <tr>
                <td class="secure-col">
                    <table class="secure">
                        <tr>
                            <td class="ic icp"><svg viewBox="0 0 24 24"><path d="M12 2 4 5v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V5l-8-3Zm4.7 7.8-5.4 5.4-2.7-2.7L10 11l1.3 1.3 4-4 1.4 1.5Z"/></svg></td>
                            <td>
                                <div class="secure-title">Compra segura</div>
                                <div class="secure-text">Este comprobante ha sido emitido electrónicamente.</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="social-col">
                    <table class="social">
                        <tr>
                            <td class="ic icp"><svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm6.9 9h-3a15.7 15.7 0 0 0-1-5 8.1 8.1 0 0 1 4 5ZM12 4.1c.7 1 1.5 3 1.8 6.9h-3.6c.3-3.9 1.1-5.9 1.8-6.9ZM4.3 13h3.8a16.4 16.4 0 0 0 1 5.1A8.1 8.1 0 0 1 4.3 13Zm3.8-2H4.3a8.1 8.1 0 0 1 4.8-5 16.4 16.4 0 0 0-1 5Zm3.9 8.9c-.7-1-1.5-3-1.8-6.9h3.6c-.3 3.9-1.1 5.9-1.8 6.9Zm2.9-1.8a16.4 16.4 0 0 0 1-5.1h3.8a8.1 8.1 0 0 1-4.8 5.1Z"/></svg></td>
                            <td>www.shipersales.pe</td>
                        </tr>
                        <tr>
                            <td class="ic icp"><svg viewBox="0 0 24 24"><path d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm9 7.2L4.6 7H4v.8l8 5.6 8-5.6V7h-.6L12 12.2Z"/></svg></td>
                            <td>{{ $company->email ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="ic icp"><svg viewBox="0 0 24 24"><path d="M6.6 10.8c1.5 3 3.6 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.3 1.2.4 2.4.6 3.7.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.7 21 3 13.3 3 3.8c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.7.1.4 0 .8-.3 1.1l-2.2 2.2Z"/></svg></td>
                            <td>{{ $company->phone ?? '' }}</td>
                        </tr>
                    </table>
                </td>
                <td class="wm-col">
                    <img class="watermark" src="{{ $logo }}" alt="">
                </td>
            </tr>
        </table>
    </div>

    <div class="footbar">¡Gracias por tu compra!<span class="orange"></span></div>
</div>
</body>
</html>
