<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota de venta - {{ $sale->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {}

        body {
            font-family: 'Helvetica Neue', 'Arial', sans-serif;
            color: #2b2b3a;
            font-size: 12px;
            background: #ffffff;
        }

        .purple        { color: #4b2d9f; }
        .orange        { color: #f5871f; }
        .muted         { color: #8a8a98; }

        /* ---- top decorative bar ---- */
        .topbar {
            height: 14px;
            background: #4b2d9f;
            background: -webkit-linear-gradient(left, #4b2d9f 0%, #6a45c9 70%);
            position: relative;
        }
        .topbar .accent {
            position: absolute;
            top: 0; right: 0;
            width: 90px; height: 14px;
            background: #f5871f;
            border-bottom-left-radius: 14px;
        }

        .wrap { padding: 28px 34px 0 34px; }

        /* ---- header ---- */
        table.header { width: 100%; border-collapse: collapse; }
        table.header td { vertical-align: top; }

        .brand img { height: 46px; vertical-align: middle; }
        .brand .name {
            display: inline-block;
            vertical-align: middle;
            margin-left: 10px;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .emitter { margin-top: 16px; }
        .emitter .company { font-size: 14px; font-weight: 700; color: #2b2b3a; margin-bottom: 8px; }
        .emitter .line { margin-bottom: 4px; color: #5b5b67; }
        .emitter .ruc { margin-top: 8px; font-size: 13px; font-weight: 700; color: #4b2d9f; }
        .ico { color: #4b2d9f; font-weight: 700; }

        /* ---- voucher card ---- */
        .voucher {
            border: 1px solid #ece9f6;
            border-radius: 12px;
            padding: 18px 20px;
            box-shadow: 0 2px 10px rgba(75,45,159,0.06);
        }
        .voucher .vtitle { font-size: 16px; font-weight: 700; color: #4b2d9f; letter-spacing: 0.5px; }
        .voucher .vnumber { font-size: 30px; font-weight: 700; color: #2b2b3a; line-height: 1.1; margin-top: 2px; }
        .voucher hr { border: none; border-top: 2px solid #ece9f6; margin: 12px 0; }
        .voucher .meta-label { font-size: 11px; font-weight: 700; color: #4b2d9f; }
        .voucher .meta-value { color: #5b5b67; margin-bottom: 10px; }

        /* ---- client box ---- */
        .client {
            margin-top: 22px;
            background: #f7f6fb;
            border: 1px solid #ece9f6;
            border-radius: 12px;
            padding: 16px 20px;
        }
        .client .ctitle { font-size: 13px; font-weight: 700; color: #4b2d9f; letter-spacing: 0.5px; margin-bottom: 12px; }
        .client table { width: 100%; border-collapse: collapse; }
        .client td { vertical-align: top; width: 50%; }
        .client .lbl { font-size: 11px; font-weight: 700; color: #6b6b78; }
        .client .val { font-weight: 700; color: #2b2b3a; margin-bottom: 8px; }

        /* ---- items ---- */
        table.items { width: 100%; border-collapse: collapse; margin-top: 22px; border-radius: 10px; overflow: hidden; }
        table.items thead th {
            background: #4b2d9f;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 11px 12px;
            text-align: center;
        }
        table.items thead th.desc { text-align: left; }
        table.items tbody td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 12px;
            text-align: center;
            color: #3a3a47;
        }
        table.items tbody td.desc { text-align: left; }
        table.items tbody td .code { display: block; font-size: 10px; color: #a0a0ac; margin-top: 3px; }

        /* ---- bottom ---- */
        table.bottom { width: 100%; border-collapse: separate; border-spacing: 16px 0; margin-top: 22px; }
        table.bottom > tbody > tr > td { vertical-align: top; width: 50%; }

        .son {
            background: #f7f6fb;
            border: 1px solid #ece9f6;
            border-radius: 12px;
            padding: 16px 18px;
        }
        .son .lbl { font-size: 11px; font-weight: 700; color: #4b2d9f; }
        .son .words { font-size: 13px; font-weight: 700; color: #2b2b3a; margin-top: 4px; text-transform: uppercase; }

        .addinfo {
            margin-top: 16px;
            background: #f7f6fb;
            border: 1px solid #ece9f6;
            border-radius: 12px;
            padding: 16px 18px;
        }
        .addinfo .ctitle { font-size: 12px; font-weight: 700; color: #4b2d9f; margin-bottom: 8px; }
        .addinfo .k { font-size: 11px; font-weight: 700; color: #4b4b58; margin-top: 6px; }
        .addinfo .v { color: #5b5b67; }

        table.totals { width: 100%; border-collapse: collapse; }
        table.totals td { padding: 6px 4px; }
        table.totals td.r { text-align: right; }
        table.totals .lbl { color: #5b5b67; }
        table.totals .amt { text-align: right; font-weight: 700; color: #2b2b3a; }
        table.totals .sep td { border-top: 1px solid #ece9f6; }
        table.totals .sale .lbl { font-weight: 700; color: #2b2b3a; }
        table.totals .sale .amt { font-size: 14px; }

        .grandtotal {
            margin-top: 12px;
            background: #4b2d9f;
            border-radius: 12px;
            padding: 16px 20px;
            color: #fff;
        }
        .grandtotal table { width: 100%; }
        .grandtotal .gl { font-size: 14px; font-weight: 700; letter-spacing: 0.4px; }
        .grandtotal .gv { text-align: right; font-size: 22px; font-weight: 700; }

        /* ---- footer ---- */
        .foot { margin-top: 26px; padding: 0 34px; }
        .foot hr { border: none; border-top: 1px solid #ece9f6; margin-bottom: 14px; }
        .foot table { width: 100%; border-collapse: collapse; }
        .foot td { vertical-align: top; font-size: 11px; color: #5b5b67; }
        .foot .ftitle { font-weight: 700; color: #4b2d9f; margin-bottom: 3px; }

        .footbar {
            margin-top: 18px;
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
            border-top-left-radius: 14px;
        }
    </style>
</head>
<body>
    <div class="topbar"><span class="accent"></span></div>

    <div class="wrap">
        <!-- HEADER -->
        <table class="header">
            <tr>
                <td style="width: 55%;">
                    <div class="brand">
                        <img src="{{ $logo }}" alt="logo">
                        <span class="name"><span class="purple">Shiper</span><span class="orange">Sales</span></span>
                    </div>
                    <div class="emitter">
                        <div class="company">{{ $company->company ?? 'SHIPERSALES' }}</div>
                        <div class="line"><span class="ico">&#9679;</span> {{ $company->address ?? '' }}</div>
                        <div class="line muted">{{ trim(($company->city ?? '').', '.($company->country ?? ''), ', ') }}</div>
                        <div class="line"><span class="ico">&#9742;</span> {{ $company->phone ?? '' }}</div>
                        <div class="line"><span class="ico">&#9993;</span> {{ $company->email ?? '' }}</div>
                        <div class="ruc">RUC: {{ $company->ruc ?? '' }}</div>
                    </div>
                </td>
                <td style="width: 45%; padding-left: 18px;">
                    <div class="voucher">
                        <div class="vtitle">NOTA DE VENTA</div>
                        <div class="vnumber">{{ $sale->number }}</div>
                        <hr>
                        <div class="meta-label">Fecha de emisión</div>
                        <div class="meta-value">{{ \Carbon\Carbon::parse($sale->date)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</div>
                        <div class="meta-label">Moneda</div>
                        <div class="meta-value">SOLES</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- CLIENT -->
        <div class="client">
            <div class="ctitle">DATOS DEL CLIENTE</div>
            <table>
                <tr>
                    <td>
                        <div class="lbl">Razón Social / Nombres y Apellidos</div>
                        <div class="val">{{ $sale->client->name }}</div>
                        <div class="lbl">{{ $sale->client->document_type ?? 'Documento' }}</div>
                        <div class="val">{{ $sale->client->document_number }}</div>
                    </td>
                    <td>
                        <div class="lbl">Dirección</div>
                        <div class="val">{{ $sale->client->address ?: '—' }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ITEMS -->
        <table class="items">
            <thead>
                <tr>
                    <th style="width: 6%;">#</th>
                    <th class="desc">Descripción</th>
                    <th style="width: 12%;">Cant.</th>
                    <th style="width: 18%;">Precio Unitario</th>
                    <th style="width: 18%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->saleDetails as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="desc">
                            {{ $item->article->title }}
                            @if($item->article->sku)
                                <span class="code">Código: {{ $item->article->sku }}</span>
                            @endif
                        </td>
                        <td>{{ number_format($item->quantity, 2) }}</td>
                        <td>S/ {{ number_format($item->price, 2) }}</td>
                        <td>S/ {{ number_format($item->price * $item->quantity, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- BOTTOM -->
        <table class="bottom">
            <tr>
                <td>
                    <div class="son">
                        <div class="lbl">SON:</div>
                        <div class="words">{{ $amountInWords }}</div>
                    </div>
                    <div class="addinfo">
                        <div class="ctitle">INFORMACIÓN ADICIONAL</div>
                        <div class="k">Condición de pago:</div>
                        <div class="v">{{ $sale->paymentMethod->name ?? 'Efectivo' }}</div>
                        <div class="k">Vendedor:</div>
                        <div class="v">{{ $sale->user->name ?? '' }}</div>
                        <div class="k">Representación impresa de la</div>
                        <div class="v">NOTA DE VENTA</div>
                    </div>
                </td>
                <td>
                    <table class="totals">
                        <tr>
                            <td class="lbl">Op. Gravadas</td>
                            <td class="amt">S/ {{ number_format($opGravadas, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">I.G.V. (18%)</td>
                            <td class="amt">S/ {{ number_format($sale->tax, 2) }}</td>
                        </tr>
                        @if($sale->delivery == 1 && $sale->delivery_fee > 0)
                        <tr>
                            <td class="lbl">Delivery</td>
                            <td class="amt">S/ {{ number_format($sale->delivery_fee, 2) }}</td>
                        </tr>
                        @endif
                        <tr class="sep sale">
                            <td class="lbl" style="padding-top:12px;">Precio de venta</td>
                            <td class="amt" style="padding-top:12px;">S/ {{ number_format($grandTotal, 2) }}</td>
                        </tr>
                    </table>
                    <div class="grandtotal">
                        <table>
                            <tr>
                                <td class="gl">TOTAL A PAGAR</td>
                                <td class="gv">S/ {{ number_format($grandTotal, 2) }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- FOOTER -->
    <div class="foot">
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
