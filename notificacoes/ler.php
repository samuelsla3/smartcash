<?php
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';
$uid=usuarioLogado();
if($_SERVER['REQUEST_METHOD']!=='POST') redirect('index.php');
verifyCsrf();
$id=intPost('id');
if(!$id) redirect('index.php');
$stmt=db()->prepare('UPDATE NOTIFICACAO SET data_leitura=NOW() WHERE id_notificacao=:id AND id_usuario=:u AND data_leitura IS NULL');
$stmt->execute(['id'=>$id,'u'=>$uid]);
redirect('index.php');
