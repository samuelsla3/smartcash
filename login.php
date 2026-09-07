<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['id_usuario'])) {
    redirect('/smartcash/dashboard/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = (string) ($_POST['senha'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Informe um e-mail válido.';
    }

    if ($senha === '') {
        $errors[] = 'Informe a senha.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'SELECT id_usuario, nome, senha
             FROM USUARIO
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            session_regenerate_id(true);

            $_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
            $_SESSION['nome_usuario'] = $usuario['nome'];

            redirect('/smartcash/dashboard/index.php');
        }

        $errors[] = 'E-mail ou senha inválidos.';
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - SmartCash</title>
    <link rel="stylesheet" href="/smartcash/assets/css/style.css">
</head>
<body>
<main class="container" style="max-width: 520px;">
    <h1>SmartCash</h1>

    <form method="post" novalidate>
        <?php foreach ($errors as $error): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endforeach; ?>

        <div class="field">
            <label for="email">E-mail</label>
            <input id="email" name="email" type="email"
                   required autocomplete="email"
                   value="<?= e($_POST['email'] ?? '') ?>">
        </div>

        <br>

        <div class="field">
            <label for="senha">Senha</label>
            <input id="senha" name="senha" type="password"
                   required autocomplete="current-password">
        </div>

        <div class="actions">
            <button type="submit">Entrar</button>
            <a class="button secondary" href="/smartcash/cadastro.php">
                Criar cadastro
            </a>
        </div>
    </form>
</main>
</body>
</html>
