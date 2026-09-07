<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$uid = usuarioLogado();
$errors = [];

$stmt = db()->prepare(
    'SELECT
        nome,
        email,
        data_nascimento,
        telefone,
        receber_email
     FROM USUARIO
     WHERE id_usuario = :id'
);

$stmt->execute([
    'id' => $uid
]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    flash('error', 'Usuário não encontrado.');
    redirect('perfil.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $nome = trim(
        (string) ($_POST['nome'] ?? '')
    );

    $email = trim(
        (string) ($_POST['email'] ?? '')
    );

    $dataNascimento = trim(
        (string) ($_POST['data_nascimento'] ?? '')
    );

    $telefone = trim(
        (string) ($_POST['telefone'] ?? '')
    );

    $receberEmail = isset($_POST['receber_email'])
        ? 1
        : 0;

    /*
     * Validação
     */
    if (mb_strlen($nome) < 2) {
        $errors[] = 'Informe um nome válido.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Informe um e-mail válido.';
    }

    if (
        $dataNascimento === ''
        || !valid_date($dataNascimento)
    ) {
        $errors[] = 'Informe uma data de nascimento válida.';
    }

    /*
     * Confere se outro usuário já utiliza o e-mail.
     */
    if (!$errors) {

        $stmt = db()->prepare(
            'SELECT id_usuario
             FROM USUARIO
             WHERE email = :email
               AND id_usuario <> :id
             LIMIT 1'
        );

        $stmt->execute([
            'email' => $email,
            'id' => $uid
        ]);

        if ($stmt->fetch()) {
            $errors[] = 'Este e-mail já está sendo utilizado.';
        }
    }

    if (!$errors) {

        $stmt = db()->prepare(
            'UPDATE USUARIO
             SET
                nome = :nome,
                email = :email,
                data_nascimento = :data,
                telefone = :telefone,
                receber_email = :receber_email
             WHERE id_usuario = :id'
        );

        $stmt->execute([
            'nome' => $nome,
            'email' => $email,
            'data' => $dataNascimento,
            'telefone' => $telefone !== ''
                ? $telefone
                : null,
            'receber_email' => $receberEmail,
            'id' => $uid
        ]);

        /*
         * Atualiza nome utilizado pelo header.
         */
        $_SESSION['nome_usuario'] = $nome;

        flash(
            'success',
            'Perfil atualizado com sucesso.'
        );

        redirect('perfil.php');
    }
}

$pageTitle = 'Editar perfil';
$basePath = '../';

require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">

    <h1>Editar perfil</h1>

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

        <div class="form-row">

            <div class="form-group">

                <label for="nome">
                    Nome
                </label>

                <input
                    id="nome"
                    name="nome"
                    maxlength="150"
                    required
                    value="<?= e(
                        $_POST['nome']
                        ?? $usuario['nome']
                    ) ?>"
                >

            </div>

            <div class="form-group">

                <label for="email">
                    E-mail
                </label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    required
                    value="<?= e(
                        $_POST['email']
                        ?? $usuario['email']
                    ) ?>"
                >

            </div>

        </div>

        <div class="form-row">

            <div class="form-group">

                <label for="data_nascimento">
                    Data de nascimento
                </label>

                <input
                    id="data_nascimento"
                    name="data_nascimento"
                    type="date"
                    required
                    value="<?= e(
                        $_POST['data_nascimento']
                        ?? $usuario['data_nascimento']
                    ) ?>"
                >

            </div>

            <div class="form-group">

                <label for="telefone">
                    Telefone
                </label>

                <input
                    id="telefone"
                    name="telefone"
                    maxlength="30"
                    value="<?= e(
                        $_POST['telefone']
                        ?? ($usuario['telefone'] ?? '')
                    ) ?>"
                >

            </div>

        </div>

        <div class="form-group">

            <label class="checkbox">

                <input
                    type="checkbox"
                    name="receber_email"
                    value="1"
                    <?= (
                        isset($_POST['receber_email'])
                        || (
                            $_SERVER['REQUEST_METHOD'] !== 'POST'
                            && (int) $usuario['receber_email'] === 1
                        )
                    ) ? 'checked' : '' ?>
                >

                Quero receber comunicações por e-mail

            </label>

        </div>

        <button class="btn">
            Salvar alterações
        </button>

    </form>

</div>

<?php
require __DIR__ . '/../includes/footer.php';
?>