

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.documentElement.classList.add('js');

function enhanceGlobalInteractions() {
    document.body?.classList.add('is-ready');

    const landingHeader = document.querySelector('.landing-page > header');
    if (landingHeader && document.querySelector('.landing-home-screens')) {
        document.documentElement.classList.add('landing-screen-scroll');
        const updateLandingHeight = () => document.documentElement.style.setProperty(
            '--landing-header-height', `${landingHeader.getBoundingClientRect().height}px`
        );
        updateLandingHeight();
        new ResizeObserver(updateLandingHeight).observe(landingHeader);
    }

    document.querySelectorAll('main:not(.auth-page)').forEach((node) => {
        node.classList.add('global-page-content');
    });

    document.querySelectorAll('main:not(.auth-page) .btn-primary, main:not(.auth-page) .btn-success').forEach((button) => {
        if (button.dataset.globalEnhanced !== undefined) return;
        button.dataset.globalEnhanced = 'true';
        button.style.position = button.style.position || 'relative';
        button.style.overflow = 'hidden';
        button.addEventListener('click', (event) => {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            const rect = button.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const ripple = document.createElement('span');
            ripple.className = 'global-ripple';
            ripple.style.width = size + 'px';
            ripple.style.height = size + 'px';
            ripple.style.left = (event.clientX - rect.left - size / 2) + 'px';
            ripple.style.top = (event.clientY - rect.top - size / 2) + 'px';
            button.appendChild(ripple);
            window.setTimeout(() => ripple.remove(), 600);
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', enhanceGlobalInteractions, { once: true });
} else {
    enhanceGlobalInteractions();
}

function showGlobalLoading(title = 'Sedang memuat', message = 'Mohon tunggu sebentar.') {
    const overlay = document.getElementById('global-loading');
    if (!overlay) return;
    const titleNode = document.getElementById('global-loading-title');
    const messageNode = document.getElementById('global-loading-message');
    if (titleNode) titleNode.textContent = title;
    if (messageNode) messageNode.textContent = message;
    overlay.hidden = false;
}

function hideGlobalLoading() {
    const overlay = document.getElementById('global-loading');
    if (overlay) overlay.hidden = true;
}

window.showGlobalLoading = showGlobalLoading;
window.hideGlobalLoading = hideGlobalLoading;

window.addEventListener('pageshow', (event) => {
    hideGlobalLoading();

    // A protected portal page can be restored from the browser's back-forward
    // cache. Reload it so the server validates the current session again.
    if (event.persisted && document.body?.dataset.privatePage !== undefined) {
        window.location.reload();
    }
});
document.addEventListener('global-loading:show', (event) => {
    showGlobalLoading(event.detail?.title, event.detail?.message);
});
document.addEventListener('global-loading:hide', hideGlobalLoading);

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.dataset.noLoading !== undefined) return;
    requestAnimationFrame(() => {
        if (event.defaultPrevented || !form.checkValidity()) return;
        showGlobalLoading(
            form.dataset.loadingTitle || 'Sedang memproses',
            form.dataset.loadingMessage || 'Mohon tunggu sebentar.'
        );
    });
}, true);

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    if (link.dataset.noLoading !== undefined || link.hasAttribute('download') || link.target === '_blank') return;

    const url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin || url.protocol === 'mailto:' || url.protocol === 'tel:') return;
    if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return;

    window.requestAnimationFrame(() => {
        if (event.defaultPrevented) return;
        showGlobalLoading(
            link.dataset.loadingTitle || 'Sedang membuka halaman',
            link.dataset.loadingMessage || 'Mohon tunggu sebentar.'
        );
    });
}, true);

