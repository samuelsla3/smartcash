<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$uid = usuarioLogado();

$inicio = $_GET['inicio'] ?? date('Y-m-01');
$fim    = $_GET['fim'] ?? date('Y-m-t');

// Receitas agrupadas por tipo
$stmt = db()->prepare("
    SELECT
        COALESCE(t.nome, 'Sem tipo') AS tipo,
        SUM(r.valor) AS total,
        COUNT(*) AS quantidade
    FROM RECEBIMENTO r
    LEFT JOIN TIPO_RECEBIMENTO t
        ON t.id_tipo_recebimento = r.id_tipo_recebimento
    WHERE r.id_usuario = :u
      AND r.data_recebimento BETWEEN :i AND :f
    GROUP BY
        t.id_tipo_recebimento,
        t.nome
    ORDER BY total DESC
");

$stmt->execute([
    'u' => $uid,
    'i' => $inicio,
    'f' => $fim
]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total de receitas no período
$stmt = db()->prepare("
    SELECT COALESCE(SUM(valor), 0)
    FROM RECEBIMENTO
    WHERE id_usuario = :u
      AND data_recebimento BETWEEN :i AND :f
");

$stmt->execute([
    'u' => $uid,
    'i' => $inicio,
    'f' => $fim
]);

$total = (float) $stmt->fetchColumn();

$pageTitle = 'Relatório de receitas';
$basePath = '../';

require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Receitas</h1>

        <p class="muted">
            Total do período:
            <strong><?= money($total) ?></strong>
        </p>
    </div>
</div>

<div class="table-card">

    <form class="form-row" method="get">

        <div class="form-group">
            <label for="inicio">Início</label>
            <input
                type="date"
                id="inicio"
                name="inicio"
                value="<?= e($inicio) ?>"
            >
        </div>

        <div class="form-group">
            <label for="fim">Fim</label>
            <input
                type="date"
                id="fim"
                name="fim"
                value="<?= e($fim) ?>"
            >
        </div>

        <button class="btn" type="submit">
            Filtrar
        </button>

    </form>

    <table>

        <thead>
            <tr>
                <th>Tipo</th>
                <th>Quantidade</th>
                <th>Total</th>
            </tr>
        </thead>

        <tbody>

        <?php if (!$rows): ?>

            <tr>
                <td colspan="3" class="empty">
                    Sem receitas no período.
                </td>
            </tr>

        <?php else: ?>

            <?php foreach ($rows as $r): ?>

                <tr>
                    <td><?= e($r['tipo']) ?></td>
                    <td><?= e($r['quantidade']) ?></td>
                    <td><?= money($r['total']) ?></td>
                </tr>

            <?php endforeach; ?>

        <?php endif; ?>

        </tbody>

    </table>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>