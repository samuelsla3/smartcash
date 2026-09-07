<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
verify_csrf($_POST['csrf_token'] ?? null);

$id = filter_var(
    $_POST['id_recebimento'] ?? '',
    FILTER_VALIDATE_INT
);

if (!$id) {
    flash('error', 'Recebimento inválido.');
    redirect('index.php');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT *
         FROM RECEBIMENTO
         WHERE id_recebimento = :id AND id_usuario = :usuario
         FOR UPDATE'
    );
    $stmt->execute(['id' => $id, 'usuario' => $id_usuario]);
    $item = $stmt->fetch();

    if (!$item) {
        throw new RuntimeException('Recebimento não encontrado.');
    }

    $stmt = $pdo->prepare(
        'SELECT id_conta FROM CONTA
         WHERE id_conta = :conta AND id_usuario = :usuario
         FOR UPDATE'
    );
    $stmt->execute([
        'conta' => $item['id_conta'],
        'usuario' => $id_usuario,
    ]);

    if (!$stmt->fetch()) {
        throw new RuntimeException('Conta vinculada não encontrada.');
    }

    $stmt = $pdo->prepare(
        'UPDATE CONTA
         SET saldo_atual = saldo_atual - :valor
         WHERE id_conta = :conta AND id_usuario = :usuario'
    );
    $stmt->execute([
        'valor' => $item['valor'],
        'conta' => $item['id_conta'],
        'usuario' => $id_usuario,
    ]);

    $stmt = $pdo->prepare(
        'DELETE FROM RECEBIMENTO
         WHERE id_recebimento = :id AND id_usuario = :usuario'
    );
    $stmt->execute(['id' => $id, 'usuario' => $id_usuario]);

    $pdo->commit();

    flash('success', 'Recebimento excluído e saldo atualizado.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    flash('error', $e->getMessage());
}

redirect('index.php');
