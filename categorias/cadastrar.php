<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
usuarioLogado();
$categories=getAllCategories(); $errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verifyCsrf(); $nome=trim((string)($_POST['nome']??'')); $pai=$_POST['id_categoria_pai']!==''?intPost('id_categoria_pai'):null;
 if($nome==='')$errors[]='Informe o nome.'; if(mb_strlen($nome)>100)$errors[]='Nome muito longo.';
 if(!$errors){$stmt=db()->prepare('INSERT INTO CATEGORIA (id_categoria_pai,nome) VALUES (:pai,:nome)');$stmt->execute(['pai'=>$pai,'nome'=>$nome]);flash('success','Categoria cadastrada.');redirect('index.php');}
}
$pageTitle='Nova categoria';$basePath='../';require __DIR__.'/../includes/header.php';?>
<div class="page-header"><h1>Nova categoria</h1><a class="btn btn-secondary" href="index.php">Voltar</a></div>
<div class="form-card"><?php foreach($errors as $err):?><div class="alert error"><?=e($err)?></div><?php endforeach;?>
<form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>">
<div class="form-group"><label for="nome">Nome</label><input id="nome" name="nome" maxlength="100" required value="<?=e($_POST['nome']??'')?>"></div>
<div class="form-group"><label for="id_categoria_pai">Categoria pai</label><select id="id_categoria_pai" name="id_categoria_pai"><option value="">Nenhuma (categoria principal)</option><?=categoryOptions($categories, isset($pai)?$pai:null)?></select></div>
<button class="btn">Salvar</button></form></div><?php require __DIR__.'/../includes/footer.php';?>
