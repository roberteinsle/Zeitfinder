// Vanilla JS only: no inline scripts or eval, so the strict CSP stays intact.
import { initOptionRows } from './options';
import { initClipboard } from './clipboard';
import { initPasskeys } from './passkeys';

function initConfirmForms() {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
}

function initSubmitOnce() {
    document.querySelectorAll('form:not([data-allow-resubmit])').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (event.defaultPrevented) {
                return;
            }
            form.querySelectorAll('button[type="submit"]').forEach((button) => {
                window.setTimeout(() => (button.disabled = true), 0);
            });
        });
    });
}

function initQuickAnswers() {
    document.querySelectorAll('[data-set-all]').forEach((button) => {
        button.addEventListener('click', () => {
            const value = button.dataset.setAll;
            const form = button.closest('form');
            form?.querySelectorAll(`input[type="radio"][value="${value}"]`).forEach((input) => {
                input.checked = true;
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initOptionRows();
    initClipboard();
    initPasskeys();
    initConfirmForms();
    initQuickAnswers();
    initSubmitOnce();
});
