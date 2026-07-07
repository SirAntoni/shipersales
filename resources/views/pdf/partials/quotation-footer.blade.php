<!-- FOOTER -->
<div class="footer">
    <table class="footgrid">
        <tr>
            <td class="secure-col">
                <table class="secure">
                    <tr>
                        <td class="ic icp"><svg viewBox="0 0 24 24"><path d="M12 2 4 5v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V5l-8-3Zm4.7 7.8-5.4 5.4-2.7-2.7L10 11l1.3 1.3 4-4 1.4 1.5Z"/></svg></td>
                        <td>
                            <div class="secure-title">Cotización formal</div>
                            <div class="secure-text">Documento generado electrónicamente. No constituye comprobante de pago.</div>
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

<div class="footbar">¡Gracias por su preferencia!<span class="orange"></span></div>
