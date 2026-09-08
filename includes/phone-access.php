<?php
$phoneUrl = phoneAccessUrl('login.php');
$portalUrl = phoneAccessUrl('portal.php');
$agendaUrl = phoneAccessUrl('agendar.php');
?>
<section class="phone-access">
    <p class="eyebrow">TESTE NO CELULAR</p>
    <strong>Não use localhost no telefone</strong>
    <p class="muted">Celular e computador no mesmo Wi-Fi. No telefone abra:</p>
    <input class="form-control" id="phoneAccessUrl" value="<?= e($phoneUrl) ?>" readonly>
    <canvas class="pix-canvas" id="phoneAccessQr"></canvas>
    <div class="welcome-actions">
        <button class="btn btn-light btn-sm" type="button" id="copyPhoneAccess">Copiar endereço</button>
        <a class="btn btn-whatsapp" href="<?= e(whatsappLink('', 'Abra o app no celular: ' . $phoneUrl)) ?>" target="_blank" rel="noopener">Mandar no WhatsApp</a>
    </div>
    <small class="muted">Portal: <?= e($portalUrl) ?><br>Agendar: <?= e($agendaUrl) ?></small>
</section>
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
(() => {
    const url = document.getElementById('phoneAccessUrl')?.value;
    const canvas = document.getElementById('phoneAccessQr');
    if (url && canvas && typeof QRCode !== 'undefined') {
        QRCode.toCanvas(canvas, url, { width: 180, margin: 1, color: { dark: '#1f5e50', light: '#ffffff' } });
    }
    document.getElementById('copyPhoneAccess')?.addEventListener('click', () => {
        navigator.clipboard.writeText(url).then(() => {
            document.getElementById('copyPhoneAccess').textContent = 'Copiado';
        });
    });
})();
</script>
