<?php

require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';

$uid=usuarioLogado();

$stmt=db()->prepare('SELECT * FROM NOTIFICACAO WHERE id_usuario=:u ORDER BY data_envio DESC');
$stmt->execute(['u'=>$uid]);
$items=$stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle='Notificações';
$basePath='../';

require __DIR__.'/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Notificações</h1>
        <p class="muted">Mensagens enviadas para sua conta.</p>
    </div>
</div>

<div class="grid">
    <?php if(!$items):?>

        <div class="card empty">
            Nenhuma notificação.
        </div>

    <?php else:foreach($items as $n):?>

        <div
            class="card"
            style="<?=is_null($n['data_leitura'])?'border-left:4px solid #166534':''?>"
        >
            <div class="page-header" style="margin-bottom:8px">

                <strong><?=e($n['mensagem'])?></strong>

                <?php if(is_null($n['data_leitura'])):?>

                    <form method="post" action="ler.php">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?=e(csrfToken())?>"
                        >

                        <input
                            type="hidden"
                            name="id"
                            value="<?=$n['id_notificacao']?>"
                        >

                        <button class="btn btn-small">
                            Marcar como lida
                        </button>
                    </form>

                <?php else:?>

                    <span class="badge badge-success">
                        Lida
                    </span>

                <?php endif;?>

            </div>

            <small class="muted">
                Enviada em <?=formatDateBr($n['data_envio'])?>
            </small>
        </div>

    <?php endforeach;endif;?>
</div>

<?php require __DIR__.'/../includes/footer.php';?>