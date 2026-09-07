<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

usuarioLogado();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id)
    redirect('index.php');

$stmt = db()->prepare('SELECT id_categoria,id_categoria_pai,nome FROM CATEGORIA WHERE id_categoria=:id');
$stmt->execute(['id' => $id]);

$cat = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cat) {
    flash('error', 'Categoria não encontrada.');
    redirect('index.php');
}

$categories = getAllCategories();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $nome = trim((string)($_POST['nome'] ?? ''));
    $pai = $_POST['id_categoria_pai'] !== '' ? intPost('id_categoria_pai') : null;

    if ($pai === $id)
        $errors[] = 'Uma categoria não pode ser pai dela mesma.';

    if ($nome === '')
        $errors[] = 'Informe o nome.';

    if (!$errors) {
        $stmt = db()->prepare('UPDATE CATEGORIA SET id_categoria_pai=:pai,nome=:nome WHERE id_categoria=:id');

        $stmt->execute([
            'pai' => $pai,
            'nome' => $nome,
            'id' => $id
        ]);

        flash('success', 'Categoria atualizada.');
        redirect('index.php');
    }
}

$pageTitle = 'Editar categoria';
$basePath = '../';

require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Editar categoria</h1>
    <a class="btn btn-secondary" href="index.php">Voltar</a>
</div>

<div class="form-card">

    <?php foreach ($errors as $err): ?>
        <div class="alert error"><?=e($err)?></div>
    <?php endforeach; ?>

    <form method="post">

        <input
            type="hidden"
            name="csrf_token"
            value="<?=e(csrfToken())?>"
        >

        <div class="form-group">
            <label>Nome</label>
            <input
                name="nome"
                maxlength="100"
                required
                value="<?=e($_POST['nome'] ?? $cat['nome'])?>"
            >
        </div>

        <div class="form-group">
            <label>Categoria pai</label>

            <select name="id_categoria_pai">
                <option value="">Nenhuma</option>
                <?=categoryOptions(
                    $categories,
                    isset($_POST['id_categoria_pai'])
                        ? intPost('id_categoria_pai')
                        : (int)($cat['id_categoria_pai'] ?? 0),
                    $id
                )?>
            </select>
        </div>

        <button class="btn">Salvar alterações</button>

    </form>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>