function enhanceNativeSelects(root = document) {
    const selects = [...root.querySelectorAll('select:not([data-native-select]):not([data-enhanced-select])')]
        .filter((select) => (select.closest('main') || select.closest('.fee-modal')) && !select.multiple && !select.closest('nav, header, aside'));

    selects.forEach((select) => {
        const inlineMenu = Boolean(select.closest('[data-select-inline]'));
        select.dataset.enhancedSelect = 'true';

        const wrapper = document.createElement('div');
        wrapper.className = 'custom-select';
        wrapper.dataset.open = 'false';

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'custom-select-button';
        button.setAttribute('aria-haspopup', 'listbox');
        button.setAttribute('aria-expanded', 'false');

        const icon = document.createElement('span');
        icon.className = 'custom-select-icon';
        icon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="m6 9 6 6-6 6"/></svg>';

        const label = document.createElement('span');
        label.className = 'custom-select-label';

        const menu = document.createElement('div');
        menu.className = 'custom-select-menu';
        menu.setAttribute('role', 'listbox');
        menu.id = `spmb-select-menu-${Math.random().toString(36).slice(2)}`;
        button.setAttribute('aria-controls', menu.id);

        const syncLabel = () => {
            const selected = select.options[select.selectedIndex];
            label.textContent = selected?.textContent?.trim() || 'Pilih data';
            button.classList.toggle('is-placeholder', !select.value);
            menu.querySelectorAll('.custom-select-option').forEach((item) => {
                item.classList.toggle('is-selected', item.dataset.value === String(select.value));
            });
        };

        const close = () => {
            wrapper.dataset.open = 'false';
            button.setAttribute('aria-expanded', 'false');
            menu.classList.remove('is-open');
        };

        const positionMenu = () => {
            if (wrapper.dataset.open !== 'true') return;
            if (inlineMenu) {
                menu.style.position = 'absolute';
                menu.style.left = '0';
                menu.style.top = 'calc(100% + 6px)';
                menu.style.width = '100%';
                menu.style.maxHeight = '192px';
                menu.dataset.direction = 'down';
                return;
            }
            const rect = button.getBoundingClientRect();
            const gap = 8;
            const edge = 12;
            const spaceBelow = window.innerHeight - rect.bottom - edge;
            const spaceAbove = rect.top - edge;
            const maxHeight = window.innerWidth <= 640 ? 176 : 288;
            // Use the actual option list height. The previous maximum-height calculation
            // placed short menus far from their field whenever they opened upward.
            const desiredHeight = Math.min(maxHeight, Math.max(48, menu.scrollHeight || maxHeight));
            const openUp = spaceBelow < desiredHeight && spaceAbove > spaceBelow;
            const available = Math.max(48, openUp ? spaceAbove : spaceBelow);
            const height = Math.min(desiredHeight, available);
            const top = openUp
                ? Math.max(edge, rect.top - height - gap)
                : Math.min(window.innerHeight - edge - height, rect.bottom + gap);
            const width = Math.min(rect.width, window.innerWidth - edge * 2);
            const tone = getComputedStyle(wrapper);
            menu.style.setProperty('--spmb-select-accent', tone.getPropertyValue('--portal-accent').trim() || '#0b3b83');
            menu.style.setProperty('--spmb-select-deep', tone.getPropertyValue('--portal-accent-deep').trim() || '#07265d');
            menu.style.setProperty('--spmb-select-soft', tone.getPropertyValue('--portal-soft').trim() || '#e8f2ff');
            menu.style.setProperty('--spmb-select-line', tone.getPropertyValue('--portal-line').trim() || '#cbd5e1');
            menu.style.left = `${Math.min(Math.max(edge, rect.left), window.innerWidth - edge - width)}px`;
            menu.style.top = `${top}px`;
            menu.style.width = `${width}px`;
            menu.style.maxHeight = `${height}px`;
            menu.dataset.direction = openUp ? 'up' : 'down';
        };

        const buildOptions = () => {
            menu.innerHTML = '';
            Array.from(select.options).forEach((option) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'custom-select-option';
                item.textContent = option.textContent;
                item.dataset.value = option.value;
                item.setAttribute('role', 'option');
                item.disabled = option.disabled;
                item.addEventListener('click', () => {
                    select.value = option.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    syncLabel();
                    close();
                });
                menu.appendChild(item);
            });
            syncLabel();
        };

        const open = () => {
            document.querySelectorAll('.custom-select[data-open="true"]').forEach((node) => {
                if (node !== wrapper) node._spmbClose?.();
            });
            wrapper.dataset.open = 'true';
            button.setAttribute('aria-expanded', 'true');
            menu.classList.add('is-open');
            positionMenu();
        };

        button.addEventListener('click', () => wrapper.dataset.open === 'true' ? close() : open());
        select.addEventListener('change', syncLabel);
        new MutationObserver(buildOptions).observe(select, { childList: true, subtree: true });

        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);
        button.append(label, icon);
        wrapper.appendChild(button);
        (inlineMenu ? wrapper : document.body).appendChild(menu);
        select.classList.add('custom-select-native');
        wrapper._spmbClose = close;
        wrapper._spmbPositionMenu = positionMenu;
        buildOptions();
    });
}

