<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

$basePath = $basePath ?? '../';
$pageTitle = $pageTitle ?? ($page_title ?? 'SmartCash');

$userName = $_SESSION['nome_usuario'] ?? 'Usuário';

$flash = function_exists('getFlash')
    ? getFlash()
    : (function_exists('get_flash') ? get_flash() : null);

$dashboardPage = $dashboardPage ?? false;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($pageTitle) ?> | SmartCash</title>

    <link rel="stylesheet" href="<?= e($basePath) ?>assets/css/style.css">

    <?php if ($dashboardPage): ?>
        <link rel="stylesheet" href="<?= e($basePath) ?>assets/css/dashboard.css">
    <?php endif; ?>
</head>

<body>

<header class="topbar">

    <a class="brand" href="<?= e($basePath) ?>dashboard/index.php">
        SmartCash
    </a>

    <nav class="nav">

        <!-- Principal -->
        <a href="<?= e($basePath) ?>dashboard/index.php">
            Dashboard
        </a>

        <!-- Núcleo financeiro -->
        <a href="<?= e($basePath) ?>contas/index.php">
            Contas
        </a>

        <a href="<?= e($basePath) ?>recebimentos/index.php">
            Recebimentos
        </a>

        <a href="<?= e($basePath) ?>despesas/index.php">
            Despesas
        </a>

        <a href="<?= e($basePath) ?>transferencias/index.php">
            Transferências
        </a>

        <!-- Módulos complementares -->
        <a href="<?= e($basePath) ?>categorias/index.php">
            Categorias
        </a>

        <a href="<?= e($basePath) ?>cartoes/index.php">
            Cartões
        </a>

        <a href="<?= e($basePath) ?>investimentos/index.php">
            Investimentos
        </a>

        <a href="<?= e($basePath) ?>objetivos/index.php">
            Objetivos
        </a>

        <a href="<?= e($basePath) ?>notificacoes/index.php">
            Notificações
        </a>

        <a href="<?= e($basePath) ?>relatorios/despesas.php">
            Relatórios
        </a>

        <a href="<?= e($basePath) ?>usuario/perfil.php">
            Perfil
        </a>

    </nav>

    <div class="user-menu">

        <span>
            <?= e($userName) ?>
        </span>

        <a href="<?= e($basePath) ?>logout.php">
            Sair
        </a>

    </div>

</header>

<main class="container">

<?php if ($flash): ?>

    <div class="alert <?= e($flash['type']) ?>">
        <?= e($flash['message']) ?>
    </div>

<?php endif; ?>