document.addEventListener('DOMContentLoaded', () => {
    const standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    if (standalone) {
        document.documentElement.classList.add('is-standalone');
    }

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js').then((reg) => reg.update());
    }

    const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);

    const banner = document.getElementById('installBanner');
    const installButton = document.getElementById('installApp');
    const dismissButton = document.getElementById('dismissInstall');
    let deferredPrompt = null;

    if (isIos && !standalone && banner && !sessionStorage.getItem('hide-install')) {
        const label = banner.querySelector('span');
        if (label) {
            label.textContent = 'No iPhone: Compartilhar → Adicionar à Tela de Início';
        }
        if (installButton) {
            installButton.hidden = true;
        }
        banner.hidden = false;
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
        if (!standalone && banner && !sessionStorage.getItem('hide-install')) {
            banner.hidden = false;
        }
    });

    installButton?.addEventListener('click', async () => {
        if (!deferredPrompt) {
            return;
        }
        deferredPrompt.prompt();
        await deferredPrompt.userChoice;
        deferredPrompt = null;
        if (banner) {
            banner.hidden = true;
        }
    });

    dismissButton?.addEventListener('click', () => {
        sessionStorage.setItem('hide-install', '1');
        if (banner) {
            banner.hidden = true;
        }
    });
});
