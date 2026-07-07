<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8" />
<title>Etiqueta - {{ $sale->number }}</title>
<style>
    {!! $fontFace ?? '' !!}

    * { box-sizing: border-box; margin: 0; padding: 0; }

    @page {
        size: 100mm 150mm;
        margin: 0;
    }

    html, body {
        width: 100mm;
        font-family: 'Poppins', Arial, Helvetica, sans-serif;
        color: #111;
        background: #fff;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .label {
        width: 100mm;
        height: 150mm;
        border: 1.5px solid #111;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    /* En pantalla, centrar y dar contexto */
    @media screen {
        body { background: #f1f1f4; padding: 20px; display: flex; justify-content: center; }
        .label { background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,.18); }
    }

    .header {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        padding: 10px 12px 8px;
        border-bottom: 1.5px solid #111;
    }
    .header .logo { width: 42px; height: 42px; flex: 0 0 auto; }
    .header .info { font-size: 9.5px; line-height: 1.45; }
    .header .info .seller { font-size: 10.5px; }
    .header .info .seller b { font-weight: 700; }
    .header .ids { margin-top: 3px; font-size: 9.5px; }
    .header .ids b { font-weight: 700; }
    .header .ids .big { font-weight: 700; font-size: 11px; }

    .band {
        text-align: center;
        font-weight: 700;
        font-size: 15px;
        padding: 7px 8px;
        border-bottom: 1.5px solid #111;
    }

    .delivery-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 7px 22px;
        border-bottom: 1.5px solid #111;
        font-size: 15px;
    }
    .delivery-row .lbl { font-weight: 400; }
    .delivery-row .val { font-weight: 700; font-size: 17px; }

    .qr-zone {
        display: flex;
        align-items: center;
        border-bottom: 1.5px solid #111;
        padding: 10px 12px;
        min-height: 42mm;
    }
    .qr-zone .qr { width: 34mm; height: 34mm; margin-left: 4mm; }
    .qr-zone .zone {
        flex: 1;
        text-align: center;
        font-weight: 700;
        font-size: 16px;
        line-height: 1.35;
        text-transform: uppercase;
        padding: 0 6px;
        word-break: break-word;
    }

    .type-band {
        text-align: center;
        font-weight: 700;
        font-size: 19px;
        letter-spacing: .5px;
        padding: 8px;
        border-bottom: 1.5px solid #111;
        text-transform: uppercase;
    }

    .details {
        flex: 1;
        padding: 12px 12px 8px;
        font-size: 11.5px;
        line-height: 1.4;
    }
    .details .row { margin-bottom: 8px; }
    .details .row b { font-weight: 700; font-size: 10.5px; }
</style>
</head>
<body>
<div class="label">
    <div class="header">
        <img class="logo" src="{{ $logo }}" alt="logo">
        <div class="info">
            <div class="seller"><b>{{ $company->company ?? 'ShiperSales' }}</b> #{{ $sale->number }}</div>
            <div>{{ $company->address ?? '' }}</div>
            <div>{{ trim(($company->city ?? '').', '.($company->country ?? 'Perú'), ', ') }}</div>
            <div class="ids">
                <b>Orden:</b> <span class="big">{{ $sale->number }}</span>
                &nbsp;<b>Venta:</b> <span class="big">{{ $sale->id }}</span>
            </div>
        </div>
    </div>

    <div class="band">{{ $sale->contact->name ?? 'Despacho' }}</div>

    <div class="delivery-row">
        <span class="lbl">Entrega:</span>
        <span class="val">{{ \Carbon\Carbon::parse($sale->date)->locale('es')->isoFormat('DD-MMM') }}</span>
    </div>

    <div class="qr-zone">
        <img class="qr" src="{{ $qr }}" alt="QR {{ $sale->number }}">
        <div class="zone">
            {{ $district ?? ($province ?? '') }}<br>
            {{ $department ?? '' }}
        </div>
    </div>

    <div class="type-band">Despacho a domicilio</div>

    <div class="details">
        <div class="row"><b>Direccion:</b> {{ $sale->client->address ?: '—' }}</div>
        <div class="row"><b>Referencia:</b> {{ $reference ?: '—' }}</div>
        <div class="row"><b>Distrito:</b> {{ $district ?? '—' }}</div>
        <div class="row"><b>Destinatario:</b> {{ $sale->client->name }} @if($sale->client->phone)({{ $sale->client->phone }})@endif</div>
        @if(!empty($comment))
            <div class="row"><b>Comentario:</b> {{ $comment }}</div>
        @endif
    </div>
</div>

<script>
    window.addEventListener('load', function () {
        window.print();
    });
</script>
</body>
</html>
