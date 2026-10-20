<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
header('Cache-Control: no-store, private');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

function authPage(bool $signup): void
{
    if (usuarioLogado()) {
        redirect(url('dashboard/index.php'));
    }
    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();
        try {
            verifyNonce();
            $email = textValue('email', 150);
            $pass = $_POST['senha'] ?? '';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !is_string($pass) || $pass === '') {
                throw new DomainException('Informe e-mail e senha válidos.');
            }
            if ($signup) {
                $name = textValue('nome', 100);
                $birth = dateValue('data_nascimento');
                $phone = phoneValue();
                if (mb_strlen($name) < 2) {
                    throw new DomainException('Informe seu nome completo.');
                }
                if (
                    strlen($pass) < 8 ||
                    strlen($pass) > 72 ||
                    $pass !== ($_POST['confirmacao'] ?? '')
                ) {
                    throw new DomainException(
                        'Use uma senha de 8 a 72 bytes e confirme com a mesma senha.'
                    );
                }
                if (one('SELECT id_usuario FROM usuario WHERE email=?', [$email])) {
                    throw new DomainException('Este e-mail já está cadastrado.');
                }
                db()->beginTransaction();
                try {
                    query(
                        'INSERT INTO usuario (nome,email,senha,data_nascimento,telefone,receber_email) VALUES (?,?,?,?,?,?)',
                        [
                            $name,
                            $email,
                            password_hash($pass, PASSWORD_DEFAULT),
                            $birth,
                            $phone,
                            isset($_POST['receber_email']) ? 1 : 0
                        ]
                    );
                    $id = (int) db()->lastInsertId();
                    notificationFor(
                        $id,
                        'Boas-vindas ao SmartCash! Comece adicionando suas contas e organize seu próximo passo.'
                    );
                    db()->commit();
                } catch (Throwable $ex) {
                    if (db()->inTransaction()) {
                        db()->rollBack();
                    }
                    throw $ex;
                }
                consumeNonce();
                flash('success', 'Cadastro concluído! Entre para começar.');
                redirect(url('login.php'));
            }
            $attempts = $_SESSION['login_attempts'] ?? ['count' => 0, 'start' => time()];
            if (time() - $attempts['start'] > 600) {
                $attempts = ['count' => 0, 'start' => time()];
            }
            if ($attempts['count'] >= 10) {
                throw new DomainException(
                    'Muitas tentativas nesta sessão. Aguarde alguns minutos e tente novamente.'
                );
            }
            $user = one('SELECT id_usuario,nome,senha FROM usuario WHERE email=?', [$email]);
            if (!$user || !password_verify($pass, $user['senha'])) {
                ++$attempts['count'];
                $_SESSION['login_attempts'] = $attempts;
                throw new DomainException('E-mail ou senha inválidos.');
            }
            session_regenerate_id(true);
            unset($_SESSION['login_attempts']);
            consumeNonce();
            $_SESSION['id_usuario'] = (int) $user['id_usuario'];
            $_SESSION['nome_usuario'] = $user['nome'];
            $_SESSION['auth_fingerprint'] = hash('sha256', $user['senha']);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            redirect(url('dashboard/index.php'));
        } catch (Throwable $ex) {
            $errors[] = safeError($ex);
        }
    }
    $title = $signup ? 'Vamos começar?' : 'Bom ter você por aqui.';
    ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $signup ? 'Criar conta' : 'Entrar' ?> · SmartCash</title>
    <link rel="icon" href="<?= e(assetUrl('assets/favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= e(assetUrl('assets/css/app-dark.css')) ?>">
