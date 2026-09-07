<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$stmt = $pdo->prepare(
    'SELECT id_conta, nome_conta, saldo_atual
     FROM CONTA
     WHERE id_usuario = :usuario
     ORDER BY nome_conta'
);
$stmt->execute(['usuario' => $id_usuario]);
$contas = $stmt->fetchAll();

$page_title = 'Nova transferência';
require __DIR__ . '/../includes/header.php';
?>
<h1>Nova transferência</h1>

<form method="post" action="salvar.php">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

<div class="form-grid">
    <div class="field">
        <label>Conta de origem</label>
        <select name="id_conta_origem" required>
            <option value="">Selecione</option>
            <?php foreach ($contas as $conta): ?>
                <option value="<?= (int)$conta['id_conta'] ?>">
                    <?= e($conta['nome_conta']) ?>
                    — saldo <?= money((float)$conta['saldo_atual']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field">
        <label>Conta de destino</label>
        <select name="id_conta_destino" required>
            <option value="">Selecione</option>
            <?php foreach ($contas as $conta): ?>
                <option value="<?= (int)$conta['id_conta'] ?>">
                    <?= e($conta['nome_conta']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field">
        <label>Valor</label>
        <input name="valor" type="number" min="0.01" step="0.01" required>
    </div>

    <div class="field">
        <label>Data</label>
        <input name="data_transferencia" type="date"
               value="<?= date('Y-m-d') ?>" required>
    </div>

    <div class="field full">
        <label>Descrição</label>
        <textarea name="descricao"></textarea>
    </div>
</div>

<div class="actions">
    <button type="submit">Transferir</button>
    <a class="button secondary" href="index.php">Cancelar</a>
</div>
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>
