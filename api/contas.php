<?php

require_once __DIR__ . '/../includes/app.php';
jsonResponse(
    rows(
        'SELECT c.id_conta,c.nome_conta,c.saldo_atual,c.id_banco FROM conta c WHERE c.id_usuario=? ORDER BY ' .
            accountOrder('c'),
        [usuarioLogado()]
    )
);
