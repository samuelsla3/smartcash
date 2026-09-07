<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

usuarioLogado();

if($_SERVER['REQUEST_METHOD']!=='POST')
    redirect('index.php');

verifyCsrf();

$id=intPost('id');

if(!$id)
    redirect('index.php');

$stmt=db()->prepare('SELECT COUNT(*) FROM CATEGORIA WHERE id_categoria_pai=:id');
$stmt->execute(['id'=>$id]);

if((int)$stmt->fetchColumn()>0){
    flash('error','Não é possível excluir: existem subcategorias vinculadas.');
    redirect('index.php');
}

$stmt=db()->prepare('SELECT COUNT(*) FROM DESPESA WHERE id_categoria=:id');
$stmt->execute(['id'=>$id]);

if((int)$stmt->fetchColumn()>0){
    flash('error','Não é possível excluir: existem despesas usando esta categoria.');
    redirect('index.php');
}

$stmt=db()->prepare('DELETE FROM CATEGORIA WHERE id_categoria=:id');
$stmt->execute(['id'=>$id]);

flash('success','Categoria excluída.');
redirect('index.php');