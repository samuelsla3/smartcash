<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';

$uid=usuarioLogado();

$stmt=db()->prepare('SELECT i.*,t.nome tipo, c.nome_conta FROM INVESTIMENTO i LEFT JOIN TIPO_INVESTIMENTO t ON t.id_tipo_investimento=i.id_tipo_investimento LEFT JOIN CONTA c ON c.id_conta=i.id_conta AND c.id_usuario=i.id_usuario WHERE i.id_usuario=:u ORDER BY i.data_aplicacao DESC');
$stmt->execute(['u'=>$uid]);
$items=$stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle='Investimentos';
$basePath='../';

require __DIR__.'/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Investimentos</h1>
        <p class="muted">Acompanhe valor aplicado e rendimento.</p>
    </div>

    <a class="btn" href="cadastrar.php">Novo investimento</a>
</div>

<div class="table-card">

    <?php if(!$items):?>

        <div class="empty">
            Nenhum investimento cadastrado.
        </div>

    <?php else:?>

        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Conta</th>
                    <th>Aplicado</th>
                    <th>Rendimento</th>
                    <th>Atualizado</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach($items as $i):?>

                    <tr>
                        <td><?=e($i['nome'])?></td>
                        <td><?=e($i['tipo']??'-')?></td>
                        <td><?=e($i['nome_conta']??'-')?></td>
                        <td><?=money($i['valor_aplicado'])?></td>
                        <td><?=money($i['rendimento'])?></td>
                        <td><?=money((float)$i['valor_aplicado']+(float)$i['rendimento'])?></td>
                        <td><?=formatDateBr($i['data_aplicacao'])?></td>

                        <td class="actions">
                            <a
                                class="btn btn-small btn-secondary"
                                href="editar.php?id=<?=$i['id_investimento']?>"
                            >
                                Editar
                            </a>

                            <form
                                method="post"
                                action="excluir.php"
                                data-confirm="Excluir este investimento?"
                            >
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?=e(csrfToken())?>"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?=$i['id_investimento']?>"
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