<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* La altura del body coincide con el margin-bottom (30mm) reservado en wkhtmltopdf,
           así el cintillo anclado a bottom:0 toca el borde inferior de la hoja. */
        html, body {
            width: 100%;
            height: 30mm;
            font-family: 'Helvetica Neue', 'Arial', sans-serif;
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
        .foot .ftitle { font-weight: 700; color: #4b2d9f; margin-bottom: 3px; }

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
                    <div class="ftitle">Compra segura</div>
                    Este comprobante ha sido emitido electrónicamente.
                </td>
                <td style="width: 45%;">
                    <div>&#127760; www.shipersales.pe</div>
                    <div>&#9993; {{ $company->email ?? '' }}</div>
                    <div>&#9742; {{ $company->phone ?? '' }}</div>
                </td>
            </tr>
        </table>
    </div>
    <div class="footbar">¡Gracias por tu compra!<span class="accent"></span></div>
</body>
</html>