</head>
<body class="auth-body">
    <aside class="auth-story">
        <a class="brand" href="<?= e(url('login.php')) ?>"><span class="brand-symbol">$<span></span></span>smartcash</a>
        <div>
            <span class="eyebrow" style="color:#a7bba4">SEU ESPAÇO FINANCEIRO</span>
            <h1>Mais clareza<br>com seu dinheiro.<br><em>Mais liberdade<br>para viver.</em></h1>
            <p>Contas, despesas e objetivos no mesmo lugar. Organize o presente e abra espaço para o seu futuro.</p>
            <div class="auth-illustration" aria-hidden="true">
                <span class="eyebrow">UM PASSO A CADA DIA</span>
                <span class="illus-money">Seu próximo sonho ↗</span>
                <div class="spark-bars">
                    <span style="height:20%"></span>
                    <span style="height:36%"></span>
                    <span style="height:28%"></span>
                    <span style="height:52%"></span>
                    <span style="height:64%"></span>
                    <span style="height:59%"></span>
                    <span style="height:82%"></span>
                    <span style="height:100%"></span>
                </div>
            </div>
        </div>
        <span class="story-foot">SmartCash - IFBA, 2026</span>
    </aside>

    <main class="auth-main">
        <div class="auth-form">
            <a class="brand auth-mobile-brand" href="<?= e(url('login.php')) ?>"><span class="brand-symbol">$<span></span></span>smartcash</a>
            <span class="eyebrow"><?= $signup ? 'SEU FUTURO COMEÇA AQUI' : 'SEU ESPAÇO FINANCEIRO' ?></span>
            <h1><?= e($title) ?></h1>
            <p class="muted"><?= $signup ? 'Crie sua conta e dê o primeiro passo.' : 'Entre para acompanhar suas finanças.' ?></p>

            <?php if ($flash = getFlash()): ?>
                <div class="alert <?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
            <?php endif; ?>
            <?php foreach ($errors as $er): ?>
                <div class="alert error" role="alert"><?= e($er) ?></div>
            <?php endforeach; ?>

            <form method="post">
                <?php csrfField(); ?>
                <?php formNonce(); ?>

                <?php if ($signup): ?>
                    <div class="field">
                        <label for="nome">Nome completo</label>
                        <input id="nome" name="nome" required maxlength="100" autocomplete="name" value="<?= e($_POST['nome'] ?? '') ?>">
                    </div>
                <?php endif; ?>

                <div class="field">
                    <label for="email">E-mail</label>
                    <input id="email" name="email" type="email" required maxlength="150" autocomplete="email" placeholder="exemplo@email.com" value="<?= e($_POST['email'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="senha">Senha<?= $signup ? ' (mínimo de 8 caracteres)' : '' ?></label>
                    <input id="senha" name="senha" type="password" required <?= $signup ? 'minlength="8" maxlength="72"' : '' ?> autocomplete="<?= $signup ? 'new-password' : 'current-password' ?>">
                </div>

                <?php if ($signup): ?>
                    <div class="field">
                        <label for="confirmacao">Confirmar senha</label>
                        <input id="confirmacao" type="password" name="confirmacao" minlength="8" maxlength="72" required autocomplete="new-password">
                    </div>
                    <div class="form-grid">
                        <div class="field">
                            <label for="data_nascimento">Nascimento</label>
                            <input id="data_nascimento" name="data_nascimento" type="date" max="<?= date('Y-m-d') ?>" required value="<?= e($_POST['data_nascimento'] ?? '') ?>">
                        </div>
                        <div class="field">
                            <label for="telefone">Telefone (opcional)</label>
                            <input id="telefone" name="telefone" type="tel" maxlength="20" autocomplete="tel" value="<?= e($_POST['telefone'] ?? '') ?>">
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($signup): ?>
                    <label class="checkbox auth-email"><input type="checkbox" name="receber_email" value="1" <?= isset($_POST['receber_email']) ? 'checked' : '' ?>> Receber comunicações por e-mail</label>
                <?php endif; ?>

                <button class="btn" type="submit"><?= $signup ? 'Criar minha conta' : 'Entrar na minha conta' ?> <?= icon('arrow') ?></button>
            </form>

            <p class="auth-switch"><?= $signup ? 'Já tem uma conta?' : 'Ainda não tem uma conta?' ?> <a href="<?= e(url($signup ? 'login.php' : 'cadastro.php')) ?>"><?= $signup ? 'Entrar' : 'Criar conta' ?></a></p>
            <p class="muted small" style="text-align:center"></p>
        </div>
    </main>

    <script src="<?= e(assetUrl('assets/js/password-visibility.js')) ?>" defer></script>
    <script src="<?= e(assetUrl('assets/js/usability.js')) ?>" defer></script>
</body>
</html>
<?php
}
