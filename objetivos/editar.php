<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$uid = usuarioLogado();
$errors = [];

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    redirect('index.php');
}

$stmt = db()->prepare(
    'SELECT *
     FROM OBJETIVO_FINANCEIRO
     WHERE id_objetivo = :id
       AND id_usuario = :u'
);

$stmt->execute([
    'id' => $id,
    'u' => $uid
]);

$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    flash('error', 'Objetivo não encontrado.');
    redirect('index.php');
}

$statuses = [
    'Em andamento',
    'Concluído',
    'Cancelado'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $desc = trim(
        (string) ($_POST['descricao'] ?? $item['descricao'])
    );

    $alvo = decimalPost('valor_objetivo');
    $atual = decimalPost('valor_atual');

    $dataLimiteInformada = trim(
        (string) ($_POST['data_limite'] ?? '')
    );

    $limite = $dataLimiteInformada !== ''
        ? datePost('data_limite')
        : null;

    $status = trim(
        (string) ($_POST['status'] ?? $item['status'])
    );

    if ($desc === '') {
        $errors[] = 'Informe a descrição.';
    }

    if ($alvo === null || $alvo <= 0) {
        $errors[] = 'Valor objetivo inválido.';
    }

    if ($atual === null) {
        $errors[] = 'Valor atual inválido.';
    }

    if ($dataLimiteInformada !== '' && $limite === null) {
        $errors[] = 'Data limite inválida.';
    }

    if (!in_array($status, $statuses, true)) {
        $errors[] = 'Status inválido.';
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'UPDATE OBJETIVO_FINANCEIRO
             SET
                descricao = :d,
                valor_objetivo = :v,
                valor_atual = :a,
                data_limite = :l,
                status = :s
             WHERE id_objetivo = :id
               AND id_usuario = :u'
        );

        $stmt->execute([
            'u' => $uid,
            'd' => $desc,
            'v' => $alvo,
            'a' => $atual,
            'l' => $limite,
            's' => $status,
            'id' => $id
        ]);

        flash('success', 'Objetivo atualizado.');
        redirect('index.php');
    }
}

$pageTitle = 'Editar objetivo';
$basePath = '../';

require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">

    <h1>Editar objetivo</h1>

    <a class="btn btn-secondary" href="index.php">
        Voltar
    </a>

</div>

<div class="form-card">

    <?php foreach ($errors as $er): ?>
        <div class="alert error">
            <?= e($er) ?>
        </div>
    <?php endforeach; ?>

    <form method="post">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrfToken()) ?>"
        >

        <div class="form-group">

            <label>Descrição</label>

            <input
                name="descricao"
                maxlength="200"
                required
                value="<?= e($_POST['descricao'] ?? $item['descricao']) ?>"
            >

        </div>

        <div class="form-row">

            <div class="form-group">

                <label>Valor objetivo</label>

                <input
                    name="valor_objetivo"
                    inputmode="decimal"
                    required
                    value="<?= e($_POST['valor_objetivo'] ?? $item['valor_objetivo']) ?>"
                >

            </div>

            <div class="form-group">

                <label>Valor atual</label>

                <input
                    name="valor_atual"
                    inputmode="decimal"
                    required
                    value="<?= e($_POST['valor_atual'] ?? $item['valor_atual']) ?>"
                >

            </div>

        </div>

        <div class="form-row">

            <div class="form-group">

                <label>Data limite</label>

                <input
                    type="date"
                    name="data_limite"
                    value="<?= e($_POST['data_limite'] ?? ($item['data_limite'] ?? '')) ?>"
                >

            </div>

            <div class="form-group">

                <label>Status</label>

                <select name="status">

                    <?php foreach ($statuses as $st): ?>

                        <option
                            value="<?= e($st) ?>"
                            <?= ($_POST['status'] ?? $item['status']) === $st
                                ? 'selected'
                                : '' ?>
                        >
                            <?= e($st) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

        </div>

        <button class="btn">
            Salvar
        </button>

    </form>

</div>

<?php
require __DIR__ . '/../includes/footer.php';
?>