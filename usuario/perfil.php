<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$uid = usuarioLogado();

$stmt = db()->prepare(
    'SELECT
        id_usuario,
        nome,
        email,
        data_nascimento,
        telefone,
        data_cadastro,
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
    redirect('/smartcash/logout.php');
}

/*
 * Resumo da conta
 */
$stmt = db()->prepare(
    'SELECT COUNT(*)
     FROM CONTA
     WHERE id_usuario = :u'
);

$stmt->execute(['u' => $uid]);
$totalContas = (int) $stmt->fetchColumn();

$stmt = db()->prepare(
    'SELECT COUNT(*)
     FROM CARTAO
     WHERE id_usuario = :u'
);

$stmt->execute(['u' => $uid]);
$totalCartoes = (int) $stmt->fetchColumn();

$stmt = db()->prepare(
    'SELECT COUNT(*)
     FROM INVESTIMENTO
     WHERE id_usuario = :u'
);

$stmt->execute(['u' => $uid]);
$totalInvestimentos = (int) $stmt->fetchColumn();

$stmt = db()->prepare(
    "SELECT COUNT(*)
     FROM OBJETIVO_FINANCEIRO
     WHERE id_usuario = :u
       AND status = 'Em andamento'"
);

$stmt->execute(['u' => $uid]);
$totalObjetivosAtivos = (int) $stmt->fetchColumn();

$pageTitle = 'Perfil';
$basePath = '../';

require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Meu perfil</h1>
        <p class="muted">
            Consulte seus dados pessoais e configurações da conta.
        </p>
    </div>

    <div class="actions">
        <a class="btn" href="editar.php">
            Editar perfil
        </a>

        <a class="btn btn-secondary" href="alterar_senha.php">
            Alterar senha
        </a>
    </div>
</div>

<div class="grid grid-2">

    <div class="card">

        <h2>Dados pessoais</h2>

        <p>
            <strong>Nome:</strong><br>
            <?= e($usuario['nome']) ?>
        </p>

        <p>
            <strong>E-mail:</strong><br>
            <?= e($usuario['email']) ?>
        </p>

        <p>
            <strong>Data de nascimento:</strong><br>

            <?php if (!empty($usuario['data_nascimento'])): ?>
                <?= e(
                    date(
                        'd/m/Y',
                        strtotime($usuario['data_nascimento'])
                    )
                ) ?>
            <?php else: ?>
                <span class="muted">Não informada</span>
            <?php endif; ?>
        </p>

        <p>
            <strong>Telefone:</strong><br>

            <?php if (!empty($usuario['telefone'])): ?>
                <?= e($usuario['telefone']) ?>
            <?php else: ?>
                <span class="muted">Não informado</span>
            <?php endif; ?>
        </p>

        <p>
            <strong>Membro desde:</strong><br>

            <?= e(
                date(
                    'd/m/Y',
                    strtotime($usuario['data_cadastro'])
                )
            ) ?>
        </p>

        <p>
            <strong>Receber e-mails:</strong><br>

            <?= (int) $usuario['receber_email'] === 1
                ? 'Sim'
                : 'Não' ?>
        </p>

    </div>

    <div class="card">

        <h2>Resumo da conta</h2>

        <div class="grid grid-2">

            <div>
                <span class="muted">
                    Contas cadastradas
                </span>

                <h2>
                    <?= $totalContas ?>
                </h2>
            </div>

            <div>
                <span class="muted">
                    Cartões cadastrados
                </span>

                <h2>
                    <?= $totalCartoes ?>
                </h2>
            </div>

            <div>
                <span class="muted">
                    Investimentos
                </span>

                <h2>
                    <?= $totalInvestimentos ?>
                </h2>
            </div>

            <div>
                <span class="muted">
                    Objetivos em andamento
                </span>

                <h2>
                    <?= $totalObjetivosAtivos ?>
                </h2>
            </div>

        </div>

    </div>

</div>

<div class="card" style="margin-top: 20px;">

    <h2>Segurança</h2>

    <p class="muted">
        Mantenha sua senha atualizada e encerre a sessão quando estiver
        utilizando um computador compartilhado.
    </p>

    <div class="actions">

        <a class="btn" href="alterar_senha.php">
            Alterar senha
        </a>

        <a class="btn btn-danger" href="<?= e($basePath) ?>logout.php">
            Sair da conta
        </a>

    </div>

</div>

<?php
require __DIR__ . '/../includes/footer.php';
?>