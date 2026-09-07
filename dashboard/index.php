<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
usuarioLogado();
$pageTitle = 'Dashboard';
$dashboardPage = true;
$basePath = '../';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-header">
    <div>
        <h1>Dashboard</h1>
        <p class="muted">Visão geral das suas finanças.</p>
    </div>
    <form class="period-filter" id="periodForm">
        <div class="form-group" style="margin:0">
            <label for="inicio">De</label>
            <input type="date" id="inicio" name="inicio">
        </div>
        <div class="form-group" style="margin:0">
            <label for="fim">Até</label>
            <input type="date" id="fim" name="fim">
        </div>
        <button class="btn" type="submit">Atualizar</button>
    </form>
</div>

<section class="metric-grid">
    <div class="metric-card"><div class="label">Saldo total</div><div class="value" id="saldoTotal">R$ 0,00</div></div>
    <div class="metric-card"><div class="label">Receitas do período</div><div class="value positive" id="receitas">R$ 0,00</div></div>
    <div class="metric-card"><div class="label">Despesas do período</div><div class="value negative" id="despesas">R$ 0,00</div></div>
    <div class="metric-card"><div class="label">Resultado financeiro</div><div class="value" id="resultado">R$ 0,00</div></div>
</section>

<section class="dashboard-grid">
    <div class="dashboard-card">
        <h2>Gastos por categoria</h2>
        <div class="chart-wrap"><canvas id="categoryChart"></canvas></div>
    </div>
    <div class="dashboard-card">
        <h2>Cartões</h2>
        <ul class="mini-list" id="cardsList"><li class="loading">Carregando...</li></ul>
    </div>
    <div class="dashboard-card">
        <h2>Últimas movimentações</h2>
        <div id="movementsList"><div class="loading">Carregando...</div></div>
    </div>
    <div class="dashboard-card">
        <h2>Investimentos</h2>
        <ul class="mini-list" id="investmentsList"><li class="loading">Carregando...</li></ul>
    </div>
    <div class="dashboard-card full">
        <h2>Objetivos financeiros</h2>
        <div id="objectivesList"><div class="loading">Carregando...</div></div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../assets/js/dashboard.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
