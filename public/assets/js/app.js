document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.sidebar');
    const toggle = document.querySelector('.sidebar-toggle');
    const overlay = document.querySelector('.sidebar-overlay');

    function openSidebar() {
        if (!sidebar) return;

        sidebar.classList.add('open');

        if (overlay) {
            overlay.classList.add('active');
        }
    }

    function closeSidebar() {
        if (!sidebar) return;

        sidebar.classList.remove('open');

        if (overlay) {
            overlay.classList.remove('active');
        }
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            if (sidebar && sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function (alert) {
        const delay = parseInt(alert.dataset.autoDismiss, 10) || 5000;

        setTimeout(function () {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity .25s ease';

            setTimeout(function () {
                alert.remove();
            }, 250);
        }, delay);
    });

    document.querySelectorAll('[data-confirm]').forEach(function (element) {
        element.addEventListener('click', function (event) {
            const message = element.dataset.confirm;

            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
});