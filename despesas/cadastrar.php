<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$stmt = $pdo->prepare(
    'SELECT id_conta, nome_conta
     FROM CONTA
     WHERE id_usuario = :usuario
     ORDER BY nome_conta'
);
$stmt->execute(['usuario' => $id_usuario]);
$contas = $stmt->fetchAll();

$stmt = $pdo->prepare(
    'SELECT id_cartao, nome_cartao, limite_disponivel
     FROM CARTAO
     WHERE id_usuario = :usuario
     ORDER BY nome_cartao'
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

$page_title = 'Nova despesa';
require __DIR__ . '/../includes/header.php';
?>
<h1>Nova despesa</h1>

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
        <input name="data" type="date" value="<?= date('Y-m-d') ?>" required>
    </div>

    <div class="field">
        <label>Tipo de despesa</label>
        <select name="id_tipo_despesa" required>
            <option value="">Selecione</option>
            <?php foreach ($tipos as $tipo): ?>
                <option value="<?= (int)$tipo['id_tipo_despesa'] ?>">
                    <?= e($tipo['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field">
        <label>Categoria</label>
        <select name="id_categoria" required>
            <option value="">Selecione</option>
            <?php foreach ($categorias as $categoria): ?>
                <option value="<?= (int)$categoria['id_categoria'] ?>">
                    <?= $categoria['pai'] ? e($categoria['pai'] . ' → ' . $categoria['nome']) : e($categoria['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field">
        <label>Forma de pagamento</label>
        <select name="forma_pagamento" id="forma_pagamento" required>
            <option value="">Selecione</option>
            <option value="conta">Conta</option>
            <option value="cartao">Cartão</option>
        </select>
    </div>

    <div class="field" id="conta-field" hidden>
        <label>Conta</label>
        <select name="id_conta" id="id_conta">
            <option value="">Selecione</option>
            <?php foreach ($contas as $conta): ?>
                <option value="<?= (int)$conta['id_conta'] ?>">
                    <?= e($conta['nome_conta']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field" id="cartao-field" hidden>
        <label>Cartão</label>
        <select name="id_cartao" id="id_cartao">
            <option value="">Selecione</option>
            <?php foreach ($cartoes as $cartao): ?>
                <option value="<?= (int)$cartao['id_cartao'] ?>">
                    <?= e($cartao['nome_cartao']) ?>
                    — limite disponível <?= money((float)$cartao['limite_disponivel']) ?>
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

    <div class="field" id="freq-field" hidden>
        <label>Frequência</label>
        <select name="frequencia" id="frequencia">
            <option value="">Selecione</option>
            <option value="diaria">Diária</option>
            <option value="semanal">Semanal</option>
            <option value="mensal">Mensal</option>
            <option value="anual">Anual</option>
        </select>
    </div>

    <div class="field" id="next-field" hidden>
        <label>Próxima cobrança</label>
        <input name="proxima_cobranca" id="proxima_cobranca" type="date">
    </div>
</div>

<div class="actions">
    <button type="submit">Cadastrar</button>
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

    if (!enabled) {
        freq.value = '';
        next.value = '';
    }
}

forma.addEventListener('change', updatePayment);
recorrente.addEventListener('change', updateRecurrence);
updatePayment();
updateRecurrence();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