function positionOpenNativeSelects() {
    document.querySelectorAll('.custom-select[data-open="true"]').forEach((select) => select._spmbPositionMenu?.());
}

document.addEventListener('scroll', positionOpenNativeSelects, true);
window.addEventListener('resize', positionOpenNativeSelects);

document.addEventListener('click', (event) => {
    if (event.target.closest('.custom-select, .custom-select-menu')) return;
    document.querySelectorAll('.custom-select[data-open="true"]').forEach((node) => {
        node._spmbClose?.();
    });
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => enhanceNativeSelects(), { once: true });
} else {
    enhanceNativeSelects();
}
window.enhanceNativeSelects = enhanceNativeSelects;

function startIdleLogout() {
    const logoutForm = document.querySelector('form[action$="/logout"]');
    if (!logoutForm || document.body?.dataset.disableIdleLogout !== undefined) return;

    const idleLimitMs = Number(document.body?.dataset.idleLogoutMs || 30 * 60 * 1000);
    const warningMs = Math.max(0, idleLimitMs - 30 * 1000);
    const token = document.querySelector('meta[name="csrf-token"]')?.content
        || logoutForm.querySelector('input[name="_token"]')?.value;
    let warningTimer = null;
    let logoutTimer = null;
    let lastActivityAt = Date.now();

    const showWarning = () => {
        const idleFor = Date.now() - lastActivityAt;
        if (idleFor < warningMs) {
            reset();
            return;
        }

        if (window.showGlobalLoading) {
            window.showGlobalLoading(
                'Sesi hampir habis',
                'Tidak ada aktivitas. Sistem akan keluar otomatis sebentar lagi.'
            );
        }
    };

    const logout = async () => {
        const idleFor = Date.now() - lastActivityAt;
        if (idleFor < idleLimitMs) {
            reset();
            return;
        }

        try {
            await fetch(logoutForm.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': token || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html, application/xhtml+xml',
                },
            });
        } catch (error) {
            // Tetap arahkan ke login walau request logout gagal karena sesi sudah kedaluwarsa.
        } finally {
            window.location.assign('/login?timeout=1');
        }
    };

    const reset = () => {
        lastActivityAt = Date.now();
        if (warningTimer) clearTimeout(warningTimer);
        if (logoutTimer) clearTimeout(logoutTimer);
        if (window.hideGlobalLoading) window.hideGlobalLoading();
        warningTimer = setTimeout(showWarning, warningMs);
        logoutTimer = setTimeout(logout, idleLimitMs);
    };

    ['click', 'keydown', 'pointerdown', 'pointermove', 'scroll', 'wheel', 'touchstart', 'input', 'change'].forEach((eventName) => {
        window.addEventListener(eventName, reset, { passive: true, capture: true });
    });

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) reset();
    });

    reset();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startIdleLogout, { once: true });
} else {
    startIdleLogout();
}
