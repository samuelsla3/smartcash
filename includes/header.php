<?php

require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? 'Visão geral';
$current = $current ?? 'dashboard';
$name = $_SESSION['nome_usuario'] ?? 'Você';
$count = usuarioLogado()
    ? (int) scalar('SELECT COUNT(*) FROM notificacao WHERE id_usuario=? AND data_leitura IS NULL', [usuarioLogado()])
    : 0;
$nav = [
    'dashboard' => 'Visão geral',
    'contas' => 'Contas',
    'recebimentos' => 'Recebimentos',
    'despesas' => 'Despesas',
    'transferencias' => 'Transferências',
    'cartoes' => 'Cartões',
    'investimentos' => 'Investimentos',
    'objetivos' => 'Objetivos',
    'categorias' => 'Categorias',
    'relatorios' => 'Relatórios',
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#0b1016">
    <title><?= e($pageTitle) ?> · SmartCash</title>
    <link rel="icon" href="<?= e(assetUrl('assets/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(assetUrl('assets/css/app-dark.css')) ?>">
</head>
<body>
    <a class="skip-link" href="#main">Pular para o conteúdo</a>

    <aside class="sidebar" id="sidebar" aria-label="Menu principal">
        <a class="brand" href="<?= e(url('dashboard/index.php')) ?>"><span class="brand-symbol">$<span></span></span>smartcash<span class="brand-dot"></span></a>
        <div class="workspace-tag"><span class="status-dot"></span> MINHAS FINANÇAS</div>

        <nav>
            <?php foreach ($nav as $key => $label): ?>
                <?php if ($key === 'investimentos'): ?>
                    <div class="nav-label">PLANEJAMENTO</div>
                <?php endif; ?>
                <a href="<?= e(url($key . '/index.php')) ?>" class="nav-item <?= $current === $key ? 'active' : '' ?>" <?= $current === $key ? 'aria-current="page"' : '' ?>><?= icon($key) ?><span><?= e($label) ?></span></a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-note">
                <span class="small">UM PASSO DE CADA VEZ</span>
                <strong>Mais clareza.<br>Novos caminhos.</strong>
                <span>Seu futuro começa nas escolhas de hoje.</span>
            </div>
            <a class="nav-item <?= $current === 'usuario' ? 'active' : '' ?>" href="<?= e(url('usuario/perfil.php')) ?>"><?= icon('usuario') ?>Meu perfil</a>
            <form action="<?= e(url('logout.php')) ?>" method="post">
                <?php csrfField(); ?>
                <button class="nav-item logout" type="submit"><?= icon('logout') ?>Sair da conta</button>
            </form>
        </div>
    </aside>

    <button class="sidebar-overlay" type="button" aria-label="Fechar menu" tabindex="-1"></button>

    <div class="app-shell">
        <header class="topbar">
            <div class="topbar-left">
                <button class="icon-button mobile-toggle" type="button" aria-label="Abrir menu" aria-controls="sidebar" aria-expanded="false"><?= icon('menu') ?></button>
                <span class="breadcrumb"><span class="breadcrumb-root">Pessoal</span><span aria-hidden="true">/</span> <strong><?= e($nav[$current] ?? $pageTitle) ?></strong></span>
            </div>
            <div class="topbar-right">
                <span class="today"><?= date('d/m/Y') ?></span>
                <button class="icon-button" id="privacy-toggle" type="button" aria-label="Ocultar valores" aria-pressed="false" title="Ocultar ou mostrar valores"><?= icon('eye') ?></button>
                <a class="icon-button notification-link" href="<?= e(url('notificacoes/index.php')) ?>" aria-label="Notificações: <?= $count ?> não lidas"><?= icon('notificacoes') ?><?php if ($count): ?><span class="notification-dot"></span><?php endif; ?></a>
                <a class="avatar" href="<?= e(url('usuario/perfil.php')) ?>" aria-label="Meu perfil"><?= e(mb_strtoupper(mb_substr($name, 0, 1))) ?></a>
            </div>
        </header>

        <main id="main" class="container" tabindex="-1">
            <?php if ($f = getFlash()): ?>
                <div class="alert <?= e($f['type']) ?>" role="status"><?= icon('check') ?><span><?= e($f['message']) ?></span></div>
            <?php endif; ?>
