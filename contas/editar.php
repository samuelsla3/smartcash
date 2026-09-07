<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    redirect('index.php');
}

$stmt = $pdo->prepare(
    'SELECT *
     FROM CONTA
     WHERE id_conta = :id AND id_usuario = :usuario
     LIMIT 1'
);
$stmt->execute([
    'id' => $id,
    'usuario' => $id_usuario,
]);

$conta = $stmt->fetch();

if (!$conta) {
    http_response_code(404);
    exit('Conta não encontrada.');
}

$bancos = $pdo->query(
    'SELECT id_banco, nome FROM BANCO ORDER BY nome'
)->fetchAll();

$tipos = $pdo->query(
    'SELECT id_tipo_conta, nome FROM TIPO_CONTA ORDER BY nome'
)->fetchAll();

$page_title = 'Editar conta';
require __DIR__ . '/../includes/header.php';
?>
<h1>Editar conta</h1>

<form method="post" action="salvar.php">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="acao" value="editar">
    <input type="hidden" name="id_conta" value="<?= (int) $conta['id_conta'] ?>">

    <p class="muted">
        O saldo não é alterado manualmente nesta tela. Ele é controlado pelas
        movimentações financeiras.
    </p>

    <div class="form-grid">
        <div class="field">
            <label>Nome</label>
            <input name="nome_conta" required value="<?= e($conta['nome_conta']) ?>">
        </div>

        <div class="field">
            <label>Banco</label>
            <select name="id_banco" required>
                <?php foreach ($bancos as $banco): ?>
                    <option value="<?= (int) $banco['id_banco'] ?>"
                        <?= (int) $banco['id_banco'] === (int) $conta['id_banco'] ? 'selected' : '' ?>>
                        <?= e($banco['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label>Tipo</label>
            <select name="id_tipo_conta" required>
                <?php foreach ($tipos as $tipo): ?>
                    <option value="<?= (int) $tipo['id_tipo_conta'] ?>"
                        <?= (int) $tipo['id_tipo_conta'] === (int) $conta['id_tipo_conta'] ? 'selected' : '' ?>>
                        <?= e($tipo['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label>Agência</label>
            <input name="agencia" required value="<?= e($conta['agencia']) ?>">
        </div>

        <div class="field">
            <label>Número</label>
            <input name="num_conta" required value="<?= e($conta['num_conta']) ?>">
        </div>
    </div>

    <div class="actions">
        <button type="submit">Salvar</button>
        <a class="button secondary" href="index.php">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>
