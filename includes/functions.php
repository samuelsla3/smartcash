<?php

declare(strict_types=1);
date_default_timezone_set('America/Bahia');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'path' => '/',
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax'
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

function e(mixed $v): string
{
    return htmlspecialchars(is_scalar($v) ? (string) $v : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function db(): PDO
{
    global $pdo;
    return $pdo;
}

function query(string $sql, array $params = []): PDOStatement
{
    $s = db()->prepare($sql);
    $s->execute($params);
    return $s;
}

function rows(string $sql, array $params = []): array
{
    return query($sql, $params)->fetchAll();
}

function one(string $sql, array $params = []): ?array
{
    return query($sql, $params)->fetch() ?: null;
}

function scalar(string $sql, array $params = []): mixed
{
    return query($sql, $params)->fetchColumn();
}

function usuarioLogado(): int
{
    return (int) ($_SESSION['id_usuario'] ?? 0);
}

function baseUrl(): string
{
    $root = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $doc = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
    if ($doc && str_starts_with($root, $doc . '/')) {
        return substr($root, strlen($doc));
    }
    if ($root === $doc) {
        return '';
    }
    return rtrim(getenv('APP_BASE_PATH') ?: '/smartcash', '/');
}

function url(string $path = ''): string
{
    return baseUrl() . '/' . ltrim($path, '/');
}

function redirect(string $u): never
{
    header('Location: ' . $u);
    exit();
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function get_flash(): ?array
{
    return getFlash();
}

function csrfToken(): string
{
    return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
}

function csrf_token(): string
{
    return csrfToken();
}

function verifyCsrf(): void
{
    if (
        !is_string($_POST['csrf_token'] ?? null) ||
        !hash_equals(csrfToken(), $_POST['csrf_token'])
    ) {
        http_response_code(403);
        exit('Sua sessão de segurança expirou. Volte, atualize a página e tente novamente.');
    }
}

function verify_csrf(?string $unused = null): void
{
    verifyCsrf();
}

function csrfField(): void
{
    echo '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function formNonce(): void
{
    $n = bin2hex(random_bytes(16));
    $_SESSION['forms'][$n] = time();
    foreach ($_SESSION['forms'] as $key => $t) {
        if ($t < time() - 14400) {
            unset($_SESSION['forms'][$key]);
        }
    }
    echo '<input type="hidden" name="form_nonce" value="' . $n . '">';
}

function verifyNonce(): void
{
    if (
        !is_string($_POST['form_nonce'] ?? null) ||
        !isset($_SESSION['forms'][$_POST['form_nonce']])
    ) {
        throw new DomainException(
            'Esta ação já foi enviada ou expirou. Atualize a página antes de tentar novamente.'
        );
    }
}

function consumeNonce(): void
{
    unset($_SESSION['forms'][$_POST['form_nonce'] ?? '']);
}

function money(float|int|string|null $v): string
{
    return 'R$ ' . number_format((float) $v, 2, ',', '.');
}
// Conversão exata em centavos, sem cálculos financeiros em ponto flutuante.
function cents(mixed $v, bool $signed = false): int
{
    if (!is_string($v) && !is_int($v)) {
        throw new DomainException('Informe um valor monetário válido.');
    }
    $s = trim((string) $v);
    if (str_contains($s, ',')) {
        if (!preg_match('/^-?(?:\d+|\d{1,3}(?:\.\d{3})+),\d{1,2}$/D', $s)) {
            throw new DomainException('Use valores como 150,00 ou 1.250,50.');
        }
        $s = str_replace(['.', ','], ['', '.'], $s);
    }
    if (!preg_match('/^(-?)(\d{1,13})(?:\.(\d{1,2}))?$/D', $s, $m)) {
        throw new DomainException('Use até duas casas decimais e um valor válido.');
    }
    $v = (int) $m[2] * 100 + (int) str_pad($m[3] ?? '', 2, '0');
    if (($m[1] ?? '') === '-') {
        $v = -$v;
    }
    if (!$signed && $v < 0) {
        throw new DomainException('O valor não pode ser negativo.');
    }
    return $v;
}

function dec(int $c): string
{
    return ($c < 0 ? '-' : '') .
        intdiv(abs($c), 100) .
        '.' .
        str_pad((string) (abs($c) % 100), 2, '0', STR_PAD_LEFT);
}

function amount(string $key, bool $positive = true, bool $signed = false): string
{
    $n = cents($_POST[$key] ?? '', $signed);
    if ($positive && $n <= 0) {
        throw new DomainException('Informe um valor maior que zero.');
    }
    return dec($n);
}

function valid_date(mixed $d): bool
{
    if (!is_string($d)) {
        return false;
    }
    $x = DateTimeImmutable::createFromFormat('!Y-m-d', $d);
    return $x && $x->format('Y-m-d') === $d;
}

function dateValue(string $key, bool $optional = false, bool $future = false): ?string
{
    $v = $_POST[$key] ?? '';
    if ($v === '' && $optional) {
        return null;
    }
    if (!valid_date($v)) {
        throw new DomainException('Informe uma data válida.');
    }
    if (!$future && $v > date('Y-m-d')) {
        throw new DomainException(
            'Movimentações realizadas não podem ter data futura. Use a recorrência para lembrar o próximo lançamento.'
        );
    }
    return $v;
}

function formatDateBr(?string $d): string
{
    return $d ? date('d/m/Y', strtotime($d)) : '—';
}

function textValue(string $key, int $max, bool $required = true): ?string
{
    $v = trim(is_string($_POST[$key] ?? null) ? $_POST[$key] : '');
    if (($required && $v === '') || mb_strlen($v) > $max) {
        throw new DomainException(
            'Confira o campo ' . str_replace('_', ' ', $key) . ' (até ' . $max . ' caracteres).'
        );
    }
    return $v === '' ? null : $v;
}

function postId(string $key): int
{
    $id = filter_var($_POST[$key] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        throw new DomainException('Selecione uma opção válida.');
    }
    return $id;
}

function notify(string $message): void
{
    notificationFor(usuarioLogado(), $message);
}

function transaction(callable $fn): mixed
{
    db()->beginTransaction();
    try {
        // Serializa alterações financeiras do mesmo usuário inclusive em sessões diferentes.
        query('SELECT id_usuario FROM usuario WHERE id_usuario=? FOR UPDATE', [
            usuarioLogado()
        ])->fetch();
        $r = $fn();
        db()->commit();
        return $r;
    } catch (Throwable $ex) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $ex;
    }
}

function safeError(Throwable $ex): string
{
    error_log('SmartCash: ' . $ex->getMessage());
    return $ex instanceof DomainException
        ? $ex->getMessage()
        : 'Não foi possível concluir. Confira os dados e tente novamente. Nenhum valor foi alterado.';
}

function requireLogin(): void
{
    $user = usuarioLogado()
        ? one('SELECT id_usuario,nome,senha FROM usuario WHERE id_usuario=?', [usuarioLogado()])
        : null;
    $valid =
        $user &&
        (!isset($_SESSION['auth_fingerprint']) ||
            hash_equals($_SESSION['auth_fingerprint'], hash('sha256', $user['senha'])));
    if (!$valid) {
        unset($_SESSION['id_usuario']);
        if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/api/')) {
            jsonResponse(['erro' => 'Sessão expirada. Faça login novamente.'], 401);
        }
        redirect(url('login.php'));
    }
    $_SESSION['nome_usuario'] = $user['nome'];
    $_SESSION['auth_fingerprint'] = hash('sha256', $user['senha']);
}

function jsonResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
    exit();
}

function schemaReady(): bool
{
    foreach (
        [
            'sc_categoria_usuario',
            'sc_pagamento_cartao',
            'sc_pagamento_item',
            'sc_resgate',
            'sc_conta_meta',
            'sc_email_fila',
            'sc_aviso_chave'
        ] as $t
    ) {
        if (
            !scalar(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?',
                [$t]
            )
        ) {
            return false;
        }
    }
    if (
        scalar(
            'SELECT COUNT(*) FROM conta c LEFT JOIN sc_conta_meta m ON m.id_conta=c.id_conta WHERE m.id_conta IS NULL'
        )
    ) {
        return false;
    }
    return true;
}

function requireSchema(): void
{
    if (!schemaReady()) {
        if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/api/')) {
            jsonResponse(['erro' => 'Abra atualizar.php para concluir a atualização.'], 503);
        }
        redirect(url('atualizar.php'));
    }
}

