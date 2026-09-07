<?php
declare(strict_types=1);
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';
$uid=usuarioLogado();
$stmt=db()->prepare('SELECT c.id_conta,c.nome_conta,c.num_conta,c.agencia,c.saldo_atual,b.nome banco,tc.nome tipo FROM CONTA c LEFT JOIN BANCO b ON b.id_banco=c.id_banco LEFT JOIN TIPO_CONTA tc ON tc.id_tipo_conta=c.id_tipo_conta WHERE c.id_usuario=:u ORDER BY c.nome_conta');
$stmt->execute(['u'=>$uid]);
jsonResponse(['contas'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
