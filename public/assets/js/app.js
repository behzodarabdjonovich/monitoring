/*
 * ADPI Monitoring — minimal vanilla JS (oflayn, tashqi kutubxonasiz).
 * - Mobil sidebar toggle (off-canvas).
 * - Profil menyusi dropdown.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var sidebar = document.getElementById('sidebar');
        var toggle = document.getElementById('sidebar-toggle');
        var backdrop = document.getElementById('sidebar-backdrop');

        function openSidebar() {
            if (!sidebar) return;
            sidebar.classList.add('is-open');
            if (backdrop) backdrop.hidden = false;
        }
        function closeSidebar() {
            if (!sidebar) return;
            sidebar.classList.remove('is-open');
            if (backdrop) backdrop.hidden = true;
        }

        if (toggle) {
            toggle.addEventListener('click', function () {
                if (sidebar && sidebar.classList.contains('is-open')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });
        }
        if (backdrop) {
            backdrop.addEventListener('click', closeSidebar);
        }

        // Profil menyusi dropdown.
        var userBtn = document.getElementById('user-menu-btn');
        var dropdown = document.getElementById('user-menu-dropdown');
        if (userBtn && dropdown) {
            userBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                var isOpen = !dropdown.hidden;
                dropdown.hidden = isOpen;
                userBtn.setAttribute('aria-expanded', String(!isOpen));
            });
            document.addEventListener('click', function () {
                dropdown.hidden = true;
                userBtn.setAttribute('aria-expanded', 'false');
            });
        }
    });
})();
