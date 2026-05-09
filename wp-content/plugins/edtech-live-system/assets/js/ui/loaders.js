(function() {
    'use strict';

    var root = window.EDTECH_UI || {};
    var progressModal = null;

    root.setButtonLoading = function(button, loading, label) {
        if (!button) {
            return;
        }

        if (loading) {
            if (!button.dataset.edtechOriginalHtml) {
                button.dataset.edtechOriginalHtml = button.innerHTML;
            }
            button.disabled = true;
            button.classList.add('edtech-button-loading');
            button.innerHTML = '<span class="edtech-button-spinner" aria-hidden="true"></span>' + (label || 'Working...');
            return;
        }

        button.disabled = false;
        button.classList.remove('edtech-button-loading');
        if (button.dataset.edtechOriginalHtml) {
            button.innerHTML = button.dataset.edtechOriginalHtml;
            delete button.dataset.edtechOriginalHtml;
        }
    };

    root.showProgressModal = function(message) {
        root.hideProgressModal();

        progressModal = document.createElement('div');
        progressModal.className = 'edtech-modal-backdrop is-visible';
        progressModal.innerHTML =
            '<div class="edtech-progress-modal" role="status" aria-live="polite">' +
                '<div class="edtech-progress-spinner" aria-hidden="true"></div>' +
                '<div class="edtech-modal__title">' + root.escapeHtml(message || 'Processing request') + '</div>' +
                '<p class="edtech-modal__message">Please wait a moment.</p>' +
            '</div>';
        document.body.appendChild(progressModal);
        return progressModal;
    };

    root.hideProgressModal = function() {
        if (progressModal) {
            progressModal.remove();
            progressModal = null;
        }
    };

    window.EDTECH_UI = root;
})();
