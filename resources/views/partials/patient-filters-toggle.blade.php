@once
<style>
    .queue-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .patient-filters-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    .patient-filters-toolbar .toolbar-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .filter-toggle-btn {
        appearance: none;
        -webkit-appearance: none;
        font-family: inherit;
        line-height: 1.2;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 12px;
        border-radius: 999px;
        border: 1px solid rgba(148, 163, 184, 0.45);
        background: rgba(255, 255, 255, 0.9);
        color: #1e2b36;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        cursor: pointer;
        transition: all 0.18s ease;
        flex-shrink: 0;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    }

    .filter-toggle-btn:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
    }

    .filter-toggle-btn.has-active-filters {
        border-color: rgba(37, 99, 235, 0.45);
        background: rgba(239, 246, 255, 0.95);
        color: #1d4ed8;
    }

    .filter-toggle-badge {
        display: none;
        align-items: center;
        justify-content: center;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        border-radius: 999px;
        background: #2563eb;
        color: #fff;
        font-size: 0.68rem;
        font-weight: 700;
        line-height: 1;
    }

    .filter-toggle-btn.has-active-filters .filter-toggle-badge {
        display: inline-flex;
    }

    .filter-toggle-chevron {
        font-size: 0.7rem;
        transition: transform 0.2s ease;
    }

    .filter-toggle-btn.is-open .filter-toggle-chevron {
        transform: rotate(180deg);
    }

    .filter-collapsible-body.is-collapsed {
        display: none !important;
    }
</style>
<script>
    (function () {
        if (window.__philarmyFiltersBound) return;
        window.__philarmyFiltersBound = true;

        function resolveTarget(btn) {
            var id = btn.getAttribute('data-filter-target');
            return id ? document.getElementById(id) : null;
        }

        function countActive(panel) {
            if (!panel) return 0;
            var count = 0;
            panel.querySelectorAll('select').forEach(function (sel) {
                var v = (sel.value || '').trim();
                if (v !== '' && v !== 'All' && v !== 'ALL' && v !== '0') count++;
            });
            panel.querySelectorAll('input[type="text"], input[type="search"]').forEach(function (inp) {
                if ((inp.value || '').trim() !== '') count++;
            });
            return count;
        }

        function syncBadge(btn, panel) {
            var badge = btn.querySelector('[data-filter-count]');
            var activeCount = countActive(panel);
            btn.classList.toggle('has-active-filters', activeCount > 0);
            if (badge) badge.textContent = String(activeCount);
        }

        function setOpen(btn, panel, open) {
            panel.classList.toggle('is-collapsed', !open);
            panel.style.display = open ? '' : 'none';
            btn.classList.toggle('is-open', open);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            var label = btn.querySelector('[data-filter-toggle-label]');
            if (label) label.textContent = open ? 'Hide Filters' : 'Filters';
        }

        function init() {
            document.querySelectorAll('[data-filter-toggle]').forEach(function (btn) {
                var panel = resolveTarget(btn);
                if (!panel) return;
                setOpen(btn, panel, false);
                syncBadge(btn, panel);
                panel.addEventListener('change', function () { syncBadge(btn, panel); });
                panel.addEventListener('input', function () { syncBadge(btn, panel); });
            });
        }

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-filter-toggle]');
            if (!btn) return;
            e.preventDefault();
            var panel = resolveTarget(btn);
            if (!panel) return;
            var isOpen = !panel.classList.contains('is-collapsed') && panel.style.display !== 'none';
            setOpen(btn, panel, !isOpen);
        });

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>
@endonce
<button type="button"
    class="filter-toggle-btn"
    data-filter-toggle
    data-filter-target="{{ $targetId }}"
    data-filter-storage="{{ $storageKey ?? $targetId }}"
    aria-controls="{{ $targetId }}"
    aria-expanded="false">
    <i class="fas fa-sliders-h"></i>
    <span data-filter-toggle-label>Filters</span>
    <span class="filter-toggle-badge" data-filter-count>0</span>
    <i class="fas fa-chevron-down filter-toggle-chevron"></i>
</button>
