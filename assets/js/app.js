document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('mainSidebar');
    const toggles = [document.getElementById('sidebarToggle'), document.getElementById('sidebarToggleTab')].filter(Boolean);
    if (sidebar && toggles.length) {
        toggles.forEach((toggle) => toggle.addEventListener('click', () => sidebar.classList.toggle('open')));
        document.addEventListener('click', (event) => {
            const clickedToggle = toggles.some((toggle) => toggle === event.target || toggle.contains(event.target));
            if (window.innerWidth <= 991 && sidebar.classList.contains('open') && !sidebar.contains(event.target) && !clickedToggle) {
                sidebar.classList.remove('open');
            }
        });
    }
});