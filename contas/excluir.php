<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

verify_csrf($_POST['csrf_token'] ?? null);

$idConta = filter_var(
    $_POST['id_conta'] ?? '',
    FILTER_VALIDATE_INT
);

if (!$idConta) {
    flash('error', 'Conta inválida.');
    redirect('index.php');
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT id_conta, saldo_atual
         FROM CONTA
         WHERE id_conta = :id AND id_usuario = :usuario
         FOR UPDATE'
    );
    $stmt->execute([
        'id' => $idConta,
        'usuario' => $id_usuario,
    ]);

    $conta = $stmt->fetch();

    if (!$conta) {
        throw new RuntimeException('Conta não encontrada.');
    }

    if ((float) $conta['saldo_atual'] != 0.0) {
        throw new RuntimeException(
            'A conta só pode ser excluída quando o saldo for zero.'
        );
    }

    $checks = [
        'RECEBIMENTO' => 'id_conta',
        'DESPESA' => 'id_conta',
        'TRANSFERENCIA' => 'id_conta_origem',
        'CARTAO' => 'id_conta',
        'INVESTIMENTO' => 'id_conta',
    ];

    foreach ($checks as $table => $column) {
        $sql = "SELECT 1 FROM {$table} WHERE {$column} = :id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $idConta]);

        if ($stmt->fetchColumn()) {
            throw new RuntimeException(
                'A conta possui registros vinculados e não pode ser excluída.'
            );
        }
    }

    // Verifica também transferências que chegam à conta.
    $stmt = $pdo->prepare(
        'SELECT 1 FROM TRANSFERENCIA
         WHERE id_conta_destino = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $idConta]);

    if ($stmt->fetchColumn()) {
        throw new RuntimeException(
            'A conta possui transferências vinculadas e não pode ser excluída.'
        );
    }

    $stmt = $pdo->prepare(
        'DELETE FROM CONTA
         WHERE id_conta = :id AND id_usuario = :usuario'
    );
    $stmt->execute([
        'id' => $idConta,
        'usuario' => $id_usuario,
    ]);

    $pdo->commit();

    flash('success', 'Conta excluída.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash('error', $e->getMessage());
}

redirect('index.php');
