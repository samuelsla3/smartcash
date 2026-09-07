<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = 'Recebimentos';

$stmt = $pdo->prepare(
    'SELECT r.*, c.nome_conta, tr.nome AS tipo
     FROM RECEBIMENTO r
     INNER JOIN CONTA c ON c.id_conta = r.id_conta
     INNER JOIN TIPO_RECEBIMENTO tr
        ON tr.id_tipo_recebimento = r.id_tipo_recebimento
     WHERE r.id_usuario = :usuario
     ORDER BY r.data_recebimento DESC, r.id_recebimento DESC'
);
$stmt->execute(['usuario' => $id_usuario]);
$recebimentos = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<h1>Recebimentos</h1>
<p><a class="button" href="cadastrar.php">Novo recebimento</a></p>

<div class="card">
<table>
<thead>
<tr>
    <th>Descrição</th>
    <th>Tipo</th>
    <th>Conta</th>
    <th>Valor</th>
    <th>Data</th>
    <th>Recorrente</th>
    <th>Ações</th>
</tr>
</thead>
<tbody>
<?php foreach ($recebimentos as $item): ?>
<tr>
    <td><?= e($item['descricao']) ?></td>
    <td><?= e($item['tipo']) ?></td>
    <td><?= e($item['nome_conta']) ?></td>
    <td><?= money((float) $item['valor']) ?></td>
    <td><?= e($item['data_recebimento']) ?></td>
    <td><?= (int) $item['recorrente'] ? 'Sim' : 'Não' ?></td>
    <td>
        <a href="editar.php?id=<?= (int) $item['id_recebimento'] ?>">Editar</a>
        |
        <form class="inline-form" method="post" action="excluir.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id_recebimento" value="<?= (int) $item['id_recebimento'] ?>">
            <button type="submit" class="danger"
                    data-confirm="Excluir este recebimento? O valor será retirado novamente do saldo da conta.">
                Excluir
            </button>
        </form>
    </td>
</tr>
<?php endforeach; ?>

<?php if (!$recebimentos): ?>
<tr><td colspan="7">Nenhum recebimento cadastrado.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
