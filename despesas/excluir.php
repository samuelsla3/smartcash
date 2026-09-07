<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
verify_csrf($_POST['csrf_token'] ?? null);

$id = filter_var($_POST['id_despesa'] ?? '', FILTER_VALIDATE_INT);

if (!$id) {
    flash('error', 'Despesa inválida.');
    redirect('index.php');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT *
         FROM DESPESA
         WHERE id_despesa = :id AND id_usuario = :usuario
         FOR UPDATE'
    );
    $stmt->execute(['id' => $id, 'usuario' => $id_usuario]);
    $item = $stmt->fetch();

    if (!$item) {
        throw new RuntimeException('Despesa não encontrada.');
    }

    if ($item['id_conta']) {
        $stmt = $pdo->prepare(
            'SELECT id_conta FROM CONTA
             WHERE id_conta = :id AND id_usuario = :usuario
             FOR UPDATE'
        );
        $stmt->execute([
            'id' => $item['id_conta'],
            'usuario' => $id_usuario,
        ]);

        if (!$stmt->fetch()) {
            throw new RuntimeException('Conta vinculada não encontrada.');
        }

        $stmt = $pdo->prepare(
            'UPDATE CONTA
             SET saldo_atual = saldo_atual + :valor
             WHERE id_conta = :id AND id_usuario = :usuario'
        );
        $stmt->execute([
            'valor' => $item['valor'],
            'id' => $item['id_conta'],
            'usuario' => $id_usuario,
        ]);
    } else {
        $stmt = $pdo->prepare(
            'SELECT id_cartao FROM CARTAO
             WHERE id_cartao = :id AND id_usuario = :usuario
             FOR UPDATE'
        );
        $stmt->execute([
            'id' => $item['id_cartao'],
            'usuario' => $id_usuario,
        ]);

        if (!$stmt->fetch()) {
            throw new RuntimeException('Cartão vinculado não encontrado.');
        }

        $stmt = $pdo->prepare(
            'UPDATE CARTAO
             SET limite_disponivel = limite_disponivel + :valor
             WHERE id_cartao = :id AND id_usuario = :usuario'
        );
        $stmt->execute([
            'valor' => $item['valor'],
            'id' => $item['id_cartao'],
            'usuario' => $id_usuario,
        ]);
    }

    $stmt = $pdo->prepare(
        'DELETE FROM DESPESA
         WHERE id_despesa = :id AND id_usuario = :usuario'
    );
    $stmt->execute(['id' => $id, 'usuario' => $id_usuario]);

    $pdo->commit();

    flash('success', 'Despesa excluída e saldo/limite restaurado.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    flash('error', $e->getMessage());
}

redirect('index.php');
