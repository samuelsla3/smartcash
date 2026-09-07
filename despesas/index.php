<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = 'Despesas';

$stmt = $pdo->prepare(
    'SELECT d.*,
            c.nome_conta,
            ca.nome_cartao,
            td.nome AS tipo_despesa,
            cat.nome AS categoria
     FROM DESPESA d
     LEFT JOIN CONTA c ON c.id_conta = d.id_conta
     LEFT JOIN CARTAO ca ON ca.id_cartao = d.id_cartao
     INNER JOIN TIPO_DESPESA td ON td.id_tipo_despesa = d.id_tipo_despesa
     INNER JOIN CATEGORIA cat ON cat.id_categoria = d.id_categoria
     WHERE d.id_usuario = :usuario
     ORDER BY d.`data` DESC, d.id_despesa DESC'
);
$stmt->execute(['usuario' => $id_usuario]);
$despesas = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<h1>Despesas</h1>
<p><a class="button" href="cadastrar.php">Nova despesa</a></p>

<div class="card">
<table>
<thead>
<tr>
    <th>Descrição</th>
    <th>Categoria</th>
    <th>Pagamento</th>
    <th>Valor</th>
    <th>Data</th>
    <th>Recorrente</th>
    <th>Ações</th>
</tr>
</thead>
<tbody>
<?php foreach ($despesas as $item): ?>
<tr>
    <td><?= e($item['descricao']) ?></td>
    <td><?= e($item['categoria']) ?></td>
    <td>
        <?= $item['id_conta']
            ? 'Conta: ' . e($item['nome_conta'])
            : 'Cartão: ' . e($item['nome_cartao']) ?>
    </td>
    <td><?= money((float) $item['valor']) ?></td>
    <td><?= e($item['data']) ?></td>
    <td><?= (int)$item['recorrente'] ? 'Sim' : 'Não' ?></td>
    <td>
        <a href="editar.php?id=<?= (int)$item['id_despesa'] ?>">Editar</a>
        |
        <form class="inline-form" method="post" action="excluir.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id_despesa" value="<?= (int)$item['id_despesa'] ?>">
            <button type="submit" class="danger"
                    data-confirm="Excluir esta despesa? O saldo/limite será restaurado.">
                Excluir
            </button>
        </form>
    </td>
</tr>
<?php endforeach; ?>

<?php if (!$despesas): ?>
<tr><td colspan="7">Nenhuma despesa cadastrada.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
