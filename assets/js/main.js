'use strict';

const qs = (selector, root = document) => root.querySelector(selector);
const menu = qs('.mobile-toggle');
function closeMenu() {
    document.body.classList.remove('menu-open');
    menu?.setAttribute('aria-expanded', 'false');
}
menu?.addEventListener('click', () => {
    const open = document.body.classList.toggle('menu-open');
    menu.setAttribute('aria-expanded', String(open));
    if (open) qs('.sidebar a')?.focus();
});
qs('.sidebar-overlay')?.addEventListener('click', closeMenu);
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeMenu();
});
const privacy = qs('#privacy-toggle');
let privateMode = false;
try {
    privateMode = localStorage.getItem('smartcash-private') === 'true';
} catch {}
function setPrivacy(value) {
    document.body.classList.toggle('privacy', value);
    privacy?.setAttribute('aria-pressed', String(value));
    privacy?.setAttribute('aria-label', value ? 'Mostrar valores' : 'Ocultar valores');
}
setPrivacy(privateMode);
privacy?.addEventListener('click', () => {
    privateMode = !privateMode;
    setPrivacy(privateMode);
    try {
        localStorage.setItem('smartcash-private', String(privateMode));
    } catch {}
});

const dialog = qs('#confirm-dialog');
let pendingForm = null;
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.dataset.confirm && form.dataset.confirmed !== 'true') {
        event.preventDefault();
        pendingForm = form;
        qs('#confirm-message').textContent = form.dataset.confirm;
        dialog.showModal();
        return;
    }
    if (form.method === 'post' && !event.defaultPrevented) {
        requestAnimationFrame(() => {
            form.querySelectorAll('button[type="submit"],button:not([type])').forEach((button) => {
                button.disabled = true;
            });
        });
    }
});
dialog?.addEventListener('close', () => {
    if (dialog.returnValue === 'confirm' && pendingForm) {
        pendingForm.dataset.confirmed = 'true';
        pendingForm.requestSubmit();
    }
    pendingForm = null;
});
document.addEventListener('click', (event) => {
    if (event.target.closest('[data-print]')) window.print();
});

function initFinancialForms() {
    const payment = qs('[name="forma_pagamento"]');
    if (payment && !payment.dataset.mainBound) {
        payment.dataset.mainBound = 'true';
        const update = () =>
            ['conta', 'cartao'].forEach((type) => {
                const field = qs(`[data-field="id_${type}"]`);
                if (!field) return;
                field.hidden = payment.value !== type;
                qs('select', field).disabled = field.hidden;
                qs('select', field).required = !field.hidden;
            });
        payment.addEventListener('change', update);
        update();
    }
    const recurring = qs('[name="recorrente"]');
    const fields = qs('#recurring-fields');
    if (recurring && fields && !recurring.dataset.mainBound) {
        recurring.dataset.mainBound = 'true';
        const update = () => {
            fields.hidden = !recurring.checked;
            fields.querySelectorAll('input,select').forEach((input) => {
                input.disabled = fields.hidden;
                input.required = !fields.hidden;
            });
        };
        recurring.addEventListener('change', update);
        update();
    }
}
initFinancialForms();
document.addEventListener('smartcash:updated', initFinancialForms);
