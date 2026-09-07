<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
verify_csrf($_POST['csrf_token'] ?? null);

$origem = filter_var($_POST['id_conta_origem'] ?? '', FILTER_VALIDATE_INT);
$destino = filter_var($_POST['id_conta_destino'] ?? '', FILTER_VALIDATE_INT);
$valor = positive_amount($_POST['valor'] ?? null);
$data = trim($_POST['data_transferencia'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');

if (
    !$origem ||
    !$destino ||
    $origem === $destino ||
    $valor === null ||
    !valid_date($data)
) {
    flash('error', 'Informe origem, destino diferentes, valor e data válidos.');
    redirect('cadastrar.php');
}

try {
    $pdo->beginTransaction();

    // Sempre bloqueia as contas em ordem crescente para reduzir
    // possibilidade de deadlock quando houver operações concorrentes.
    $ids = [$origem, $destino];
    sort($ids);

    $contasBloqueadas = [];

    foreach ($ids as $idConta) {
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
            throw new RuntimeException('Uma das contas não pertence ao usuário.');
        }

        $contasBloqueadas[(int)$idConta] = $conta;
    }

    if ((float)$contasBloqueadas[$origem]['saldo_atual'] < $valor) {
        throw new RuntimeException('Saldo insuficiente na conta de origem.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO TRANSFERENCIA
            (id_usuario, id_conta_origem, id_conta_destino,
             valor, data_transferencia, descricao)
         VALUES
            (:usuario, :origem, :destino, :valor, :data, :descricao)'
    );
    $stmt->execute([
        'usuario' => $id_usuario,
        'origem' => $origem,
        'destino' => $destino,
        'valor' => $valor,
        'data' => $data,
        'descricao' => $descricao !== '' ? $descricao : null,
    ]);

    $stmt = $pdo->prepare(
        'UPDATE CONTA
         SET saldo_atual = saldo_atual - :valor
         WHERE id_conta = :id AND id_usuario = :usuario'
    );
    $stmt->execute([
        'valor' => $valor,
        'id' => $origem,
        'usuario' => $id_usuario,
    ]);

    $stmt = $pdo->prepare(
        'UPDATE CONTA
         SET saldo_atual = saldo_atual + :valor
         WHERE id_conta = :id AND id_usuario = :usuario'
    );
    $stmt->execute([
        'valor' => $valor,
        'id' => $destino,
        'usuario' => $id_usuario,
    ]);

    $pdo->commit();

    flash('success', 'Transferência realizada. Ela não foi registrada como despesa.');
    redirect('index.php');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    flash('error', $e->getMessage());
    redirect('cadastrar.php');
}
