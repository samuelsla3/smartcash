<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';

$uid = usuarioLogado();
$inicio = $_GET['inicio'] ?? date('Y-m-01');
$fim = $_GET['fim'] ?? date('Y-m-t');

$stmt = db()->prepare(
    'SELECT 
        COALESCE(c.nome, \'Sem categoria\') categoria,
        SUM(d.valor) total,
        COUNT(*) quantidade 
    FROM DESPESA d 
    LEFT JOIN CATEGORIA c ON c.id_categoria = d.id_categoria 
    WHERE d.id_usuario = :u 
        AND d.data BETWEEN :i AND :f 
    GROUP BY c.id_categoria, c.nome 
    ORDER BY total DESC'
);

$stmt->execute([
    'u' => $uid,
    'i' => $inicio,
    'f' => $fim
]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Relatório de despesas';
$basePath = '../';

require __DIR__.'/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Despesas por categoria</h1>
        <p class="muted">
            Período: <?=e(formatDateBr($inicio))?> a <?=e(formatDateBr($fim))?>
        </p>
    </div>

    <a 
        class="btn btn-secondary" 
        href="categorias.php?inicio=<?=e($inicio)?>&fim=<?=e($fim)?>"
    >
        Ver categorias
    </a>
</div>

<div class="table-card">

    <form class="form-row" method="get">

        <div class="form-group">
            <label>Início</label>
            <input 
                type="date" 
                name="inicio" 
                value="<?=e($inicio)?>"
            >
        </div>

        <div class="form-group">
            <label>Fim</label>
            <input 
                type="date" 
                name="fim" 
                value="<?=e($fim)?>"
            >
        </div>

        <button class="btn">Filtrar</button>

    </form>

    <table>
        <thead>
            <tr>
                <th>Categoria</th>
                <th>Quantidade</th>
                <th>Total</th>
            </tr>
        </thead>

        <tbody>

            <?php if(!$rows): ?>

                <tr>
                    <td colspan="3" class="empty">
                        Sem despesas no período.
                    </td>
                </tr>

            <?php else: foreach($rows as $r): ?>

                <tr>
                    <td><?=e($r['categoria'])?></td>
                    <td><?=e($r['quantidade'])?></td>
                    <td><?=money($r['total'])?></td>
                </tr>

            <?php endforeach; endif; ?>

        </tbody>
    </table>

</div>

<?php
require __DIR__.'/../includes/footer.php';
?>