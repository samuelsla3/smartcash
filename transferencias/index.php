<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = 'Transferências';

$stmt = $pdo->prepare(
    'SELECT t.*,
            o.nome_conta AS conta_origem,
            d.nome_conta AS conta_destino
     FROM TRANSFERENCIA t
     INNER JOIN CONTA o ON o.id_conta = t.id_conta_origem
     LEFT JOIN CONTA d ON d.id_conta = t.id_conta_destino
     WHERE t.id_usuario = :usuario
     ORDER BY t.data_transferencia DESC, t.id_transferencia DESC'
);
$stmt->execute(['usuario' => $id_usuario]);
$transferencias = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<h1>Transferências</h1>
<p><a class="button" href="cadastrar.php">Nova transferência</a></p>

<div class="card">
<table>
<thead>
<tr>
    <th>Origem</th>
    <th>Destino</th>
    <th>Valor</th>
    <th>Data</th>
    <th>Descrição</th>
</tr>
</thead>
<tbody>
<?php foreach ($transferencias as $item): ?>
<tr>
    <td><?= e($item['conta_origem']) ?></td>
    <td><?= e($item['conta_destino'] ?? 'Sem conta de destino') ?></td>
    <td><?= money((float)$item['valor']) ?></td>
    <td><?= e($item['data_transferencia']) ?></td>
    <td><?= e($item['descricao']) ?></td>
</tr>
<?php endforeach; ?>

<?php if (!$transferencias): ?>
<tr><td colspan="5">Nenhuma transferência cadastrada.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
