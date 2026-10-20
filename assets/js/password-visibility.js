'use strict';

(() => {
    const eye =
        '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>';
    const eyeOff =
        '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 3 18 18M10.6 5.1A11 11 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4M6.2 6.2A18 18 0 0 0 2 12s3.6 7 10 7a11 11 0 0 0 5.8-1.8M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>';

    document.querySelectorAll('input[type="password"]').forEach((input, index) => {
        if (input.closest('.password-control')) {
            return;
        }

        if (!input.id) {
            input.id = `password-field-${index}`;
        }

        const label = input.labels?.[0]?.textContent.trim() || 'Senha';
        const wrapper = document.createElement('div');
        const toggle = document.createElement('button');

        wrapper.className = 'password-control';
        toggle.className = 'password-visibility-toggle';
        toggle.type = 'button';
        toggle.setAttribute('aria-controls', input.id);
        toggle.dataset.passwordVisibility = input.id;

        const updateButton = () => {
            const visible = input.type === 'text';
            const action = visible ? 'Ocultar senha' : 'Mostrar senha';

            toggle.innerHTML = visible ? eyeOff : eye;
            toggle.setAttribute('aria-label', `${action}: ${label}`);
            toggle.setAttribute('aria-pressed', String(visible));
            toggle.title = action;
        };

        toggle.addEventListener('click', () => {
            input.type = input.type === 'password' ? 'text' : 'password';
            updateButton();
        });

        input.before(wrapper);
        wrapper.append(input, toggle);
        updateButton();

        // Uma página restaurada do cache deve voltar a ocultar as senhas.
        window.addEventListener('pageshow', () => {
            input.type = 'password';
            updateButton();
        });
    });
})();
