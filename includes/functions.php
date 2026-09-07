<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* SAÍDA / HTML */

function e(mixed $value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


/* USUÁRIO / AUTENTICAÇÃO*/

function usuarioLogado(): int
{
    return (int) ($_SESSION['id_usuario'] ?? 0);
}


/* BANCO DE DADOS*/

function db(): PDO
{
    global $pdo;

    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException(
            'A conexão PDO $pdo não foi disponibilizada por config/database.php.'
        );
    }

    return $pdo;
}


/* REDIRECIONAMENTO*/

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}


/* MENSAGENS FLASH*/

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function getFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;

    unset($_SESSION['flash']);

    return $flash;
}

/*
 * Compatibilidade com o núcleo antigo.
 */
function get_flash(): ?array
{
    return getFlash();
}


/* CSRF*/

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/*
 * Compatibilidade com o núcleo:
 * csrf_token()
 */
function csrf_token(): string
{
    return csrfToken();
}


function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit('Token de segurança inválido.');
    }
}

/*
 * Compatibilidade com o núcleo:
 * verify_csrf()
 */
function verify_csrf(?string $token = null): void
{
    if ($token === null) {
        verifyCsrf();
        return;
    }

    if (
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit('Token de segurança inválido.');
    }
}


/* VALORES MONETÁRIOS*/

function money(float|int|string|null $value): string
{
    return 'R$ ' . number_format(
        (float)$value,
        2,
        ',',
        '.'
    );
}



function decimalPost(
    string $key,
    bool $allowZero = true
): ?float {

    $value = trim((string)($_POST[$key] ?? ''));

    if ($value === '') {
        return null;
    }

    if (str_contains($value, ',')) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    }

    if (!is_numeric($value)) {
        return null;
    }

    $number = (float)$value;

    if (!$allowZero && $number <= 0) {
        return null;
    }

    if ($allowZero && $number < 0) {
        return null;
    }

    return $number;
}



function money_input(?string $value): ?float
{
    if ($value === null) {
        return null;
    }

    $value = trim($value);

    if ($value === '') {
        return null;
    }

    if (str_contains($value, ',')) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    }

    if (!is_numeric($value)) {
        return null;
    }

    $number = (float)$value;

    return $number >= 0 ? $number : null;
}


/*
 * Retorna somente valores positivos.
 */
function positive_amount(?string $value): ?float
{
    $number = money_input($value);

    return ($number !== null && $number > 0)
        ? $number
        : null;
}


/* INTEIROS / IDs */

function intPost(string $key): ?int
{
    $value = $_POST[$key] ?? null;

    return (
        is_numeric($value) &&
        (int)$value > 0
    )
        ? (int)$value
        : null;
}


/* DATAS*/

function datePost(string $key): ?string
{
    $value = trim((string)($_POST[$key] ?? ''));

    if ($value === '') {
        return null;
    }

    $date = DateTime::createFromFormat(
        'Y-m-d',
        $value
    );

    return (
        $date &&
        $date->format('Y-m-d') === $value
    )
        ? $value
        : null;
}


/*
 * Compatibilidade com o núcleo:
 * valid_date()
 */
function valid_date(string $date): bool
{
    $d = DateTime::createFromFormat(
        'Y-m-d',
        $date
    );

    return (
        $d !== false &&
        $d->format('Y-m-d') === $date
    );
}


/*
 * Formata data para padrão brasileiro.
 */
function formatDateBr(?string $date): string
{
    if (!$date) {
        return '-';
    }

    $dt = DateTime::createFromFormat(
        'Y-m-d',
        substr($date, 0, 10)
    );

    return $dt
        ? $dt->format('d/m/Y')
        : e($date);
}


/* RECORRÊNCIA */

function recurring_data(
    bool $recorrente,
    ?string $frequencia,
    ?string $proximaData
): array {

    if (!$recorrente) {
        return [0, null, null];
    }

    $frequenciasPermitidas = [
        'diaria',
        'semanal',
        'mensal',
        'anual'
    ];

    if (
        !in_array(
            $frequencia,
            $frequenciasPermitidas,
            true
        ) ||
        !$proximaData ||
        !valid_date($proximaData)
    ) {
        throw new InvalidArgumentException(
            'Informe uma frequência e uma próxima data válidas.'
        );
    }

    return [
        1,
        $frequencia,
        $proximaData
    ];
}


/* CATEGORIAS*/

/**
 * Monta árvore hierárquica das categorias.
 *
 * CATEGORIA é global no sistema, pois o schema
 * não possui id_usuario.
 */
function buildCategoryTree(
    array $categories,
    ?int $parentId = null,
    int $level = 0
): array {

    $tree = [];

    foreach ($categories as $category) {

        $currentParent =
            $category['id_categoria_pai'] !== null
                ? (int)$category['id_categoria_pai']
                : null;

        if ($currentParent === $parentId) {

            $category['nivel'] = $level;

            $tree[] = $category;

            $tree = array_merge(
                $tree,
                buildCategoryTree(
                    $categories,
                    (int)$category['id_categoria'],
                    $level + 1
                )
            );
        }
    }

    return $tree;
}


/**
 * Gera <option> para seleção de categorias.
 */
function categoryOptions(
    array $categories,
    ?int $selected = null,
    ?int $excludeId = null
): string {

    $tree = buildCategoryTree($categories);

    $html = '';

    foreach ($tree as $category) {

        $id = (int)$category['id_categoria'];

        if (
            $excludeId !== null &&
            $id === $excludeId
        ) {
            continue;
        }

        $prefix = str_repeat(
            '— ',
            (int)$category['nivel']
        );

        $isSelected =
            $selected === $id
                ? ' selected'
                : '';

        $html .=
            '<option value="' .
            $id .
            '"' .
            $isSelected .
            '>' .
            e(
                $prefix .
                $category['nome']
            ) .
            '</option>';
    }

    return $html;
}


/**
 * Busca todas as categorias.
 *
 * Não filtra por id_usuario porque
 * CATEGORIA não possui essa coluna.
 */
function getAllCategories(): array
{
    return db()
        ->query(
            'SELECT
                id_categoria,
                id_categoria_pai,
                nome
             FROM CATEGORIA
             ORDER BY nome'
        )
        ->fetchAll(PDO::FETCH_ASSOC);
}


/* RESPOSTAS JSON*/

function jsonResponse(
    array $data,
    int $status = 200
): never {

    http_response_code($status);

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}