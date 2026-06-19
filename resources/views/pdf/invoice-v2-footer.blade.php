<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', 'Arial', sans-serif; }

        .foot { padding: 0 34px 10px 34px; position: relative; }
        .foot .watermark { position: absolute; right: 28px; bottom: 2px; width: 86px; opacity: 0.10; }
        .foot hr { border: none; border-top: 1px solid #ece9f6; margin-bottom: 12px; }
        .foot table { width: 100%; border-collapse: collapse; }
        .foot td { vertical-align: top; font-size: 11px; color: #5b5b67; }
        .foot .ftitle { font-weight: 700; color: #4b2d9f; margin-bottom: 3px; }

        .footbar {
            height: 34px;
            background: #4b2d9f;
            background: -webkit-linear-gradient(left, #4b2d9f 0%, #6a45c9 70%);
            position: relative;
            color: #fff;
            text-align: center;
            line-height: 34px;
            font-weight: 700;
            font-size: 12px;
        }
        .footbar .accent {
            position: absolute;
            top: 0; right: 0;
            width: 90px; height: 34px;
            background: #f5871f;
        }
    </style>
</head>
<body>
    <div class="foot">
        <img class="watermark" src="{{ $logo }}" alt="">
        <hr>
        <table>
            <tr>
                <td style="width: 50%;">
                    <div class="ftitle">Compra segura</div>
                    Este comprobante ha sido emitido electrónicamente.
                </td>
                <td style="width: 50%;">
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
