<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
verify_csrf($_POST['csrf_token'] ?? null);

$acao = $_POST['acao'] ?? '';
$descricao = trim($_POST['descricao'] ?? '');
$valor = positive_amount($_POST['valor'] ?? null);
$data = trim($_POST['data'] ?? '');
$idTipo = filter_var($_POST['id_tipo_despesa'] ?? '', FILTER_VALIDATE_INT);
$idCategoria = filter_var($_POST['id_categoria'] ?? '', FILTER_VALIDATE_INT);
$forma = $_POST['forma_pagamento'] ?? '';
$idConta = filter_var($_POST['id_conta'] ?? '', FILTER_VALIDATE_INT);
$idCartao = filter_var($_POST['id_cartao'] ?? '', FILTER_VALIDATE_INT);
$recorrente = (int)($_POST['recorrente'] ?? 0) === 1;
$frequencia = $_POST['frequencia'] ?? null;
$proxima = $_POST['proxima_cobranca'] ?? null;

$back = $acao === 'editar'
    ? 'editar.php?id=' . (int)($_POST['id_despesa'] ?? 0)
    : 'cadastrar.php';

if (
    $descricao === '' ||
    $valor === null ||
    !valid_date($data) ||
    !$idTipo ||
    !$idCategoria ||
    !in_array($forma, ['conta', 'cartao'], true)
) {
    flash('error', 'Preencha corretamente os campos obrigatórios.');
    redirect($back);
}

if ($forma === 'conta' && !$idConta) {
    flash('error', 'Selecione a conta de pagamento.');
    redirect($back);
}

if ($forma === 'cartao' && !$idCartao) {
    flash('error', 'Selecione o cartão.');
    redirect($back);
}

