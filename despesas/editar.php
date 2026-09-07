<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) redirect('index.php');

$stmt = $pdo->prepare(
    'SELECT *
     FROM DESPESA
     WHERE id_despesa = :id AND id_usuario = :usuario'
);
$stmt->execute(['id' => $id, 'usuario' => $id_usuario]);
$item = $stmt->fetch();

if (!$item) {
    http_response_code(404);
    exit('Despesa não encontrada.');
}

$stmt = $pdo->prepare(
    'SELECT id_conta, nome_conta FROM CONTA
     WHERE id_usuario = :usuario ORDER BY nome_conta'
);
$stmt->execute(['usuario' => $id_usuario]);
$contas = $stmt->fetchAll();

$stmt = $pdo->prepare(
    'SELECT id_cartao, nome_cartao, limite_disponivel
     FROM CARTAO WHERE id_usuario = :usuario ORDER BY nome_cartao'
);
$stmt->execute(['usuario' => $id_usuario]);
$cartoes = $stmt->fetchAll();

$tipos = $pdo->query(
    'SELECT id_tipo_despesa, nome FROM TIPO_DESPESA ORDER BY nome'
)->fetchAll();

$categorias = $pdo->query(
    'SELECT c.id_categoria, c.nome, p.nome AS pai
     FROM CATEGORIA c
     LEFT JOIN CATEGORIA p ON p.id_categoria = c.id_categoria_pai
     ORDER BY COALESCE(p.nome, c.nome), c.nome'
)->fetchAll();

$formaAtual = $item['id_conta'] ? 'conta' : 'cartao';

$page_title = 'Editar despesa';
require __DIR__ . '/../includes/header.php';
?>
<h1>Editar despesa</h1>

<form method="post" action="salvar.php">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="acao" value="editar">
<input type="hidden" name="id_despesa" value="<?= (int)$item['id_despesa'] ?>">

<div class="form-grid">
    <div class="field">
        <label>Descrição</label>
        <input name="descricao" required value="<?= e($item['descricao']) ?>">
    </div>

    <div class="field">
        <label>Valor</label>
        <input name="valor" type="number" min="0.01" step="0.01"
               required value="<?= e((string)$item['valor']) ?>">
    </div>

    <div class="field">
        <label>Data</label>
        <input name="data" type="date" required value="<?= e($item['data']) ?>">
    </div>

    <div class="field">
        <label>Tipo de despesa</label>
        <select name="id_tipo_despesa" required>
            <?php foreach ($tipos as $tipo): ?>
                <option value="<?= (int)$tipo['id_tipo_despesa'] ?>"
                    <?= (int)$tipo['id_tipo_despesa'] === (int)$item['id_tipo_despesa'] ? 'selected' : '' ?>>
                    <?= e($tipo['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field">
        <label>Categoria</label>
        <select name="id_categoria" required>
            <?php foreach ($categorias as $categoria): ?>
                <option value="<?= (int)$categoria['id_categoria'] ?>"
                    <?= (int)$categoria['id_categoria'] === (int)$item['id_categoria'] ? 'selected' : '' ?>>
                    <?= $categoria['pai'] ? e($categoria['pai'] . ' → ' . $categoria['nome']) : e($categoria['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field">
        <label>Forma de pagamento</label>
        <select name="forma_pagamento" id="forma_pagamento" required>
            <option value="conta" <?= $formaAtual === 'conta' ? 'selected' : '' ?>>Conta</option>
            <option value="cartao" <?= $formaAtual === 'cartao' ? 'selected' : '' ?>>Cartão</option>
        </select>
    </div>

    <div class="field" id="conta-field">
        <label>Conta</label>
        <select name="id_conta" id="id_conta">
            <option value="">Selecione</option>
            <?php foreach ($contas as $conta): ?>
                <option value="<?= (int)$conta['id_conta'] ?>"
                    <?= (int)$conta['id_conta'] === (int)$item['id_conta'] ? 'selected' : '' ?>>
                    <?= e($conta['nome_conta']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field" id="cartao-field">
        <label>Cartão</label>
        <select name="id_cartao" id="id_cartao">
            <option value="">Selecione</option>
            <?php foreach ($cartoes as $cartao): ?>
                <option value="<?= (int)$cartao['id_cartao'] ?>"
                    <?= (int)$cartao['id_cartao'] === (int)$item['id_cartao'] ? 'selected' : '' ?>>
                    <?= e($cartao['nome_cartao']) ?>
                    — disponível <?= money((float)$cartao['limite_disponivel']) ?>
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

    <div class="field" id="freq-field">
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

    <div class="field" id="next-field">
        <label>Próxima cobrança</label>
        <input name="proxima_cobranca" id="proxima_cobranca" type="date"
               value="<?= e($item['proxima_cobranca']) ?>">
    </div>
</div>

<div class="actions">
    <button type="submit">Salvar</button>
    <a class="button secondary" href="index.php">Cancelar</a>
</div>
</form>

<script>
const forma = document.getElementById('forma_pagamento');
const contaField = document.getElementById('conta-field');
const cartaoField = document.getElementById('cartao-field');
const conta = document.getElementById('id_conta');
const cartao = document.getElementById('id_cartao');

function updatePayment() {
    const isConta = forma.value === 'conta';
    const isCartao = forma.value === 'cartao';
    contaField.hidden = !isConta;
    cartaoField.hidden = !isCartao;
    conta.required = isConta;
    cartao.required = isCartao;
    if (!isConta) conta.value = '';
    if (!isCartao) cartao.value = '';
}

const recorrente = document.getElementById('recorrente');
const freqField = document.getElementById('freq-field');
const nextField = document.getElementById('next-field');
const freq = document.getElementById('frequencia');
const next = document.getElementById('proxima_cobranca');

function updateRecurrence() {
    const enabled = recorrente.value === '1';
    freqField.hidden = !enabled;
    nextField.hidden = !enabled;
    freq.required = enabled;
    next.required = enabled;
    if (!enabled) { freq.value = ''; next.value = ''; }
}

forma.addEventListener('change', updatePayment);
recorrente.addEventListener('change', updateRecurrence);
updatePayment();
updateRecurrence();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
