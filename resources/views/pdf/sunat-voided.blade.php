<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8" />
<title>{{ $title }} - {{ $number }}</title>
<style>
    {!! $fontFace ?? '' !!}

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: 'Poppins', Arial, Helvetica, sans-serif;
        color: #11102a;
        font-size: 10.5px;
        background: #ffffff;
        -webkit-print-color-adjust: exact;
    }

    .invoice {
        position: relative;
        width: 210mm;
        height: 297mm;
        background: #fff;
        padding: 14mm 9mm 0;
        overflow: hidden;
    }

    .topbar {
        position: absolute; top: 0; left: 0; right: 0;
        height: 15px; background: #3d079d;
        border-bottom-left-radius: 14px; border-bottom-right-radius: 14px; z-index: 2;
    }
    .topbar .orange {
        position: absolute; top: 0; right: 0;
        width: 13%; height: 15px; background: #ff7900; border-bottom-right-radius: 14px;
    }

    table.header { width: 100%; border-collapse: collapse; }
    table.header > tbody > tr > td { vertical-align: top; }
    .seller-col { padding-right: 10mm; }
    .doc-col { width: 100mm; }

    .brand td { vertical-align: middle; }
    .brand .logo-img { width: 42px; height: 42px; }
    .brand-name { padding-left: 8px; font-size: 22px; font-weight: 700; letter-spacing: -1.1px; color: #3d079d; white-space: nowrap; }
    .brand-name b { color: #ff7900; font-weight: 700; }

    .seller-name { font-weight: 700; font-size: 12px; margin: 10px 0 8px; color: #11102a; }
    table.seller-list { border-collapse: collapse; }
    table.seller-list td { vertical-align: top; padding: 3px 0; font-size: 11px; color: #2c2942; line-height: 1.3; }
    table.seller-list td.ic { width: 22px; }
    .ic svg { width: 15px; height: 15px; }
    .ruc { margin-top: 11px; color: #3d079d; font-weight: 700; font-size: 12px; }

    .doc-box { border: 1px solid #dedbea; border-radius: 13px; padding: 14px 16px; }
    .doc-title { color: #3d079d; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; }
    .doc-number { color: #3d079d; font-size: 22px; font-weight: 700; line-height: 1.05; margin: 3px 0 10px; padding-bottom: 10px; border-bottom: 2px solid #3d079d; word-break: break-all; }
    table.meta { border-collapse: collapse; margin-bottom: 13px; }
    table.meta td { vertical-align: top; }
    table.meta td.ic { width: 23px; }
    .meta-label { color: #3d079d; font-weight: 700; font-size: 11.5px; margin-bottom: 2px; }
    .meta-value { font-size: 12px; color: #2c2942; }

    .section-title { margin-top: 22px; color: #3d079d; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: .3px; }
    .section-title .ic { display: inline-block; vertical-align: middle; margin-right: 8px; }
    .section-title .ic svg { width: 18px; height: 18px; }

    .items { margin-top: 12px; border: 1px solid #dedbea; border-radius: 10px; overflow: hidden; }
    table.items-tbl { width: 100%; border-collapse: collapse; table-layout: fixed; }
    table.items-tbl thead th {
        background: #3d079d; color: #fff; padding: 9px 11px;
        font-size: 10px; text-transform: uppercase; letter-spacing: .3px;
        border-right: 1px solid rgba(255,255,255,.25); font-weight: 600;
    }
    table.items-tbl thead th.c1 { width: 40px; }
    table.items-tbl thead th.c2 { width: 130px; }
    table.items-tbl thead th.c3 { width: 150px; }
    table.items-tbl thead th:last-child { border-right: 0; }
    table.items-tbl tbody td {
        padding: 11px 13px; vertical-align: top; font-size: 11.5px;
        border-right: 1px solid #dedbea; border-top: 1px solid #dedbea; color: #2c2942;
    }
    table.items-tbl tbody td.center { text-align: center; }
    table.items-tbl tbody td:last-child { border-right: 0; }

    .footer {
        position: absolute; left: 9mm; right: 9mm; bottom: 11mm;
        border-top: 2px solid #3d079d; padding-top: 11px;
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
    .watermark { width: 60px; height: 60px; opacity: .07; }

    .footbar {
        position: absolute; left: 0; right: 0; bottom: 0; height: 8mm;
        background: #3d079d; color: #fff; text-align: center; line-height: 8mm;
        font-weight: 700; font-size: 12px; border-top-left-radius: 14px; border-top-right-radius: 14px;
    }
    .footbar .orange { position: absolute; top: 0; right: 0; width: 13%; height: 8mm; background: #ff7900; border-top-right-radius: 14px; }

    .icp svg { fill: #3d079d; }
</style>
</head>
<body>
<div class="invoice">
    <div class="topbar"><span class="orange"></span></div>

    <table class="header">
        <tr>
            <td class="seller-col">
                <table class="brand">
                    <tr>
                        <td><img class="logo-img" src="{{ $logo }}" alt="logo"></td>
                        <td class="brand-name">Shiper<b>Sales</b></td>
                    </tr>
                </table>
                <p class="seller-name">{{ $c['name'] }}</p>
                <table class="seller-list">
                    <tr>
                        <td class="ic icp"><svg viewBox="0 0 24 24"><path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7Zm0 9.8A2.8 2.8 0 1 1 12 6.2a2.8 2.8 0 0 1 0 5.6Z"/></svg></td>
                        <td>{{ $c['address'] }}<br>{{ trim(($c['city'] ?? '').', '.($c['country'] ?? ''), ', ') }}</td>
                    </tr>
                    <tr>
                        <td class="ic icp"><svg viewBox="0 0 24 24"><path d="M6.6 10.8c1.5 3 3.6 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.3 1.2.4 2.4.6 3.7.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.7 21 3 13.3 3 3.8c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.7.1.4 0 .8-.3 1.1l-2.2 2.2Z"/></svg></td>
                        <td>{{ $c['phone'] }}</td>
                    </tr>
                    <tr>
                        <td class="ic icp"><svg viewBox="0 0 24 24"><path d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm9 7.2L4.6 7H4v.8l8 5.6 8-5.6V7h-.6L12 12.2Z"/></svg></td>
                        <td>{{ $c['email'] }}</td>
                    </tr>
                </table>
                <div class="ruc">RUC: {{ $c['ruc'] }}</div>
            </td>

            <td class="doc-col">
                <div class="doc-box">
                    <div class="doc-title">{{ $title }}</div>
                    <div class="doc-number">{{ $number }}</div>
                    <table class="meta">
                        <tr>
                            <td class="ic icp"><svg viewBox="0 0 24 24"><path d="M7 2h2v3h6V2h2v3h2a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2V2Zm12 9H5v9h14v-9ZM5 7v2h14V7H5Z"/></svg></td>
                            <td>
                                <div class="meta-label">Fecha de generación</div>
                                <div class="meta-value">{{ $fechaGen }}</div>
                            </td>
                        </tr>
                    </table>
                    <table class="meta">
                        <tr>
                            <td class="ic icp"><svg viewBox="0 0 24 24"><path d="M7 2h2v3h6V2h2v3h2a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h2V2Zm12 9H5v9h14v-9ZM5 7v2h14V7H5Z"/></svg></td>
                            <td>
                                <div class="meta-label">Fecha de comunicación</div>
                                <div class="meta-value">{{ $fechaCom }}</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">
        <span class="ic icp"><svg viewBox="0 0 24 24"><path d="M6 2h9l5 5v15H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm8 1.5V8h4.5L14 3.5ZM8 12h8v2H8v-2Zm0 4h8v2H8v-2Z"/></svg></span>Documentos dados de baja
    </div>

    <div class="items">
        <table class="items-tbl">
            <thead>
                <tr>
                    <th class="c1">#</th>
                    <th class="c2">Tipo</th>
                    <th class="c3">Documento</th>
                    <th>Motivo de baja</th>
                </tr>
            </thead>
            <tbody>
                @foreach($details as $i => $det)
                    <tr>
                        <td class="center">{{ $i + 1 }}</td>
                        <td>{{ $det['tipo'] }}</td>
                        <td>{{ $det['documento'] }}</td>
                        <td>{{ $det['motivo'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <table class="footgrid">
            <tr>
                <td class="secure-col">
                    <table class="secure">
                        <tr>
                            <td class="ic icp"><svg viewBox="0 0 24 24"><path d="M12 2 4 5v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V5l-8-3Zm4.7 7.8-5.4 5.4-2.7-2.7L10 11l1.3 1.3 4-4 1.4 1.5Z"/></svg></td>
                            <td>
                                <div class="secure-title">Comunicación válida</div>
                                <div class="secure-text">Emitida electrónicamente ante SUNAT.</div>
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
                            <td>{{ $c['email'] }}</td>
                        </tr>
                        <tr>
                            <td class="ic icp"><svg viewBox="0 0 24 24"><path d="M6.6 10.8c1.5 3 3.6 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.3 1.2.4 2.4.6 3.7.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.7 21 3 13.3 3 3.8c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.7.1.4 0 .8-.3 1.1l-2.2 2.2Z"/></svg></td>
                            <td>{{ $c['phone'] }}</td>
                        </tr>
                    </table>
                </td>
                <td class="wm-col">
                    <img class="watermark" src="{{ $logo }}" alt="">
                </td>
            </tr>
        </table>
    </div>

    <div class="footbar">{{ $footer ?? 'Comunicación de baja electrónica' }}<span class="orange"></span></div>
</div>
</body>
</html>
