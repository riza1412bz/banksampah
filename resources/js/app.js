// Menu mobile (hamburger) di layouts/app.blade.php.
// Toggle ARIA + kelas .terbuka; tutup saat pilih link, Escape, klik luar,
// atau saat viewport naik ke desktop.
(function () {
    'use strict';

    var btn = document.querySelector('[aria-controls="menu-mobile"]');
    if (!btn) return;
    var panel = document.getElementById('menu-mobile');
    if (!panel) return;

    var TERBUKA = 'terbuka';

    function terbuka() {
        return btn.getAttribute('aria-expanded') === 'true';
    }

    function setTerbuka(buka) {
        btn.setAttribute('aria-expanded', String(buka));
        btn.setAttribute('aria-label', buka ? 'Tutup menu' : 'Buka menu');
        var menu = btn.querySelector('.ikon-menu');
        var tutup = btn.querySelector('.ikon-tutup');
        if (menu) menu.classList.toggle('hidden', buka);
        if (tutup) tutup.classList.toggle('hidden', !buka);
        panel.classList.toggle(TERBUKA, buka);
        panel.setAttribute('aria-hidden', String(!buka));
        if (buka) {
            panel.removeAttribute('inert');
        } else {
            panel.setAttribute('inert', '');
        }
    }

    btn.addEventListener('click', function () {
        setTerbuka(!terbuka());
    });

    // Setelah klik link / tombol Keluar di dalam panel, tutup menu.
    panel.addEventListener('click', function (e) {
        if (e.target.closest('a, button')) setTerbuka(false);
    });

    // Escape: tutup & fokuskan kembali tombol.
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && terbuka()) {
            setTerbuka(false);
            btn.focus();
        }
    });

    // Klik di luar panel & tombol: tutup.
    document.addEventListener('click', function (e) {
        if (!terbuka()) return;
        if (!panel.contains(e.target) && !btn.contains(e.target)) setTerbuka(false);
    });

    // Naik ke desktop (>=1024px): reset supaya state tidak nyangkut terbuka.
    window.matchMedia('(min-width: 1024px)').addEventListener('change', function (e) {
        if (e.matches) setTerbuka(false);
    });
})();
