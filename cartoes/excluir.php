<?php

require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';

$uid=usuarioLogado();

if($_SERVER['REQUEST_METHOD']!=='POST')
    redirect('index.php');

verifyCsrf();

$id=intPost('id');

if(!$id)
    redirect('index.php');

$stmt=db()->prepare('SELECT COUNT(*) FROM DESPESA WHERE id_cartao=:id AND id_usuario=:u');
$stmt->execute(['id'=>$id,'u'=>$uid]);

if((int)$stmt->fetchColumn()>0){
    flash('error','Não é possível excluir: existem despesas vinculadas a este cartão.');
    redirect('index.php');
}

$stmt=db()->prepare('DELETE FROM CARTAO WHERE id_cartao=:id AND id_usuario=:u');
$stmt->execute(['id'=>$id,'u'=>$uid]);

flash('success','Cartão excluído.');
redirect('index.php');