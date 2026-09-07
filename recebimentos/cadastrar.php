<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$contasStmt = $pdo->prepare(
    'SELECT id_conta, nome_conta, saldo_atual
     FROM CONTA
     WHERE id_usuario = :usuario
     ORDER BY nome_conta'
);
$contasStmt->execute(['usuario' => $id_usuario]);
$contas = $contasStmt->fetchAll();

$tipos = $pdo->query(
    'SELECT id_tipo_recebimento, nome
     FROM TIPO_RECEBIMENTO
     ORDER BY nome'
)->fetchAll();

$page_title = 'Novo recebimento';
require __DIR__ . '/../includes/header.php';
?>
<h1>Novo recebimento</h1>

<form method="post" action="salvar.php">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="acao" value="criar">

<div class="form-grid">
    <div class="field">
        <label>Descrição</label>
        <input name="descricao" required>
    </div>

    <div class="field">
        <label>Valor</label>
        <input name="valor" type="number" min="0.01" step="0.01" required>
    </div>

    <div class="field">
        <label>Data</label>
        <input name="data_recebimento" type="date"
               value="<?= date('Y-m-d') ?>" required>
    </div>

    <div class="field">
        <label>Conta</label>
        <select name="id_conta" required>
            <option value="">Selecione</option>
            <?php foreach ($contas as $conta): ?>
                <option value="<?= (int) $conta['id_conta'] ?>">
                    <?= e($conta['nome_conta']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field">
        <label>Tipo de recebimento</label>
        <select name="id_tipo_recebimento" required>
            <option value="">Selecione</option>
            <?php foreach ($tipos as $tipo): ?>
                <option value="<?= (int) $tipo['id_tipo_recebimento'] ?>">
                    <?= e($tipo['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field">
        <label>Recorrência</label>
        <select name="recorrente" id="recorrente" required>
            <option value="0">Não</option>
            <option value="1">Sim</option>
        </select>
    </div>

    <div class="field" id="frequencia-field" hidden>
        <label>Frequência</label>
        <select name="frequencia" id="frequencia">
            <option value="">Selecione</option>
            <option value="diaria">Diária</option>
            <option value="semanal">Semanal</option>
            <option value="mensal">Mensal</option>
            <option value="anual">Anual</option>
        </select>
    </div>

    <div class="field" id="proximo-field" hidden>
        <label>Próximo recebimento</label>
        <input name="proximo_recebimento" id="proximo_recebimento" type="date">
    </div>
</div>

<div class="actions">
    <button type="submit">Cadastrar</button>
    <a class="button secondary" href="index.php">Cancelar</a>
</div>
</form>

<script>
const recorrente = document.getElementById('recorrente');
const freqField = document.getElementById('frequencia-field');
const nextField = document.getElementById('proximo-field');
const freq = document.getElementById('frequencia');
const nextDate = document.getElementById('proximo_recebimento');

function updateRecurrence() {
    const enabled = recorrente.value === '1';
    freqField.hidden = !enabled;
    nextField.hidden = !enabled;
    freq.required = enabled;
    nextDate.required = enabled;

    if (!enabled) {
        freq.value = '';
        nextDate.value = '';
    }
}
recorrente.addEventListener('change', updateRecurrence);
updateRecurrence();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
