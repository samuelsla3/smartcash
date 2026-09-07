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
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = (string) ($_POST['senha'] ?? '');
    $confirmacao = (string) ($_POST['confirmacao'] ?? '');
    $dataNascimento = trim($_POST['data_nascimento'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');

    if (mb_strlen($nome) < 2) {
        $errors[] = 'Informe seu nome.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Informe um e-mail válido.';
    }

    if (strlen($senha) < 6) {
        $errors[] = 'A senha deve ter pelo menos 6 caracteres.';
    }

    if ($senha !== $confirmacao) {
        $errors[] = 'As senhas não coincidem.';
    }

    if ($dataNascimento === '' || !valid_date($dataNascimento)) {
    $errors[] = 'Informe uma data de nascimento válida.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'SELECT id_usuario FROM USUARIO WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);

        if ($stmt->fetch()) {
            $errors[] = 'Este e-mail já está cadastrado.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO USUARIO
                    (nome, email, senha, data_nascimento, telefone,
                     data_cadastro, receber_email)
                 VALUES
                    (:nome, :email, :senha, :data_nascimento, :telefone,
                     NOW(), :receber_email)'
            );

            $stmt->execute([
                'nome' => $nome,
                'email' => $email,
                'senha' => password_hash($senha, PASSWORD_DEFAULT),
                'data_nascimento' => $dataNascimento,
                'telefone' => $telefone !== '' ? $telefone : null,
                'receber_email' => 1,
            ]);

            flash('success', 'Cadastro realizado. Faça login.');
            redirect('/smartcash/login.php');
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastro - SmartCash</title>
    <link rel="stylesheet" href="/smartcash/assets/css/style.css">
</head>
<body>
<main class="container" style="max-width: 700px;">
    <h1>Criar cadastro</h1>

    <form method="post">
        <?php foreach ($errors as $error): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endforeach; ?>

        <div class="form-grid">
            <div class="field">
                <label for="nome">Nome</label>
                <input id="nome" name="nome" required
                       value="<?= e($_POST['nome'] ?? '') ?>">
            </div>

            <div class="field">
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" required
                       value="<?= e($_POST['email'] ?? '') ?>">
            </div>

            <div class="field">
                <label for="senha">Senha</label>
                <input id="senha" name="senha" type="password"
                       minlength="6" required>
            </div>

            <div class="field">
                <label for="confirmacao">Confirmar senha</label>
                <input id="confirmacao" name="confirmacao" type="password"
                       minlength="6" required>
            </div>

            <div class="field">
                <label for="data_nascimento">Data de nascimento</label>
                <input id="data_nascimento"
                    name="data_nascimento"
                    type="date"
                    required
                    value="<?= e($_POST['data_nascimento'] ?? '') ?>">
            </div>

            <div class="field">
                <label for="telefone">Telefone</label>
                <input id="telefone" name="telefone"
                       value="<?= e($_POST['telefone'] ?? '') ?>">
            </div>
        </div>

        <div class="actions">
            <button type="submit">Cadastrar</button>
            <a class="button secondary" href="/smartcash/login.php">Voltar</a>
        </div>
    </form>
</main>
</body>
</html>
