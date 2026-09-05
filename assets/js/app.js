document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('mainSidebar');
    const toggle = document.getElementById('sidebarToggle');
    if (sidebar && toggle) {
        toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
        document.addEventListener('click', (event) => {
            if (window.innerWidth <= 991 && sidebar.classList.contains('open') && !sidebar.contains(event.target) && event.target !== toggle) {
                sidebar.classList.remove('open');
            }
        });
    }
});