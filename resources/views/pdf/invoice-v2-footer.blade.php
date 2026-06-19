<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        {!! $fontFace ?? '' !!}

        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* La altura del body coincide con el margin-bottom (30mm) reservado en wkhtmltopdf,
           así el cintillo anclado a bottom:0 toca el borde inferior de la hoja. */
        html, body {
            width: 100%;
            height: 30mm;
            font-family: 'Poppins', 'Helvetica Neue', 'Arial', sans-serif;
        }
        body { position: relative; }

        .watermark {
            position: absolute;
            right: 34px;
            bottom: 11mm;
            width: 58px;
            opacity: 0.10;
        }

        .foot {
            position: absolute;
            left: 0; right: 0;
            bottom: 9mm;            /* justo encima del cintillo */
            padding: 0 34px;
        }
        .foot hr { border: none; border-top: 1px solid #ece9f6; margin-bottom: 10px; }
        .foot table { width: 100%; border-collapse: collapse; }
        .foot td { vertical-align: top; font-size: 11px; color: #5b5b67; }
        .foot td div { margin-bottom: 3px; }
        .foot td div span { vertical-align: middle; }
        .foot .ftitle { font-weight: 700; color: #4b2d9f; margin-bottom: 4px; }
        .fico { width: 13px; height: 13px; vertical-align: middle; margin-right: 6px; }

        .footbar {
            position: absolute;
            left: 0; right: 0;
            bottom: 0;
            height: 9mm;
            background: #4b2d9f;
            background: -webkit-linear-gradient(left, #4b2d9f 0%, #6a45c9 70%);
            color: #fff;
            text-align: center;
            line-height: 9mm;
            font-weight: 700;
            font-size: 12px;
        }
        .footbar .accent {
            position: absolute;
            top: 0; right: 0;
            width: 90px; height: 9mm;
            background: #f5871f;
        }
    </style>
</head>
<body>
    <img class="watermark" src="{{ $logo }}" alt="">
    <div class="foot">
        <hr>
        <table>
            <tr>
                <td style="width: 50%;">
                    <div class="ftitle">
                        <svg class="fico" viewBox="0 0 24 24"><path d="M12 3l7 2.5v5c0 4.5-3 7.8-7 9-4-1.2-7-4.5-7-9v-5z" fill="#4b2d9f"/><path d="M9 12l2 2 4-4.2" fill="none" stroke="#fff" stroke-width="1.6"/></svg>
                        <span>Compra segura</span>
                    </div>
                    Este comprobante ha sido emitido electrónicamente.
                </td>
                <td style="width: 45%;">
                    <div>
                        <svg class="fico" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="none" stroke="#4b2d9f" stroke-width="1.5"/><path d="M3 12h18M12 3c2.8 3 2.8 15 0 18M12 3c-2.8 3-2.8 15 0 18" fill="none" stroke="#4b2d9f" stroke-width="1.2"/></svg>
                        <span>www.shipersales.pe</span>
                    </div>
                    <div>
                        <svg class="fico" viewBox="0 0 24 24"><rect x="3.5" y="6" width="17" height="12" rx="2" fill="none" stroke="#4b2d9f" stroke-width="1.5"/><path d="M4 7l8 6 8-6" fill="none" stroke="#4b2d9f" stroke-width="1.3"/></svg>
                        <span>{{ $company->email ?? '' }}</span>
                    </div>
                    <div>
                        <svg class="fico" viewBox="0 0 24 24"><path d="M7 4c-.6 0-1.2.5-1.2 1.2 0 7 5.8 12.8 12.8 12.8.7 0 1.2-.6 1.2-1.2v-2.6c0-.5-.4-1-.9-1.1l-2.8-.6c-.4-.1-.9 0-1.1.4l-.8 1c-2-1-3.6-2.6-4.6-4.6l1-.8c.3-.3.5-.7.4-1.1l-.6-2.8C8 4.4 7.5 4 7 4z" fill="#4b2d9f"/></svg>
                        <span>{{ $company->phone ?? '' }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <div class="footbar">¡Gracias por tu compra!<span class="accent"></span></div>
</body>
</html>
