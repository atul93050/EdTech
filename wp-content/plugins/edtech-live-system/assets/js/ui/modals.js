(function() {
    'use strict';

    var root = window.EDTECH_UI || {};
    var activeModal = null;
    var previousFocus = null;

    function showConfirmModal(options) {
        options = options || {};

        return new Promise(function(resolve) {
            closeActive(false);
            previousFocus = document.activeElement;

            var backdrop = document.createElement('div');
            backdrop.className = 'edtech-modal-backdrop';
            backdrop.innerHTML =
                '<div class="edtech-modal" role="dialog" aria-modal="true" aria-labelledby="edtech-modal-title" aria-describedby="edtech-modal-message">' +
                    '<div class="edtech-modal__body">' +
                        '<div class="edtech-modal__icon">' + getIcon(options.variant || 'info') + '</div>' +
                        '<h2 class="edtech-modal__title" id="edtech-modal-title">' + root.escapeHtml(options.title || 'Confirm action') + '</h2>' +
                        '<p class="edtech-modal__message" id="edtech-modal-message">' + root.escapeHtml(options.message || 'Are you sure you want to continue?') + '</p>' +
                    '</div>' +
                    '<div class="edtech-modal__actions">' +
                        '<button type="button" class="edtech-modal__button edtech-modal__button--cancel" data-edtech-modal-cancel>' + root.escapeHtml(options.cancelText || 'Cancel') + '</button>' +
                        '<button type="button" class="edtech-modal__button edtech-modal__button--confirm ' + (options.variant === 'danger' ? 'edtech-modal__button--danger' : '') + '" data-edtech-modal-confirm>' + root.escapeHtml(options.confirmText || 'Confirm') + '</button>' +
                    '</div>' +
                '</div>';

            document.body.appendChild(backdrop);
            activeModal = { backdrop: backdrop, resolve: resolve };

            window.requestAnimationFrame(function() {
                backdrop.classList.add('is-visible');
                var confirmButton = backdrop.querySelector('[data-edtech-modal-confirm]');
                if (confirmButton) {
                    confirmButton.focus();
                }
            });

            backdrop.addEventListener('click', function(event) {
                if (event.target === backdrop) {
                    closeActive(false);
                }
            });

            backdrop.querySelector('[data-edtech-modal-cancel]').addEventListener('click', function() {
                closeActive(false);
            });

            backdrop.querySelector('[data-edtech-modal-confirm]').addEventListener('click', function() {
                closeActive(true);
            });

            document.addEventListener('keydown', onKeydown);
        });
    }

    function closeActive(result) {
        if (!activeModal) {
            return;
        }

        var modal = activeModal;
        activeModal = null;
        document.removeEventListener('keydown', onKeydown);
        modal.backdrop.classList.remove('is-visible');

        window.setTimeout(function() {
            modal.backdrop.remove();
            if (previousFocus && typeof previousFocus.focus === 'function') {
                previousFocus.focus();
            }
            modal.resolve(!!result);
        }, 180);
    }

    function onKeydown(event) {
        if (event.key === 'Escape') {
            closeActive(false);
        }

        if (event.key !== 'Tab' || !activeModal) {
            return;
        }

        var focusable = activeModal.backdrop.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (!focusable.length) {
            return;
        }

        var first = focusable[0];
        var last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    function getIcon(variant) {
        if (variant === 'danger') {
            return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 9v4m0 4h.01M10.3 4 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 4a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        }

        return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 16v-4m0-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    root.showConfirmModal = showConfirmModal;
    root.closeModal = function() { closeActive(false); };
    window.EDTECH_UI = root;
})();
