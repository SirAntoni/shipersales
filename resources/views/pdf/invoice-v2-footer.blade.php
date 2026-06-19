<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8" />
<style>
    {!! $fontFace ?? '' !!}

    * { box-sizing: border-box; margin: 0; padding: 0; }

    /* La banda mide igual que el margin-bottom (40mm) reservado en wkhtmltopdf. */
    html, body {
        width: 100%;
        height: 40mm;
        font-family: 'Poppins', Arial, Helvetica, sans-serif;
        color: #11102a;
    }
    body { position: relative; }

    .footer {
        position: absolute;
        left: 11mm; right: 11mm;
        bottom: 12mm;                /* deja sitio para el cintillo */
        border-top: 2px solid #3d079d;
        padding-top: 14px;
    }
    table.footgrid { width: 100%; border-collapse: collapse; }
    table.footgrid > tbody > tr > td { vertical-align: top; }
    .secure-col { width: 38%; padding-right: 20px; border-right: 1px solid #dedbea; }
    .social-col { padding-left: 22px; }
    .wm-col { width: 86px; text-align: right; }

    table.secure { border-collapse: collapse; }
    table.secure td { vertical-align: top; }
    table.secure td.ic { width: 46px; }
    table.secure td.ic svg { width: 36px; height: 36px; fill: #3d079d; }
    .secure-title { color: #3d079d; font-weight: 700; font-size: 13px; margin-bottom: 6px; }
    .secure-text { font-size: 11px; line-height: 1.35; color: #2c2942; }

    table.social { border-collapse: collapse; }
    table.social td { vertical-align: middle; padding: 3px 0; font-size: 12.5px; color: #2c2942; }
    table.social td.ic { width: 24px; }
    table.social td.ic svg { width: 16px; height: 16px; fill: #3d079d; }

    .watermark { width: 72px; height: 72px; opacity: .12; }

    .footbar {
        position: absolute;
        left: 0; right: 0; bottom: 0;
        height: 9mm;
        background: #3d079d;
        color: #fff;
        text-align: center;
        line-height: 9mm;
        font-weight: 700;
        font-size: 13px;
        border-top-left-radius: 14px;
        border-top-right-radius: 14px;
    }
    .footbar .orange {
        position: absolute; top: 0; right: 0;
        width: 13%; height: 9mm;
        background: #ff7900;
        border-top-right-radius: 14px;
    }
</style>
</head>
<body>
    <div class="footer">
        <table class="footgrid">
            <tr>
                <td class="secure-col">
                    <table class="secure">
                        <tr>
                            <td class="ic"><svg viewBox="0 0 24 24"><path d="M12 2 4 5v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V5l-8-3Zm4.7 7.8-5.4 5.4-2.7-2.7L10 11l1.3 1.3 4-4 1.4 1.5Z"/></svg></td>
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
                            <td class="ic"><svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm6.9 9h-3a15.7 15.7 0 0 0-1-5 8.1 8.1 0 0 1 4 5ZM12 4.1c.7 1 1.5 3 1.8 6.9h-3.6c.3-3.9 1.1-5.9 1.8-6.9ZM4.3 13h3.8a16.4 16.4 0 0 0 1 5.1A8.1 8.1 0 0 1 4.3 13Zm3.8-2H4.3a8.1 8.1 0 0 1 4.8-5 16.4 16.4 0 0 0-1 5Zm3.9 8.9c-.7-1-1.5-3-1.8-6.9h3.6c-.3 3.9-1.1 5.9-1.8 6.9Zm2.9-1.8a16.4 16.4 0 0 0 1-5.1h3.8a8.1 8.1 0 0 1-4.8 5.1Z"/></svg></td>
                            <td>www.shipersales.pe</td>
                        </tr>
                        <tr>
                            <td class="ic"><svg viewBox="0 0 24 24"><path d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm9 7.2L4.6 7H4v.8l8 5.6 8-5.6V7h-.6L12 12.2Z"/></svg></td>
                            <td>{{ $company->email ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="ic"><svg viewBox="0 0 24 24"><path d="M6.6 10.8c1.5 3 3.6 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.3 1.2.4 2.4.6 3.7.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.7 21 3 13.3 3 3.8c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.7.1.4 0 .8-.3 1.1l-2.2 2.2Z"/></svg></td>
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
</body>
</html>
