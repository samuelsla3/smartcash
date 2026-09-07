<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
verify_csrf($_POST['csrf_token'] ?? null);

$acao = $_POST['acao'] ?? '';
$descricao = trim($_POST['descricao'] ?? '');
$valor = positive_amount($_POST['valor'] ?? null);
$data = trim($_POST['data_recebimento'] ?? '');
$idConta = filter_var($_POST['id_conta'] ?? '', FILTER_VALIDATE_INT);
$idTipo = filter_var($_POST['id_tipo_recebimento'] ?? '', FILTER_VALIDATE_INT);
$recorrente = (int)($_POST['recorrente'] ?? 0) === 1;
$frequencia = $_POST['frequencia'] ?? null;
$proximo = $_POST['proximo_recebimento'] ?? null;

$back = $acao === 'editar'
    ? 'editar.php?id=' . (int)($_POST['id_recebimento'] ?? 0)
    : 'cadastrar.php';

if ($descricao === '' || $valor === null || !$idConta || !$idTipo || !valid_date($data)) {
    flash('error', 'Preencha corretamente os campos obrigatórios.');
    redirect($back);
}

try {
    [$recorrenteDb, $freqDb, $proximoDb] =
        recurring_data($recorrente, $frequencia, $proximo);

    $pdo->beginTransaction();

    if ($acao === 'criar') {
        // Garante que a conta pertence ao usuário e bloqueia sua linha.
        $stmt = $pdo->prepare(
            'SELECT id_conta FROM CONTA
             WHERE id_conta = :conta AND id_usuario = :usuario
             FOR UPDATE'
        );
        $stmt->execute(['conta' => $idConta, 'usuario' => $id_usuario]);

        if (!$stmt->fetch()) {
            throw new RuntimeException('Conta inválida.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO RECEBIMENTO
                (id_usuario, id_conta, id_tipo_recebimento, descricao, valor,
                 data_recebimento, recorrente, frequencia, proximo_recebimento)
             VALUES
                (:usuario, :conta, :tipo, :descricao, :valor, :data,
                 :recorrente, :frequencia, :proximo)'
        );

        $stmt->execute([
            'usuario' => $id_usuario,
            'conta' => $idConta,
            'tipo' => $idTipo,
            'descricao' => $descricao,
            'valor' => $valor,
            'data' => $data,
            'recorrente' => $recorrenteDb,
            'frequencia' => $freqDb,
            'proximo' => $proximoDb,
        ]);

        $stmt = $pdo->prepare(
            'UPDATE CONTA
             SET saldo_atual = saldo_atual + :valor
             WHERE id_conta = :conta AND id_usuario = :usuario'
        );
        $stmt->execute([
            'valor' => $valor,
            'conta' => $idConta,
            'usuario' => $id_usuario,
        ]);

        $pdo->commit();
        flash('success', 'Recebimento cadastrado e saldo atualizado.');
        redirect('index.php');
    }

    if ($acao === 'editar') {
        $id = filter_var(
            $_POST['id_recebimento'] ?? '',
            FILTER_VALIDATE_INT
        );

        if (!$id) {
            throw new RuntimeException('Recebimento inválido.');
        }

        $stmt = $pdo->prepare(
            'SELECT *
             FROM RECEBIMENTO
             WHERE id_recebimento = :id AND id_usuario = :usuario
             FOR UPDATE'
        );
        $stmt->execute(['id' => $id, 'usuario' => $id_usuario]);
        $old = $stmt->fetch();

        if (!$old) {
            throw new RuntimeException('Recebimento não encontrado.');
        }

        // Bloqueia as duas contas quando necessário.
        $idsContas = array_unique([(int)$old['id_conta'], (int)$idConta]);
        sort($idsContas);

        foreach ($idsContas as $contaId) {
            $stmt = $pdo->prepare(
                'SELECT id_conta FROM CONTA
                 WHERE id_conta = :conta AND id_usuario = :usuario
                 FOR UPDATE'
            );
            $stmt->execute([
                'conta' => $contaId,
                'usuario' => $id_usuario,
            ]);

            if (!$stmt->fetch()) {
                throw new RuntimeException('Conta inválida.');
            }
        }

        // Desfaz o efeito antigo.
        $stmt = $pdo->prepare(
            'UPDATE CONTA
             SET saldo_atual = saldo_atual - :valor
             WHERE id_conta = :conta AND id_usuario = :usuario'
        );
        $stmt->execute([
            'valor' => $old['valor'],
            'conta' => $old['id_conta'],
            'usuario' => $id_usuario,
        ]);

        // Grava o novo recebimento.
        $stmt = $pdo->prepare(
            'UPDATE RECEBIMENTO
             SET id_conta = :conta,
                 id_tipo_recebimento = :tipo,
                 descricao = :descricao,
                 valor = :valor,
                 data_recebimento = :data,
                 recorrente = :recorrente,
                 frequencia = :frequencia,
                 proximo_recebimento = :proximo
             WHERE id_recebimento = :id AND id_usuario = :usuario'
        );
        $stmt->execute([
            'conta' => $idConta,
            'tipo' => $idTipo,
            'descricao' => $descricao,
            'valor' => $valor,
            'data' => $data,
            'recorrente' => $recorrenteDb,
            'frequencia' => $freqDb,
            'proximo' => $proximoDb,
            'id' => $id,
            'usuario' => $id_usuario,
        ]);

        // Aplica o novo efeito.
        $stmt = $pdo->prepare(
            'UPDATE CONTA
             SET saldo_atual = saldo_atual + :valor
             WHERE id_conta = :conta AND id_usuario = :usuario'
        );
        $stmt->execute([
            'valor' => $valor,
            'conta' => $idConta,
            'usuario' => $id_usuario,
        ]);

        $pdo->commit();
        flash('success', 'Recebimento atualizado e saldo recalculado.');
        redirect('index.php');
    }

    throw new RuntimeException('Ação inválida.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    flash('error', $e->getMessage());
    redirect($back);
}
