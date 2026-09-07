<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$bancos = $pdo->query(
    'SELECT id_banco, nome, codigo_banco FROM BANCO ORDER BY nome'
)->fetchAll();

$tipos = $pdo->query(
    'SELECT id_tipo_conta, nome FROM TIPO_CONTA ORDER BY nome'
)->fetchAll();

$page_title = 'Nova conta';
require __DIR__ . '/../includes/header.php';
?>
<h1>Nova conta</h1>

<form method="post" action="salvar.php">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="acao" value="criar">

    <div class="form-grid">
        <div class="field">
            <label>Nome da conta</label>
            <input name="nome_conta" required>
        </div>

        <div class="field">
            <label>Banco</label>
            <select name="id_banco" required>
                <option value="">Selecione</option>
                <?php foreach ($bancos as $banco): ?>
                    <option value="<?= (int) $banco['id_banco'] ?>">
                        <?= e($banco['nome']) ?> (<?= e($banco['codigo_banco']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label>Tipo de conta</label>
            <select name="id_tipo_conta" required>
                <option value="">Selecione</option>
                <?php foreach ($tipos as $tipo): ?>
                    <option value="<?= (int) $tipo['id_tipo_conta'] ?>">
                        <?= e($tipo['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label>Agência</label>
            <input name="agencia" required>
        </div>

        <div class="field">
            <label>Número da conta</label>
            <input name="num_conta" required>
        </div>

        <div class="field">
            <label>Saldo inicial</label>
            <input name="saldo_atual" type="number" min="0" step="0.01"
                   value="0" required>
        </div>
    </div>

    <div class="actions">
        <button type="submit">Cadastrar</button>
        <a class="button secondary" href="index.php">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>
