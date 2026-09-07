<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
usuarioLogado();
$categories = getAllCategories();
$tree = buildCategoryTree($categories);
$pageTitle='Categorias'; $basePath='../';
require __DIR__.'/../includes/header.php';
?>
<div class="page-header"><div><h1>Categorias</h1><p class="muted">Categorias e subcategorias usadas nas despesas.</p></div><a class="btn" href="cadastrar.php">Nova categoria</a></div>
<div class="table-card">
<?php if (!$tree): ?><div class="empty">Nenhuma categoria cadastrada.</div><?php else: ?>
<table><thead><tr><th>Categoria</th><th>Ações</th></tr></thead><tbody>
<?php foreach($tree as $cat): ?>
<tr><td><?= e(str_repeat('— ', (int)$cat['nivel']).$cat['nome']) ?></td><td class="actions"><a class="btn btn-small btn-secondary" href="editar.php?id=<?= (int)$cat['id_categoria'] ?>">Editar</a><form method="post" action="excluir.php" data-confirm="Excluir esta categoria?"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="id" value="<?= (int)$cat['id_categoria'] ?>"><button class="btn btn-small btn-danger">Excluir</button></form></td></tr>
<?php endforeach; ?></tbody></table>
<?php endif; ?></div>
<?php require __DIR__.'/../includes/footer.php'; ?>
