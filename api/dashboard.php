<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$userId = usuarioLogado();
$pdo = db();

$inicio = $_GET['inicio'] ?? date('Y-m-01');
$fim = $_GET['fim'] ?? date('Y-m-t');

$inicioDt = DateTime::createFromFormat('Y-m-d', (string)$inicio);
$fimDt = DateTime::createFromFormat('Y-m-d', (string)$fim);
if (!$inicioDt || !$fimDt || $inicioDt->format('Y-m-d') !== $inicio || $fimDt->format('Y-m-d') !== $fim || $inicio > $fim) {
    jsonResponse(['erro' => 'Período inválido.'], 422);
}

$stmt = $pdo->prepare('SELECT COALESCE(SUM(saldo_atual),0) FROM CONTA WHERE id_usuario = :id_usuario');
$stmt->execute(['id_usuario' => $userId]);
$saldoTotal = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COALESCE(SUM(valor),0) FROM RECEBIMENTO WHERE id_usuario = :id_usuario AND data_recebimento BETWEEN :inicio AND :fim');
$stmt->execute(['id_usuario' => $userId, 'inicio' => $inicio, 'fim' => $fim]);
$receitas = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COALESCE(SUM(valor),0) FROM DESPESA WHERE id_usuario = :id_usuario AND data BETWEEN :inicio AND :fim');
$stmt->execute(['id_usuario' => $userId, 'inicio' => $inicio, 'fim' => $fim]);
$despesas = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    'SELECT COALESCE(c.nome, \'Sem categoria\') AS categoria, SUM(d.valor) AS total
     FROM DESPESA d
     LEFT JOIN CATEGORIA c ON c.id_categoria = d.id_categoria
     WHERE d.id_usuario = :id_usuario AND d.data BETWEEN :inicio AND :fim
     GROUP BY c.id_categoria, c.nome
     ORDER BY total DESC'
);
$stmt->execute(['id_usuario' => $userId, 'inicio' => $inicio, 'fim' => $fim]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    'SELECT
        d.id_despesa AS id,
        \'despesa\' AS tipo,
        d.descricao,
        d.valor,
        d.data AS data_movimentacao
     FROM DESPESA d
     WHERE d.id_usuario = :usuario_despesa

     UNION ALL

     SELECT
        r.id_recebimento AS id,
        \'recebimento\' AS tipo,
        r.descricao,
        r.valor,
        r.data_recebimento AS data_movimentacao
     FROM RECEBIMENTO r
     WHERE r.id_usuario = :usuario_recebimento

     ORDER BY data_movimentacao DESC
     LIMIT 10'
);

$stmt->execute([
    'usuario_despesa' => $userId,
    'usuario_recebimento' => $userId
]);

$movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    'SELECT ca.id_cartao, ca.nome_cartao, ca.limite_total, ca.limite_disponivel,
            b.nome AS banco
     FROM CARTAO ca
     LEFT JOIN BANCO b ON b.id_banco = ca.id_banco
     WHERE ca.id_usuario = :id_usuario
     ORDER BY ca.nome_cartao'
);
$stmt->execute(['id_usuario' => $userId]);
$cartoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    'SELECT i.id_investimento, i.nome, i.valor_aplicado, i.rendimento,
            (i.valor_aplicado + i.rendimento) AS valor_atualizado
     FROM INVESTIMENTO i
     WHERE i.id_usuario = :id_usuario
     ORDER BY i.data_aplicacao DESC
     LIMIT 10'
);
$stmt->execute(['id_usuario' => $userId]);
$investimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    'SELECT id_objetivo, descricao, valor_objetivo, valor_atual, data_limite, status,
            CASE WHEN valor_objetivo > 0 THEN LEAST(100, (valor_atual / valor_objetivo) * 100) ELSE 0 END AS progresso
     FROM OBJETIVO_FINANCEIRO
     WHERE id_usuario = :id_usuario
     ORDER BY data_limite IS NULL, data_limite ASC
     LIMIT 10'
);
$stmt->execute(['id_usuario' => $userId]);
$objetivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

jsonResponse([
    'periodo' => ['inicio' => $inicio, 'fim' => $fim],
    'saldo_total' => $saldoTotal,
    'receitas' => $receitas,
    'despesas' => $despesas,
    'resultado' => $receitas - $despesas,
    'categorias' => $categorias,
    'movimentacoes' => $movimentacoes,
    'cartoes' => $cartoes,
    'investimentos' => $investimentos,
    'objetivos' => $objetivos
]);