function period(): array
{
    $mode = $_GET['modo_periodo'] ?? 'datas';
    if ($mode === 'mes') {
        $month = filter_var($_GET['mes'] ?? date('n'), FILTER_VALIDATE_INT);
        $year = filter_var($_GET['ano'] ?? date('Y'), FILTER_VALIDATE_INT);
        if (!$month || $month < 1 || $month > 12 || !$year || $year < 1900 || $year > 2100) {
            throw new DomainException('Selecione um mês e ano válidos.');
        }
        $i = sprintf('%04d-%02d-01', $year, $month);
        $f = (new DateTimeImmutable($i))->format('Y-m-t');
    } else {
        $i = $_GET['inicio'] ?? date('Y-m-01');
        $f = $_GET['fim'] ?? date('Y-m-t');
    }
    if (
        !valid_date($i) ||
        !valid_date($f) ||
        $i > $f ||
        strtotime($f) - strtotime($i) > 3660 * 86400
    ) {
        throw new DomainException('Escolha um período válido de até 10 anos.');
    }
    return [$i, $f];
}

function icon(string $name): string
{
    $paths = [
        'dashboard' => 'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',
        'contas' => 'M3 9h18 M4 20h16 M6 10v7 M10 10v7 M14 10v7 M18 10v7 M3 6l9-4 9 4v3H3z',
        'recebimentos' => 'M12 3v12 m-5-5 5 5 5-5 M5 17v4h14v-4',
        'despesas' => 'M12 15V3 m-5 5 5-5 5 5 M5 17v4h14v-4',
        'transferencias' => 'M3 7h17 m-4-4 4 4-4 4 M21 17H4 m4-4-4 4 4 4',
        'cartoes' => 'M3 5h18v14H3z M3 10h18 M6 15h4',
        'investimentos' => 'M3 20h18 M4 15l5-5 4 3 7-9 m-6 0h6v6',
        'objetivos' => 'M12 3a9 9 0 1 0 9 9 M12 7a5 5 0 1 0 5 5 M12 12l8-8 M16 4h4v4',
        'categorias' => 'M3 3h8v8H3z M15 3h6v6h-6z M3 15h6v6H3z M13 13h8v8h-8z',
        'relatorios' => 'M5 3h10l4 4v14H5z M14 3v5h5 M8 12h8 M8 16h6',
        'notificacoes' => 'M5 17h14l-2-3V9a5 5 0 0 0-10 0v5z M10 21h4',
        'usuario' => 'M12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M4 21v-3a8 6 0 0 1 16 0v3',
        'plus' => 'M12 5v14 M5 12h14',
        'menu' => 'M4 6h16 M4 12h16 M4 18h16',
        'logout' => 'M9 4H4v16h5 M9 12h12 m-4-4 4 4-4 4',
        'check' => 'M5 12l4 4L19 6',
        'search' => 'M10 3a7 7 0 1 0 0 14 7 7 0 0 0 0-14 M15 15l6 6',
        'eye' => 'M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12 M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6',
        'arrow' => 'M5 12h14 m-5-5 5 5-5 5',
        'calendar' => 'M4 5h16v16H4z M8 2v6 M16 2v6 M4 10h16',
        'download' => 'M12 3v12 m-4-4 4 4 4-4 M4 17v4h16v-4'
    ];
    return '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' .
        '<path d="' .
        ($paths[$name] ?? $paths['dashboard']) .
        '"/></svg>';
}

/** Versiona os assets pelo conteúdo para invalidar o cache após alterações. */
function assetUrl(string $path): string
{
    $file = __DIR__ . '/../' . ltrim($path, '/');
    $version = is_file($file) ? substr(hash_file('sha256', $file), 0, 12) : 'dark-2';

    return url($path) . '?v=' . $version;
}

require_once __DIR__ . '/accounting.php';
require_once __DIR__ . '/mail.php';
