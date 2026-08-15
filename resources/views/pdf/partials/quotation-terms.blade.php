<!-- CONDICIONES -->
@php
    $validityDays = (int) \Carbon\Carbon::parse($quotation->date)->diffInDays(\Carbon\Carbon::parse($quotation->valid_until));
@endphp
<div class="card terms">
    <div class="extra-title">
        <span class="ic icp"><svg viewBox="0 0 24 24"><path d="M12 2 4 5v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V5l-8-3Zm-1.2 14.5-3.6-3.6 1.4-1.4 2.2 2.2 4.8-4.8 1.4 1.4-6.2 6.2Z"/></svg></span>Condiciones de la cotización
    </div>
    <table class="terms-grid">
        <tr>
            <td class="tleft">
                <div class="term-title">Condiciones de pago</div>
                <div class="term-text">
                    <span class="term-check">✓</span> Adelanto del 50% para iniciar el pedido.<br>
                    <span class="term-check">✓</span> Saldo del 50% antes del despacho o entrega.<br>
                    <span class="term-check">✓</span> La cotización tiene una validez de {{ $validityDays }} días.
                </div>
                <div class="term-title">Tiempo de entrega</div>
                <div class="term-text">Tiempo estimado: {{ $quotation->delivery_time_text }}</div>
            </td>
            <td class="tright">
                <div class="term-title">Cancelaciones</div>
                <div class="term-text">Una vez realizado el pedido al proveedor, el adelanto no es reembolsable.</div>
                <div class="term-title">Cambios y devoluciones</div>
                <div class="term-text">Al tratarse de un producto solicitado especialmente para el cliente, no se aceptan devoluciones por cambio de opinión.</div>
            </td>
        </tr>
    </table>
</div>
