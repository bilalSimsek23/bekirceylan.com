(function () {
    'use strict';

    // Mobil menü
    var toggle = document.querySelector('[data-nav-toggle]');
    var nav = document.querySelector('[data-nav]');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        nav.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () { nav.classList.remove('is-open'); });
        });
    }

    // Hero slider
    var slider = document.querySelector('[data-slider]');
    if (slider) {
        var slides = slider.querySelectorAll('.hero-slide');
        var current = 0, timer;
        var show = function (i) {
            slides[current].classList.remove('is-active');
            current = (i + slides.length) % slides.length;
            slides[current].classList.add('is-active');
        };
        var start = function () {
            clearInterval(timer);
            if (slides.length > 1) timer = setInterval(function () { show(current + 1); }, 6000);
        };
        var prev = slider.querySelector('[data-slide-prev]');
        var next = slider.querySelector('[data-slide-next]');
        if (prev) prev.addEventListener('click', function () { show(current - 1); start(); });
        if (next) next.addEventListener('click', function () { show(current + 1); start(); });
        start();
    }

    // Kategori filtresi (ana sayfa)
    var grid = document.querySelector('[data-tour-grid]');
    var filterEmpty = document.querySelector('[data-filter-empty]');
    document.querySelectorAll('button[data-filter]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('button[data-filter]').forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            var f = btn.getAttribute('data-filter');
            var visible = 0;
            grid.querySelectorAll('.tour-card').forEach(function (card) {
                var match = f === 'all' || card.getAttribute('data-category') === f;
                card.hidden = !match;
                if (match) visible++;
            });
            if (filterEmpty) filterEmpty.hidden = visible > 0;
        });
    });

    // "Bilgi Al" butonu: formda turu seç ve forma kaydır
    document.querySelectorAll('[data-inquire]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            var select = document.querySelector('[data-tour-select]');
            var form = document.getElementById('iletisim');
            if (!select || !form) return;
            e.preventDefault();
            select.value = btn.getAttribute('data-inquire');
            form.scrollIntoView({ behavior: 'smooth' });
            setTimeout(function () { var n = document.querySelector('[data-inquiry-form] input[name=name]'); if (n) n.focus({ preventScroll: true }); }, 600);
        });
    });

    // Bilgi formu: veritabanına kaydet + WhatsApp aç
    var form = document.querySelector('[data-inquiry-form]');
    if (form && window.fetch) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var errorBox = form.querySelector('[data-form-error]');
            errorBox.hidden = true;

            if (!form.checkValidity()) {
                errorBox.textContent = 'Lütfen ad soyad, telefon alanlarını doldurup KVKK onayını işaretleyin.';
                errorBox.hidden = false;
                return;
            }

            var button = form.querySelector('button[type=submit]');
            button.disabled = true;
            // Pop-up engelleyiciye takılmamak için pencereyi tıklama anında aç
            var waWindow = window.open('', '_blank');

            fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form)
            }).then(function (res) {
                return res.json().then(function (data) { return { ok: res.ok, data: data }; });
            }).then(function (r) {
                if (!r.ok) {
                    if (waWindow) waWindow.close();
                    var msg = r.data && r.data.errors ? Object.values(r.data.errors)[0][0] : (r.data.message || 'Bir hata oluştu.');
                    errorBox.textContent = msg;
                    errorBox.hidden = false;
                    return;
                }
                if (waWindow) { waWindow.location.href = r.data.whatsapp; } else { window.location.href = r.data.whatsapp; }
                form.reset();
                var ok = document.createElement('div');
                ok.className = 'alert alert-success';
                ok.innerHTML = 'Teşekkürler! Talebiniz alındı. WhatsApp açılmadıysa <a target="_blank" rel="noopener" href="' + r.data.whatsapp + '">buraya tıklayın</a>.';
                form.insertBefore(ok, form.querySelector('h3').nextSibling);
            }).catch(function () {
                if (waWindow) waWindow.close();
                errorBox.textContent = 'Bağlantı hatası. Lütfen tekrar deneyin veya WhatsApp üzerinden yazın.';
                errorBox.hidden = false;
            }).finally(function () {
                button.disabled = false;
            });
        });
    }
})();
