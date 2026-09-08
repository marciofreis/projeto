document.addEventListener('DOMContentLoaded', () => {
    const drawQr = (canvas, payload) => {
        if (!canvas || !payload || typeof QRCode === 'undefined') {
            return;
        }
        QRCode.toCanvas(canvas, payload, { width: 220, margin: 1, color: { dark: '#1f5e50', light: '#ffffff' } });
    };

    document.querySelectorAll('.js-pix-auto').forEach((canvas) => {
        drawQr(canvas, canvas.dataset.payload || '');
    });

    document.querySelectorAll('.js-copy-payload').forEach((button) => {
        button.addEventListener('click', () => {
            const area = button.parentElement.querySelector('textarea');
            if (!area) {
                return;
            }
            navigator.clipboard.writeText(area.value).then(() => {
                button.textContent = 'Código copiado';
            });
        });
    });

    const modalElement = document.getElementById('pixModal');
    const payloadField = document.getElementById('pixPayload');
    const canvas = document.getElementById('pixCanvas');
    const valueLabel = document.getElementById('pixValueLabel');
    const copyButton = document.getElementById('copyPix');
    if (!modalElement || !payloadField || !canvas) {
        return;
    }

    const modal = window.bootstrap ? window.bootstrap.Modal.getOrCreateInstance(modalElement) : null;
    document.querySelectorAll('.js-open-pix').forEach((button) => {
        button.addEventListener('click', () => {
            payloadField.value = button.dataset.payload || '';
            if (valueLabel) {
                valueLabel.textContent = button.dataset.value || '';
            }
            drawQr(canvas, payloadField.value);
            modal?.show();
        });
    });

    copyButton?.addEventListener('click', () => {
        navigator.clipboard.writeText(payloadField.value).then(() => {
            copyButton.textContent = 'Código copiado';
        });
    });
});
