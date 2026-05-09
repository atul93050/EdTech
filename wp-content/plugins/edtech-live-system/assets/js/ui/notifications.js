(function() {
    'use strict';

    var root = window.EDTECH_UI || {};
    var positions = {};
    var icons = {
        success: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        error: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8v5m0 4h.01M10.3 3.9 2.7 17a2 2 0 0 0 1.7 3h15.2a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        warning: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4m0 4h.01M10.3 4 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 4a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        info: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 16v-4m0-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        loading: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3.2-6.9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>'
    };

    function getViewport(position) {
        position = position || 'top-right';

        if (positions[position]) {
            return positions[position];
        }

        var viewport = document.createElement('div');
        viewport.className = 'edtech-toast-viewport';
        viewport.dataset.position = position;
        viewport.setAttribute('aria-live', 'polite');
        viewport.setAttribute('aria-relevant', 'additions');
        document.body.appendChild(viewport);
        positions[position] = viewport;
        return viewport;
    }

    function normalizeOptions(type, message, options) {
        if (typeof message === 'object' && message !== null) {
            options = message;
            message = options.message;
        }

        options = options || {};

        return {
            type: type || options.type || 'info',
            title: options.title || type.charAt(0).toUpperCase() + type.slice(1),
            message: message || options.message || '',
            duration: options.duration === 0 ? 0 : options.duration || 4200,
            position: options.position || 'top-right',
            icon: options.icon || icons[type] || icons.info,
            actions: options.actions || []
        };
    }

    function closeToast(toast) {
        if (!toast || toast.classList.contains('is-leaving')) {
            return;
        }

        toast.classList.add('is-leaving');
        window.setTimeout(function() {
            toast.remove();
        }, 230);
    }

    function showToast(type, message, options) {
        var opts = normalizeOptions(type, message, options);
        var viewport = getViewport(opts.position);
        var toast = document.createElement('div');
        var progress = '';

        toast.className = 'edtech-toast edtech-toast--' + opts.type;
        toast.setAttribute('role', opts.type === 'error' ? 'alert' : 'status');
        toast.tabIndex = -1;

        if (opts.duration > 0) {
            progress = '<span class="edtech-toast__progress" style="animation-duration:' + opts.duration + 'ms"></span>';
        }

        toast.innerHTML =
            '<div class="edtech-toast__icon">' + opts.icon + '</div>' +
            '<div class="edtech-toast__content">' +
                '<div class="edtech-toast__title">' + escapeHtml(opts.title) + '</div>' +
                '<p class="edtech-toast__message">' + escapeHtml(opts.message) + '</p>' +
                renderActions(opts.actions) +
            '</div>' +
            '<button type="button" class="edtech-toast__close" aria-label="Close notification">×</button>' +
            progress;

        viewport.appendChild(toast);
        window.requestAnimationFrame(function() {
            toast.classList.add('is-visible');
        });

        toast.querySelector('.edtech-toast__close').addEventListener('click', function() {
            closeToast(toast);
        });

        toast.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeToast(toast);
            }
        });

        toast.querySelectorAll('[data-edtech-toast-action]').forEach(function(button) {
            button.addEventListener('click', function() {
                var action = opts.actions[parseInt(button.dataset.edtechToastAction, 10)];
                if (action && typeof action.onClick === 'function') {
                    action.onClick(toast);
                }
                closeToast(toast);
            });
        });

        if (opts.duration > 0) {
            window.setTimeout(function() {
                closeToast(toast);
            }, opts.duration);
        }

        return {
            element: toast,
            close: function() {
                closeToast(toast);
            }
        };
    }

    function renderActions(actions) {
        if (!actions || !actions.length) {
            return '';
        }

        return '<div class="edtech-toast__actions">' + actions.map(function(action, index) {
            return '<button type="button" class="edtech-toast__action" data-edtech-toast-action="' + index + '">' + escapeHtml(action.label || 'Action') + '</button>';
        }).join('') + '</div>';
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function(match) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[match];
        });
    }

    root.showToast = showToast;
    root.showSuccess = function(message, options) { return showToast('success', message, options); };
    root.showError = function(message, options) { return showToast('error', message, options); };
    root.showInfo = function(message, options) { return showToast('info', message, options); };
    root.showWarning = function(message, options) { return showToast('warning', message, options); };
    root.showLoader = function(message, options) {
        options = options || {};
        options.duration = options.duration === undefined ? 0 : options.duration;
        options.title = options.title || 'Working';
        return showToast('loading', message || 'Please wait...', options);
    };
    root.escapeHtml = escapeHtml;

    window.EDTECH_UI = root;
})();
