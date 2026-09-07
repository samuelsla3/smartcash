<?php
require_once __DIR__.'/../config/database.php';require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/functions.php';$uid=usuarioLogado();if($_SERVER['REQUEST_METHOD']!=='POST')redirect('index.php');verifyCsrf();$id=intPost('id');if(!$id)redirect('index.php');
$stmt=db()->prepare('DELETE FROM OBJETIVO_FINANCEIRO WHERE id_objetivo=:id AND id_usuario=:u');$stmt->execute(['id'=>$id,'u'=>$uid]);flash('success','Objetivo excluído.');redirect('index.php');
