<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = 'Contas';

$stmt = $pdo->prepare(
    'SELECT c.*, b.nome AS banco, tc.nome AS tipo
     FROM CONTA c
     INNER JOIN BANCO b ON b.id_banco = c.id_banco
     INNER JOIN TIPO_CONTA tc ON tc.id_tipo_conta = c.id_tipo_conta
     WHERE c.id_usuario = :usuario
     ORDER BY c.nome_conta'
);
$stmt->execute(['usuario' => $id_usuario]);
$contas = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<h1>Minhas contas</h1>

<p>
    <a class="button" href="cadastrar.php">Nova conta</a>
</p>

<div class="card">
<table>
    <thead>
    <tr>
        <th>Conta</th>
        <th>Banco</th>
        <th>Tipo</th>
        <th>Agência</th>
        <th>Número</th>
        <th>Saldo</th>
        <th>Ações</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($contas as $conta): ?>
        <tr>
            <td><?= e($conta['nome_conta']) ?></td>
            <td><?= e($conta['banco']) ?></td>
            <td><?= e($conta['tipo']) ?></td>
            <td><?= e($conta['agencia']) ?></td>
            <td><?= e($conta['num_conta']) ?></td>
            <td><?= money((float) $conta['saldo_atual']) ?></td>
            <td>
                <a href="editar.php?id=<?= (int) $conta['id_conta'] ?>">Editar</a>
                |
                <form class="inline-form" method="post" action="excluir.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="id_conta" value="<?= (int) $conta['id_conta'] ?>">
                    <button type="submit" class="danger"
                            data-confirm="Excluir esta conta? A conta só poderá ser excluída se não houver registros dependentes e seu saldo for zero.">
                        Excluir
                    </button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>

    <?php if (!$contas): ?>
        <tr><td colspan="7">Nenhuma conta cadastrada.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
