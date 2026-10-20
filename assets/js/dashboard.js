'use strict';

(() => {
    const source = document.getElementById('chart-data');

    if (!source || typeof Chart === 'undefined') {
        return;
    }

    const data = JSON.parse(source.textContent);
    const colors = ['#c0ed8c', '#b2a5ee', '#79c6df', '#e8bc83', '#8fd5b5', '#e3a0bd'];
    const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const money = (value) =>
        new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);

    Chart.defaults.font.family = 'Inter, Segoe UI, system-ui, sans-serif';
    Chart.defaults.font.size = 11;
    Chart.defaults.color = '#8fa5b7';
    Chart.defaults.borderColor = '#26333f';

    const tooltip = {
        backgroundColor: '#202e3b',
        titleColor: '#f0f5f8',
        bodyColor: '#c9d9e5',
        borderColor: '#405361',
        borderWidth: 1,
        padding: 13,
        cornerRadius: 10,
        displayColors: true,
        callbacks: {
            label: (context) => `${context.dataset.label || context.label}: ${money(context.raw)}`
        }
    };

    new Chart(document.getElementById('flowChart'), {
        type: 'bar',
        data: {
            labels: data.flow.labels,
            datasets: [
                {
                    label: 'Receitas',
                    data: data.flow.receitas,
                    backgroundColor: '#c0ed8c',
                    hoverBackgroundColor: '#d1f5ab',
                    borderRadius: 6,
                    maxBarThickness: 32
                },
                {
                    label: 'Despesas',
                    data: data.flow.despesas,
                    backgroundColor: '#a89be0',
                    hoverBackgroundColor: '#c0b4f0',
                    borderRadius: 6,
                    maxBarThickness: 32
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: reducedMotion ? false : { duration: 600 },
            layout: { padding: { top: 8, right: 8 } },
            plugins: {
                legend: { display: false },
                tooltip
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { padding: 12, maxTicksLimit: 12 }
                },
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: '#24313c', drawTicks: false },
                    ticks: {
                        padding: 12,
                        maxTicksLimit: 5,
                        callback: (value) => money(value)
                    }
                }
            }
        }
    });

    if (!data.categories.length) {
        return;
    }

    const total = data.categories.reduce((sum, item) => sum + Number(item.total), 0);
    const centerLabel = {
        id: 'centerLabel',
        afterDraw(chart) {
            const { ctx, chartArea } = chart;

            if (!chartArea) {
                return;
            }

            const x = (chartArea.left + chartArea.right) / 2;
            const y = (chartArea.top + chartArea.bottom) / 2;
            const width = Math.min(chartArea.width, chartArea.height) * 0.57;
            const label = money(total);

            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = '#95aabc';
            ctx.font = '10px Segoe UI, sans-serif';
            ctx.fillText('DESPESAS', x, y - 15);

            let size = 19;
            ctx.font = `600 ${size}px Segoe UI, sans-serif`;

            while (ctx.measureText(label).width > width && size > 10) {
                size--;
                ctx.font = `600 ${size}px Segoe UI, sans-serif`;
            }

            ctx.fillStyle = '#eef4f7';
            ctx.fillText(label, x, y + 12);
            ctx.restore();
        }
    };

    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: data.categories.map((item) => item.categoria),
            datasets: [
                {
                    data: data.categories.map((item) => Number(item.total)),
                    backgroundColor: data.categories.map(
                        (_, index) => colors[index % colors.length]
                    ),
                    borderWidth: 5,
                    borderColor: '#131b24',
                    borderRadius: 4,
                    hoverOffset: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: reducedMotion ? false : { duration: 600 },
            cutout: '80%',
            layout: { padding: 7 },
            plugins: {
                legend: { display: false },
                tooltip
            }
        },
        plugins: [centerLabel]
    });
})();
