<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

require_once __DIR__ . '/functions.php';

/**
 * Exige que exista um usuário autenticado.
 */
function exigirLogin(): void
{
    if (usuarioLogado() <= 0) {
        header('Location: /smartcash/login.php');
        exit;
    }
}

exigirLogin();

$id_usuario = usuarioLogado();