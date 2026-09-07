<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';

$uid=usuarioLogado();

$stmt=db()->prepare(
    'SELECT c.id_conta,c.nome_conta,b.nome banco,tc.nome tipo,c.saldo_atual
    FROM CONTA c
    LEFT JOIN BANCO b ON b.id_banco=c.id_banco
    LEFT JOIN TIPO_CONTA tc ON tc.id_tipo_conta=c.id_tipo_conta
    WHERE c.id_usuario=:u
    ORDER BY c.nome_conta'
);

$stmt->execute(['u'=>$uid]);
$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
$total=array_sum(array_map(fn($r)=>(float)$r['saldo_atual'],$rows));

$pageTitle='Relatório de saldo';
$basePath='../';

require __DIR__.'/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Saldo das contas</h1>
        <p class="muted">
            Saldo total: <strong><?=money($total)?></strong>
        </p>
    </div>
</div>

<div class="table-card">
    <table>
        <thead>
            <tr>
                <th>Conta</th>
                <th>Banco</th>
                <th>Tipo</th>
                <th>Saldo</th>
            </tr>
        </thead>

        <tbody>
            <?php if(!$rows):?>
                <tr>
                    <td colspan="4" class="empty">Nenhuma conta.</td>
                </tr>
            <?php else:foreach($rows as $r):?>
                <tr>
                    <td><?=e($r['nome_conta'])?></td>
                    <td><?=e($r['banco']??'-')?></td>
                    <td><?=e($r['tipo']??'-')?></td>
                    <td><?=money($r['saldo_atual'])?></td>
                </tr>
            <?php endforeach;endif;?>
        </tbody>
    </table>
</div>

<?php require __DIR__.'/../includes/footer.php';?>