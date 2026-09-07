(() => {
    let categoryChart = null;

    const money = (value) => Number(value || 0).toLocaleString('pt-BR', {
        style: 'currency', currency: 'BRL'
    });

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[char]));

    const formatDate = (value) => {
        if (!value) return '-';
        const [year, month, day] = String(value).slice(0, 10).split('-');
        return `${day}/${month}/${year}`;
    };

    async function loadDashboard() {
        const params = new URLSearchParams();
        const inicio = document.querySelector('#inicio').value;
        const fim = document.querySelector('#fim').value;
        if (inicio) params.set('inicio', inicio);
        if (fim) params.set('fim', fim);

        const response = await fetch(`../api/dashboard.php?${params.toString()}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.erro || 'Erro ao carregar dashboard.');

        document.querySelector('#saldoTotal').textContent = money(data.saldo_total);
        document.querySelector('#receitas').textContent = money(data.receitas);
        document.querySelector('#despesas').textContent = money(data.despesas);
        const result = document.querySelector('#resultado');
        result.textContent = money(data.resultado);
        result.classList.toggle('positive', Number(data.resultado) >= 0);
        result.classList.toggle('negative', Number(data.resultado) < 0);

        renderCategories(data.categorias);
        renderCards(data.cartoes);
        renderMovements(data.movimentacoes);
        renderInvestments(data.investimentos);
        renderObjectives(data.objetivos);
    }

    function renderCategories(items) {
        const labels = items.map(item => item.categoria);
        const values = items.map(item => Number(item.total));
        const canvas = document.querySelector('#categoryChart');

        if (categoryChart) categoryChart.destroy();
        categoryChart = new Chart(canvas, {
            type: 'doughnut',
            data: { labels, datasets: [{ data: values }] },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    function renderCards(items) {
        const list = document.querySelector('#cardsList');
        if (!items.length) {
            list.innerHTML = '<li class="empty">Nenhum cartão cadastrado.</li>';
            return;
        }
        list.innerHTML = items.map(card => `
            <li>
                <strong>${escapeHtml(card.nome_cartao)}</strong>
                <div class="muted">${escapeHtml(card.banco || 'Banco não informado')}</div>
                <div>Disponível: <strong>${money(card.limite_disponivel)}</strong> / ${money(card.limite_total)}</div>
            </li>
        `).join('');
    }

    function renderMovements(items) {
        const container = document.querySelector('#movementsList');
        if (!items.length) {
            container.innerHTML = '<div class="empty">Nenhuma movimentação encontrada.</div>';
            return;
        }
        container.innerHTML = items.map(item => `
            <div class="movement">
                <div><strong>${escapeHtml(item.descricao || 'Sem descrição')}</strong><small>${formatDate(item.data_movimentacao)}</small></div>
                <strong class="${item.tipo === 'despesa' ? 'negative' : 'positive'}">
                    ${item.tipo === 'despesa' ? '-' : '+'} ${money(item.valor)}
                </strong>
            </div>
        `).join('');
    }

    function renderInvestments(items) {
        const list = document.querySelector('#investmentsList');
        if (!items.length) {
            list.innerHTML = '<li class="empty">Nenhum investimento cadastrado.</li>';
            return;
        }
        list.innerHTML = items.map(item => `
            <li>
                <strong>${escapeHtml(item.nome)}</strong>
                <div>Aplicado: ${money(item.valor_aplicado)}</div>
                <div>Atualizado: <strong>${money(item.valor_atualizado)}</strong></div>
            </li>
        `).join('');
    }

    function renderObjectives(items) {
        const container = document.querySelector('#objectivesList');
        if (!items.length) {
            container.innerHTML = '<div class="empty">Nenhum objetivo cadastrado.</div>';
            return;
        }
        container.innerHTML = items.map(item => {
            const progress = Math.min(100, Math.max(0, Number(item.progresso || 0)));
            return `
                <div class="objective-item">
                    <div class="objective-head">
                        <strong>${escapeHtml(item.descricao)}</strong>
                        <span>${progress.toFixed(1)}%</span>
                    </div>
                    <div class="progress"><div class="progress-bar" style="width:${progress}%"></div></div>
                    <small class="muted">${money(item.valor_atual)} / ${money(item.valor_objetivo)} · ${escapeHtml(item.status || '')}</small>
                </div>
            `;
        }).join('');
    }

    document.addEventListener('DOMContentLoaded', () => {
        const now = new Date();
        const first = new Date(now.getFullYear(), now.getMonth(), 1);
        const last = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        const iso = date => date.toISOString().slice(0, 10);
        document.querySelector('#inicio').value = iso(first);
        document.querySelector('#fim').value = iso(last);

        document.querySelector('#periodForm').addEventListener('submit', (event) => {
            event.preventDefault();
            loadDashboard().catch(error => alert(error.message));
        });
        loadDashboard().catch(error => alert(error.message));
    });
})();
