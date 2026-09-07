<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$uid = usuarioLogado();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $senhaAtual = (string) (
        $_POST['senha_atual'] ?? ''
    );

    $novaSenha = (string) (
        $_POST['nova_senha'] ?? ''
    );

    $confirmacao = (string) (
        $_POST['confirmacao'] ?? ''
    );

    if ($senhaAtual === '') {
        $errors[] = 'Informe sua senha atual.';
    }

    if (strlen($novaSenha) < 6) {
        $errors[] =
            'A nova senha deve ter pelo menos 6 caracteres.';
    }

    if ($novaSenha !== $confirmacao) {
        $errors[] = 'As novas senhas não coincidem.';
    }

    if (!$errors) {

        $stmt = db()->prepare(
            'SELECT senha
             FROM USUARIO
             WHERE id_usuario = :id'
        );

        $stmt->execute([
            'id' => $uid
        ]);

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            !$usuario
            || !password_verify(
                $senhaAtual,
                $usuario['senha']
            )
        ) {
            $errors[] = 'Senha atual incorreta.';
        }
    }

    if (!$errors) {

        /*
         * Opcionalmente impede reutilizar a mesma senha.
         */
        if (
            password_verify(
                $novaSenha,
                $usuario['senha']
            )
        ) {
            $errors[] =
                'A nova senha deve ser diferente da senha atual.';
        }
    }

    if (!$errors) {

        $hash = password_hash(
            $novaSenha,
            PASSWORD_DEFAULT
        );

        $stmt = db()->prepare(
            'UPDATE USUARIO
             SET senha = :senha
             WHERE id_usuario = :id'
        );

        $stmt->execute([
            'senha' => $hash,
            'id' => $uid
        ]);

        /*
         * Renova o identificador da sessão
         * depois de uma mudança sensível.
         */
        session_regenerate_id(true);

        flash(
            'success',
            'Senha alterada com sucesso.'
        );

        redirect('perfil.php');
    }
}

$pageTitle = 'Alterar senha';
$basePath = '../';

require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">

    <h1>Alterar senha</h1>

    <a class="btn btn-secondary" href="perfil.php">
        Voltar
    </a>

</div>

<div class="form-card">

    <?php foreach ($errors as $error): ?>

        <div class="alert error">
            <?= e($error) ?>
        </div>

    <?php endforeach; ?>

    <form method="post">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrfToken()) ?>"
        >

        <div class="form-group">

            <label for="senha_atual">
                Senha atual
            </label>

            <input
                id="senha_atual"
                name="senha_atual"
                type="password"
                autocomplete="current-password"
                required
            >

        </div>

        <div class="form-row">

            <div class="form-group">

                <label for="nova_senha">
                    Nova senha
                </label>

                <input
                    id="nova_senha"
                    name="nova_senha"
                    type="password"
                    minlength="6"
                    autocomplete="new-password"
                    required
                >

            </div>

            <div class="form-group">

                <label for="confirmacao">
                    Confirmar nova senha
                </label>

                <input
                    id="confirmacao"
                    name="confirmacao"
                    type="password"
                    minlength="6"
                    autocomplete="new-password"
                    required
                >

            </div>

        </div>

        <button class="btn">
            Alterar senha
        </button>

    </form>

</div>

<?php
require __DIR__ . '/../includes/footer.php';
?>