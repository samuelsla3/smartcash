'use strict';

(() => {
    const formatPhone = (value) => {
        const digits = value.replace(/\D/g, '').slice(0, 11);
        if (!digits) return '';
        if (digits.length <= 2) return `(${digits}`;
        const local = digits.slice(2);
        const split = digits.length > 10 ? 5 : 4;
        return `(${digits.slice(0, 2)}) ${local.slice(0, split)}${local.length > split ? '-' + local.slice(split) : ''}`;
    };
    const formatMoney = (value, final = false, canonical = false, signed = false) => {
        const negative = signed && value.trim().startsWith('-');
        let clean = value.replace(/[^\d.,]/g, '');
        if (!clean.replace(/\D/g, '')) return negative && !final ? '-' : '';
        if (canonical && !clean.includes(',') && /\.\d{1,2}$/.test(clean))
            clean = clean.replace(/\.(\d{1,2})$/, ',$1');
        const parts = clean.split(',');
        let integer = parts[0].replace(/\D/g, '').replace(/^0+(?=\d)/, '') || '0';
        integer = integer.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        const fraction = (parts[1] || '').replace(/\D/g, '').slice(0, 2);
        return (
            (negative ? '-' : '') +
            integer +
            (final ? ',' + fraction.padEnd(2, '0') : parts.length > 1 ? ',' + fraction : '')
        );
    };
    const nextMonth = (value) => {
        const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
        if (!match) return '';
        const year = Number(match[1]);
        const month = Number(match[2]);
        const day = Math.min(Number(match[3]), new Date(year, month + 1, 0).getDate());
        const result = new Date(year, month, day);
        return `${result.getFullYear()}-${String(result.getMonth() + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    };

    function updatePeriod(form) {
        const mode = form.querySelector('[data-period-mode]');
        if (!mode) return;
        form.querySelectorAll('[data-period-group]').forEach((group) => {
            group.hidden = group.dataset.periodGroup !== mode.value;
            group.querySelectorAll('input,select').forEach((input) => {
                input.disabled = group.hidden;
            });
        });
    }

    let controller = null;
    let timer = null;
    let sequence = 0;
    async function liveFilter(form) {
        if (!form.isConnected || !form.checkValidity()) return;
        clearTimeout(timer);
        controller?.abort();
        controller = new AbortController();
        const requestId = ++sequence;
        const url = new URL(form.getAttribute('action') || location.pathname, location.origin);
        url.search = new URLSearchParams(new FormData(form)).toString();
        const main = document.querySelector('#main');
        const focused = document.activeElement;
        const focusName = focused?.name;
        const selection =
            typeof focused?.selectionStart === 'number'
                ? [focused.selectionStart, focused.selectionEnd]
                : null;
        main.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(url, {
                signal: controller.signal,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (response.redirected && new URL(response.url).pathname.endsWith('/login.php')) {
                location.assign(response.url);
                return;
            }
            if (!response.ok) throw Error('Filtro indisponível');
            const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
            const next = doc.querySelector('#main');
            if (!next || requestId !== sequence) return;
            main.innerHTML = next.innerHTML;
            history.replaceState(null, '', url);
            document.dispatchEvent(new Event('smartcash:updated'));
            if (focusName) {
                const input = [...main.querySelectorAll('input,select')].find(
                    (element) => element.name === focusName
                );
                input?.focus({ preventScroll: true });
                if (selection && input?.setSelectionRange) input.setSelectionRange(...selection);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                let status = form.querySelector('[data-filter-error]');
                if (!status) {
                    status = document.createElement('span');
                    status.dataset.filterError = '';
                    status.setAttribute('role', 'status');
                    form.append(status);
                }
                status.textContent = 'Não foi possível atualizar. Tente o botão Filtrar.';
            }
        } finally {
            if (requestId === sequence) main.removeAttribute('aria-busy');
        }
    }

    function init() {
        document.querySelectorAll('input[name="telefone"]').forEach((input) => {
            if (input.dataset.phoneBound) return;
            input.dataset.phoneBound = 'true';
            input.type = 'tel';
            input.inputMode = 'tel';
            input.maxLength = 15;
            input.placeholder = '(77) 99999-9999';
            input.value = formatPhone(input.value);
            input.addEventListener('input', () => {
                input.value = formatPhone(input.value);
            });
        });
        document.querySelectorAll('input[data-money]').forEach((input) => {
            if (input.dataset.moneyBound) return;
            input.dataset.moneyBound = 'true';
            const signed = input.hasAttribute('data-signed');
            input.value = formatMoney(input.value, true, true, signed);
            input.addEventListener('focus', () => {
                if (input.value === '0,00') input.select();
            });
            input.addEventListener('input', () => {
                const offset = input.value.length - input.selectionStart;
                input.value = formatMoney(input.value, false, false, signed);
                const position = Math.max(0, input.value.length - offset);
                input.setSelectionRange(position, position);
            });
            input.addEventListener('blur', () => {
                input.value = formatMoney(input.value, true, false, signed);
            });
            input.addEventListener('paste', (event) => {
                const pasted = event.clipboardData?.getData('text');
                if (pasted) {
                    event.preventDefault();
                    input.value = formatMoney(pasted, true, true, signed);
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
            input.form?.addEventListener(
                'submit',
                () => {
                    input.value = formatMoney(input.value, true, false, signed);
                },
                { capture: true }
            );
        });
        document.querySelectorAll('[data-period-form]').forEach((form) => {
            if (form.dataset.periodBound) return;
            form.dataset.periodBound = 'true';
            updatePeriod(form);
            form.querySelector('[data-period-mode]')?.addEventListener('change', () =>
                updatePeriod(form)
            );
        });
        document.querySelectorAll('[data-live-filter]').forEach((form) => {
            if (form.dataset.liveBound) return;
            form.dataset.liveBound = 'true';
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                liveFilter(form);
            });
            form.addEventListener('input', (event) => {
                if (event.target.name !== 'q') return;
                controller?.abort();
                sequence++;
                clearTimeout(timer);
                timer = setTimeout(() => liveFilter(form), 350);
            });
            form.addEventListener('change', () => {
                updatePeriod(form);
                liveFilter(form);
            });
        });
        const expenseType = document.querySelector('[name="id_tipo_despesa"]');
        if (expenseType && !expenseType.dataset.autoRecurringBound) {
            expenseType.dataset.autoRecurringBound = 'true';
            expenseType.addEventListener('change', () => {
                if (
                    expenseType.selectedOptions[0]?.textContent
                        .trim()
                        .toLocaleLowerCase('pt-BR') !== 'fixa'
                )
                    return;
                const recurring = document.querySelector('[name="recorrente"]');
                if (!recurring) return;
                recurring.checked = true;
                recurring.dispatchEvent(new Event('change'));
                document.querySelector('[name="frequencia"]').value = 'mensal';
                const source = document.querySelector('[name="data"]');
                const next = document.querySelector('[name="proxima_cobranca"]');
                if (source && next) next.value = nextMonth(source.value);
            });
        }
        const account = document.querySelector('form[method="post"] select[name="id_conta"]');
        const bank = document.querySelector('[data-bank-preview]');
        if (account && bank && !account.dataset.bankBound) {
            account.dataset.bankBound = 'true';
            const update = () => {
                bank.textContent = account.selectedOptions[0]?.dataset.bank
                    ? `Instituição: ${account.selectedOptions[0].dataset.bank}`
                    : 'A instituição será a mesma da conta associada.';
            };
            account.addEventListener('change', update);
            update();
        }
    }
    init();
    document.addEventListener('smartcash:updated', init);
})();
