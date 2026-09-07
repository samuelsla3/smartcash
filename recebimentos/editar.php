<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) redirect('index.php');

$stmt = $pdo->prepare(
    'SELECT *
     FROM RECEBIMENTO
     WHERE id_recebimento = :id AND id_usuario = :usuario'
);
$stmt->execute(['id' => $id, 'usuario' => $id_usuario]);
$item = $stmt->fetch();

if (!$item) {
    http_response_code(404);
    exit('Recebimento não encontrado.');
}

$stmt = $pdo->prepare(
    'SELECT id_conta, nome_conta
     FROM CONTA
     WHERE id_usuario = :usuario
     ORDER BY nome_conta'
);
$stmt->execute(['usuario' => $id_usuario]);
$contas = $stmt->fetchAll();

$tipos = $pdo->query(
    'SELECT id_tipo_recebimento, nome
     FROM TIPO_RECEBIMENTO ORDER BY nome'
)->fetchAll();

$page_title = 'Editar recebimento';
require __DIR__ . '/../includes/header.php';
?>
<h1>Editar recebimento</h1>

<form method="post" action="salvar.php">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="acao" value="editar">
<input type="hidden" name="id_recebimento" value="<?= (int) $item['id_recebimento'] ?>">

<div class="form-grid">
    <div class="field">
        <label>Descrição</label>
        <input name="descricao" required value="<?= e($item['descricao']) ?>">
    </div>

    <div class="field">
        <label>Valor</label>
        <input name="valor" type="number" min="0.01" step="0.01"
               required value="<?= e((string) $item['valor']) ?>">
    </div>

    <div class="field">
        <label>Data</label>
        <input name="data_recebimento" type="date" required
               value="<?= e($item['data_recebimento']) ?>">
    </div>

    <div class="field">
        <label>Conta</label>
        <select name="id_conta" required>
            <?php foreach ($contas as $conta): ?>
                <option value="<?= (int) $conta['id_conta'] ?>"
                    <?= (int) $conta['id_conta'] === (int) $item['id_conta'] ? 'selected' : '' ?>>
                    <?= e($conta['nome_conta']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field">
        <label>Tipo</label>
        <select name="id_tipo_recebimento" required>
            <?php foreach ($tipos as $tipo): ?>
                <option value="<?= (int) $tipo['id_tipo_recebimento'] ?>"
                    <?= (int) $tipo['id_tipo_recebimento'] === (int) $item['id_tipo_recebimento'] ? 'selected' : '' ?>>
                    <?= e($tipo['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field">
        <label>Recorrência</label>
        <select name="recorrente" id="recorrente" required>
            <option value="0" <?= !(int)$item['recorrente'] ? 'selected' : '' ?>>Não</option>
            <option value="1" <?= (int)$item['recorrente'] ? 'selected' : '' ?>>Sim</option>
        </select>
    </div>

    <div class="field" id="frequencia-field">
        <label>Frequência</label>
        <select name="frequencia" id="frequencia">
            <option value="">Selecione</option>
            <?php foreach (['diaria'=>'Diária','semanal'=>'Semanal','mensal'=>'Mensal','anual'=>'Anual'] as $v=>$label): ?>
                <option value="<?= $v ?>" <?= $item['frequencia'] === $v ? 'selected' : '' ?>>
                    <?= $label ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field" id="proximo-field">
        <label>Próximo recebimento</label>
        <input name="proximo_recebimento" id="proximo_recebimento" type="date"
               value="<?= e($item['proximo_recebimento']) ?>">
    </div>
</div>

<div class="actions">
    <button type="submit">Salvar</button>
    <a class="button secondary" href="index.php">Cancelar</a>
</div>
</form>

<script>
const r = document.getElementById('recorrente');
const ff = document.getElementById('frequencia-field');
const pf = document.getElementById('proximo-field');
const f = document.getElementById('frequencia');
const d = document.getElementById('proximo_recebimento');
function update() {
    const enabled = r.value === '1';
    ff.hidden = !enabled; pf.hidden = !enabled;
    f.required = enabled; d.required = enabled;
    if (!enabled) { f.value = ''; d.value = ''; }
}
r.addEventListener('change', update); update();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