try {
    [$recorrenteDb, $freqDb, $proximaDb] =
        recurring_data($recorrente, $frequencia, $proxima);

    $pdo->beginTransaction();

    if ($acao === 'criar') {
        if ($forma === 'conta') {
            // Verifica propriedade e saldo, bloqueando a conta.
            $stmt = $pdo->prepare(
                'SELECT saldo_atual
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
                throw new RuntimeException('Conta inválida.');
            }

            if ((float)$conta['saldo_atual'] < $valor) {
                throw new RuntimeException(
                    'Saldo insuficiente para realizar esta despesa.'
                );
            }
        } else {
            // Verifica propriedade e limite, bloqueando o cartão.
            $stmt = $pdo->prepare(
                'SELECT limite_disponivel
                 FROM CARTAO
                 WHERE id_cartao = :id AND id_usuario = :usuario
                 FOR UPDATE'
            );
            $stmt->execute([
                'id' => $idCartao,
                'usuario' => $id_usuario,
            ]);
            $cartao = $stmt->fetch();

            if (!$cartao) {
                throw new RuntimeException('Cartão inválido.');
            }

            if ((float)$cartao['limite_disponivel'] < $valor) {
                throw new RuntimeException(
                    'Limite disponível insuficiente para esta despesa.'
                );
            }
        }

        $stmt = $pdo->prepare(
            'INSERT INTO DESPESA
                (id_usuario, id_conta, id_cartao, id_tipo_despesa,
                 id_categoria, descricao, valor, `data`, recorrente,
                 frequencia, proxima_cobranca)
             VALUES
                (:usuario, :conta, :cartao, :tipo, :categoria, :descricao,
                 :valor, :data, :recorrente, :frequencia, :proxima)'
        );

        $stmt->execute([
            'usuario' => $id_usuario,
            'conta' => $forma === 'conta' ? $idConta : null,
            'cartao' => $forma === 'cartao' ? $idCartao : null,
            'tipo' => $idTipo,
            'categoria' => $idCategoria,
            'descricao' => $descricao,
            'valor' => $valor,
            'data' => $data,
            'recorrente' => $recorrenteDb,
            'frequencia' => $freqDb,
            'proxima' => $proximaDb,
        ]);

        if ($forma === 'conta') {
            $stmt = $pdo->prepare(
                'UPDATE CONTA
                 SET saldo_atual = saldo_atual - :valor
                 WHERE id_conta = :id AND id_usuario = :usuario'
            );
            $stmt->execute([
                'valor' => $valor,
                'id' => $idConta,
                'usuario' => $id_usuario,
            ]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE CARTAO
                 SET limite_disponivel = limite_disponivel - :valor
                 WHERE id_cartao = :id AND id_usuario = :usuario'
            );
            $stmt->execute([
                'valor' => $valor,
                'id' => $idCartao,
                'usuario' => $id_usuario,
            ]);
        }

        $pdo->commit();
        flash('success', 'Despesa cadastrada e saldo/limite atualizado.');
        redirect('index.php');
    }

    if ($acao === 'editar') {
        $id = filter_var($_POST['id_despesa'] ?? '', FILTER_VALIDATE_INT);

        if (!$id) {
            throw new RuntimeException('Despesa inválida.');
        }

        $stmt = $pdo->prepare(
            'SELECT *
             FROM DESPESA
             WHERE id_despesa = :id AND id_usuario = :usuario
             FOR UPDATE'
        );
        $stmt->execute(['id' => $id, 'usuario' => $id_usuario]);
        $old = $stmt->fetch();

        if (!$old) {
            throw new RuntimeException('Despesa não encontrada.');
        }

        // Bloqueia os recursos antigos e novos em ordem determinística.
        $oldResource = $old['id_conta']
            ? 'conta:' . (int)$old['id_conta']
            : 'cartao:' . (int)$old['id_cartao'];

        $newResource = $forma === 'conta'
            ? 'conta:' . (int)$idConta
            : 'cartao:' . (int)$idCartao;

        $resources = array_unique([$oldResource, $newResource]);
        sort($resources);

        foreach ($resources as $resource) {
            [$type, $resourceId] = explode(':', $resource);

            if ($type === 'conta') {
                $stmt = $pdo->prepare(
                    'SELECT id_conta, saldo_atual
                     FROM CONTA
                     WHERE id_conta = :id AND id_usuario = :usuario
                     FOR UPDATE'
                );
            } else {
                $stmt = $pdo->prepare(
                    'SELECT id_cartao, limite_disponivel
                     FROM CARTAO
                     WHERE id_cartao = :id AND id_usuario = :usuario
                     FOR UPDATE'
                );
            }

            $stmt->execute([
                'id' => (int)$resourceId,
                'usuario' => $id_usuario,
            ]);

            if (!$stmt->fetch()) {
                throw new RuntimeException(
                    ucfirst($type) . ' vinculado não encontrado.'
                );
            }
        }

        // Desfaz o efeito financeiro anterior.
        if ($old['id_conta']) {
            $stmt = $pdo->prepare(
                'UPDATE CONTA
                 SET saldo_atual = saldo_atual + :valor
                 WHERE id_conta = :id AND id_usuario = :usuario'
            );
            $stmt->execute([
                'valor' => $old['valor'],
                'id' => $old['id_conta'],
                'usuario' => $id_usuario,
            ]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE CARTAO
                 SET limite_disponivel = limite_disponivel + :valor
                 WHERE id_cartao = :id AND id_usuario = :usuario'
            );
            $stmt->execute([
                'valor' => $old['valor'],
                'id' => $old['id_cartao'],
                'usuario' => $id_usuario,
            ]);
        }

        // Verifica se o novo lançamento cabe no recurso escolhido.
        if ($forma === 'conta') {
            $stmt = $pdo->prepare(
                'SELECT saldo_atual FROM CONTA
                 WHERE id_conta = :id AND id_usuario = :usuario
                 FOR UPDATE'
            );
            $stmt->execute(['id' => $idConta, 'usuario' => $id_usuario]);
            $conta = $stmt->fetch();

            if (!$conta || (float)$conta['saldo_atual'] < $valor) {
                throw new RuntimeException('Saldo insuficiente para a nova despesa.');
            }
        } else {
            $stmt = $pdo->prepare(
                'SELECT limite_disponivel FROM CARTAO
                 WHERE id_cartao = :id AND id_usuario = :usuario
                 FOR UPDATE'
            );
            $stmt->execute(['id' => $idCartao, 'usuario' => $id_usuario]);
            $cartao = $stmt->fetch();

            if (!$cartao || (float)$cartao['limite_disponivel'] < $valor) {
                throw new RuntimeException(
                    'Limite insuficiente para a nova despesa.'
                );
            }
        }

        $stmt = $pdo->prepare(
            'UPDATE DESPESA
             SET id_conta = :conta,
                 id_cartao = :cartao,
                 id_tipo_despesa = :tipo,
                 id_categoria = :categoria,
                 descricao = :descricao,
                 valor = :valor,
                 `data` = :data,
                 recorrente = :recorrente,
                 frequencia = :frequencia,
                 proxima_cobranca = :proxima
             WHERE id_despesa = :id AND id_usuario = :usuario'
        );
        $stmt->execute([
            'conta' => $forma === 'conta' ? $idConta : null,
            'cartao' => $forma === 'cartao' ? $idCartao : null,
            'tipo' => $idTipo,
            'categoria' => $idCategoria,
            'descricao' => $descricao,
            'valor' => $valor,
            'data' => $data,
            'recorrente' => $recorrenteDb,
            'frequencia' => $freqDb,
            'proxima' => $proximaDb,
            'id' => $id,
            'usuario' => $id_usuario,
        ]);

        // Aplica o novo efeito financeiro.
        if ($forma === 'conta') {
            $stmt = $pdo->prepare(
                'UPDATE CONTA
                 SET saldo_atual = saldo_atual - :valor
                 WHERE id_conta = :id AND id_usuario = :usuario'
            );
            $stmt->execute([
                'valor' => $valor,
                'id' => $idConta,
                'usuario' => $id_usuario,
            ]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE CARTAO
                 SET limite_disponivel = limite_disponivel - :valor
                 WHERE id_cartao = :id AND id_usuario = :usuario'
            );
            $stmt->execute([
                'valor' => $valor,
                'id' => $idCartao,
                'usuario' => $id_usuario,
            ]);
        }

        $pdo->commit();
        flash('success', 'Despesa atualizada corretamente.');
        redirect('index.php');
    }

    throw new RuntimeException('Ação inválida.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    flash('error', $e->getMessage());
    redirect($back);
}
