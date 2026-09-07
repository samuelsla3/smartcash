<?php

require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';

$uid=usuarioLogado();

$stmt=db()->prepare('SELECT c.*,b.nome banco,co.nome_conta FROM CARTAO c LEFT JOIN BANCO b ON b.id_banco=c.id_banco LEFT JOIN CONTA co ON co.id_conta=c.id_conta AND co.id_usuario=c.id_usuario WHERE c.id_usuario=:id ORDER BY c.nome_cartao');

$stmt->execute(['id'=>$uid]);

$cards=$stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle='Cartões';
$basePath='../';

require __DIR__.'/../includes/header.php';

?>

<div class="page-header">
    <div>
        <h1>Cartões</h1>
        <p class="muted">Cartões associados às suas contas.</p>
    </div>

    <a class="btn" href="cadastrar.php">Novo cartão</a>
</div>

<div class="table-card">

    <?php if(!$cards):?>

        <div class="empty">
            Nenhum cartão cadastrado.
        </div>

    <?php else:?>

        <table>
            <thead>
                <tr>
                    <th>Cartão</th>
                    <th>Banco</th>
                    <th>Conta</th>
                    <th>Vencimento</th>
                    <th>Limite</th>
                    <th>Disponível</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach($cards as $c):?>

                    <tr>
                        <td><?=e($c['nome_cartao'])?></td>

                        <td><?=e($c['banco']??'-')?></td>

                        <td><?=e($c['nome_conta']??'-')?></td>

                        <td>
                            Dia <?=e($c['dia_vencimento'])?>
                        </td>

                        <td><?=money($c['limite_total'])?></td>

                        <td><?=money($c['limite_disponivel'])?></td>

                        <td class="actions">

                            <a
                                class="btn btn-small btn-secondary"
                                href="editar.php?id=<?=$c['id_cartao']?>"
                            >
                                Editar
                            </a>

                            <form
                                method="post"
                                action="excluir.php"
                                data-confirm="Excluir este cartão?"
                            >
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?=e(csrfToken())?>"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?=$c['id_cartao']?>"
                                >

                                <button class="btn btn-small btn-danger">
                                    Excluir
                                </button>
                            </form>

                        </td>
                    </tr>

                <?php endforeach;?>

            </tbody>
        </table>

    <?php endif;?>

</div>

<?php require __DIR__.'/../includes/footer.php';?>