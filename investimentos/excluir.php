<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$uid = usuarioLogado();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

verifyCsrf();

$id = intPost('id');

if (!$id) {
    redirect('index.php');
}

$pdo = db();

try {

    $pdo->beginTransaction();

    /*
     * Busca e bloqueia o investimento.
     */
    $stmt = $pdo->prepare(
        'SELECT
            id_investimento,
            id_conta,
            valor_aplicado
         FROM INVESTIMENTO
         WHERE id_investimento = :id
           AND id_usuario = :usuario
         FOR UPDATE'
    );

    $stmt->execute([
        'id' => $id,
        'usuario' => $uid
    ]);

    $investimento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$investimento) {
        throw new RuntimeException(
            'Investimento não encontrado.'
        );
    }

    /*
     * Bloqueia a conta relacionada.
     */
    $stmt = $pdo->prepare(
        'SELECT id_conta
         FROM CONTA
         WHERE id_conta = :conta
           AND id_usuario = :usuario
         FOR UPDATE'
    );

    $stmt->execute([
        'conta' => $investimento['id_conta'],
        'usuario' => $uid
    ]);

    if (!$stmt->fetch()) {
        throw new RuntimeException(
            'Conta vinculada ao investimento não encontrada.'
        );
    }

    /*
     * Devolve o valor aplicado para a conta.
     */
    $stmt = $pdo->prepare(
        'UPDATE CONTA
         SET saldo_atual = saldo_atual + :valor
         WHERE id_conta = :conta
           AND id_usuario = :usuario'
    );

    $stmt->execute([
        'valor' => $investimento['valor_aplicado'],
        'conta' => $investimento['id_conta'],
        'usuario' => $uid
    ]);

    /*
     * Exclui o investimento.
     */
    $stmt = $pdo->prepare(
        'DELETE FROM INVESTIMENTO
         WHERE id_investimento = :id
           AND id_usuario = :usuario'
    );

    $stmt->execute([
        'id' => $id,
        'usuario' => $uid
    ]);

    $pdo->commit();

    flash(
        'success',
        'Investimento excluído e valor devolvido à conta.'
    );

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash(
        'error',
        $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Não foi possível excluir o investimento.'
    );
}

redirect('index.php');