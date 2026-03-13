/**
 * Mini WMS – Vanilla JS
 */
(function () {
    'use strict';

    /* ── Flash alert dismiss & auto-dismiss ────────────────────────────────── */
    function initAlerts() {
        document.querySelectorAll('.alert-close').forEach(function (btn) {
            btn.addEventListener('click', function () {
                dismissAlert(btn.closest('.alert'));
            });
        });

        document.querySelectorAll('.alert-success').forEach(function (alert) {
            setTimeout(function () { dismissAlert(alert); }, 4000);
        });
    }

    function dismissAlert(alert) {
        if (!alert || !alert.parentNode) return;
        alert.style.transition = 'opacity .3s, transform .3s';
        alert.style.opacity    = '0';
        alert.style.transform  = 'translateX(20px)';
        setTimeout(function () { alert.remove(); }, 320);
    }

    /* ── Confirm dangerous actions ─────────────────────────────────────────── */
    function initConfirm() {
        document.querySelectorAll('[data-confirm]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                if (!window.confirm(el.dataset.confirm)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        });
    }

    /* ── Disable submit + show spinner ────────────────────────────────────── */
    function initSubmitSpinner() {
        document.querySelectorAll('form[data-spinner]').forEach(function (form) {
            form.addEventListener('submit', function () {
                var btn = form.querySelector('[type="submit"]');
                if (!btn) return;
                btn.disabled = true;
                var label = btn.querySelector('.btn-label');
                var spinner = btn.querySelector('.spinner');
                if (label)   label.textContent = btn.dataset.savingText || 'Saving…';
                if (spinner) spinner.style.display = 'inline-block';
            });
        });
    }

    /* ── Auto-generate order reference ────────────────────────────────────── */
    function initOrderRef() {
        var refInput = document.getElementById('reference');
        if (!refInput || refInput.value) return;
        var now   = new Date();
        var y     = now.getFullYear();
        var m     = String(now.getMonth() + 1).padStart(2, '0');
        var d     = String(now.getDate()).padStart(2, '0');
        var rand  = String(Math.floor(Math.random() * 900) + 100);
        refInput.placeholder = 'ORD-' + y + '-' + m + d + '-' + rand;
    }

    /* ── Copy order reference button ───────────────────────────────────────── */
    function initCopyButton() {
        document.querySelectorAll('[data-copy]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var text = btn.dataset.copy;
                if (!text) return;
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(function () { showCopied(btn); });
                } else {
                    var ta = document.createElement('textarea');
                    ta.value = text;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    ta.remove();
                    showCopied(btn);
                }
            });
        });
    }

    function showCopied(btn) {
        var original = btn.innerHTML;
        btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>';
        btn.classList.add('btn-success-flash');
        setTimeout(function () {
            btn.innerHTML = original;
            btn.classList.remove('btn-success-flash');
        }, 1800);
    }

    /* ── Table rows clickable ─────────────────────────────────────────────── */
    function initTableLinks() {
        document.querySelectorAll('tr[data-href]').forEach(function (row) {
            row.style.cursor = 'pointer';
            row.addEventListener('click', function (e) {
                if (e.target.closest('a, button, form')) return;
                window.location.href = row.dataset.href;
            });
        });
    }

    /* ── Active status tab (orders) ───────────────────────────────────────── */
    function initStatusTabs() {
        var params = new URLSearchParams(window.location.search);
        var status = params.get('status') || 'all';
        document.querySelectorAll('.tab-link').forEach(function (tab) {
            var tabStatus = tab.dataset.status || tab.getAttribute('href').match(/status=([^&]*)/)?.[1] || 'all';
            tab.classList.toggle('active', tabStatus === status);
        });
    }

    /* ── Stock warning on stock_move_new ─────────────────────────────────── */
    function initStockWarning() {
        var select  = document.getElementById('product_id');
        var qtyIn   = document.getElementById('qty');
        var warning = document.getElementById('stock-warning');
        var reason  = document.getElementById('reason');
        if (!select || !qtyIn || !warning) return;

        function check() {
            var opt   = select.options[select.selectedIndex];
            var stock = parseInt(opt ? opt.dataset.stock : '0', 10) || 0;
            var qty   = parseInt(qtyIn.value, 10) || 0;
            var isOut = reason && reason.value === 'manual_out';
            if (isOut && qty > stock) {
                warning.textContent = 'Warning: quantity (' + qty + ') exceeds available stock (' + stock + ').';
                warning.classList.add('visible');
            } else {
                warning.classList.remove('visible');
            }
        }

        select.addEventListener('change', check);
        qtyIn.addEventListener('input', check);
        if (reason) reason.addEventListener('change', check);
    }

    /* ── Mobile sidebar toggle ────────────────────────────────────────────── */
    function initSidebar() {
        var toggle  = document.getElementById('nav-toggle');
        var sidebar = document.getElementById('sidebar');
        var overlay = document.getElementById('sidebar-overlay');
        if (!toggle || !sidebar) return;

        function openSidebar() {
            sidebar.classList.add('open');
            if (overlay) overlay.classList.add('visible');
            document.body.style.overflow = 'hidden';
        }
        function closeSidebar() {
            sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('visible');
            document.body.style.overflow = '';
        }

        toggle.addEventListener('click', function () {
            sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
        });
        if (overlay) overlay.addEventListener('click', closeSidebar);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeSidebar();
        });
    }

    /* ── Init ─────────────────────────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        initAlerts();
        initConfirm();
        initSubmitSpinner();
        initOrderRef();
        initCopyButton();
        initTableLinks();
        initStatusTabs();
        initStockWarning();
        initSidebar();
    });
})();
