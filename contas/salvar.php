<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

verify_csrf($_POST['csrf_token'] ?? null);

$acao = $_POST['acao'] ?? '';
$nome = trim($_POST['nome_conta'] ?? '');
$idBanco = filter_var($_POST['id_banco'] ?? '', FILTER_VALIDATE_INT);
$idTipo = filter_var($_POST['id_tipo_conta'] ?? '', FILTER_VALIDATE_INT);
$agencia = trim($_POST['agencia'] ?? '');
$numConta = trim($_POST['num_conta'] ?? '');

if ($nome === '' || !$idBanco || !$idTipo || $agencia === '' || $numConta === '') {
    flash('error', 'Preencha todos os campos obrigatórios.');
    redirect($acao === 'editar'
        ? 'editar.php?id=' . (int) ($_POST['id_conta'] ?? 0)
        : 'cadastrar.php');
}

try {
    if ($acao === 'criar') {
        $saldo = money_input($_POST['saldo_atual'] ?? null);

        if ($saldo === null) {
            flash('error', 'Saldo inicial inválido.');
            redirect('cadastrar.php');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO CONTA
                (id_usuario, id_banco, id_tipo_conta, nome_conta,
                 num_conta, agencia, saldo_atual)
             VALUES
                (:usuario, :banco, :tipo, :nome, :numero, :agencia, :saldo)'
        );

        $stmt->execute([
            'usuario' => $id_usuario,
            'banco' => $idBanco,
            'tipo' => $idTipo,
            'nome' => $nome,
            'numero' => $numConta,
            'agencia' => $agencia,
            'saldo' => $saldo,
        ]);

        flash('success', 'Conta cadastrada.');
        redirect('index.php');
    }

    if ($acao === 'editar') {
        $idConta = filter_var(
            $_POST['id_conta'] ?? '',
            FILTER_VALIDATE_INT
        );

        if (!$idConta) {
            flash('error', 'Conta inválida.');
            redirect('index.php');
        }

        $stmt = $pdo->prepare(
            'UPDATE CONTA
             SET id_banco = :banco,
                 id_tipo_conta = :tipo,
                 nome_conta = :nome,
                 num_conta = :numero,
                 agencia = :agencia
             WHERE id_conta = :id AND id_usuario = :usuario'
        );

        $stmt->execute([
            'banco' => $idBanco,
            'tipo' => $idTipo,
            'nome' => $nome,
            'numero' => $numConta,
            'agencia' => $agencia,
            'id' => $idConta,
            'usuario' => $id_usuario,
        ]);

        flash('success', 'Conta atualizada.');
        redirect('index.php');
    }

    flash('error', 'Ação inválida.');
    redirect('index.php');
} catch (PDOException $e) {
    flash('error', 'Não foi possível salvar a conta.');
    redirect($acao === 'editar'
        ? 'editar.php?id=' . (int) ($_POST['id_conta'] ?? 0)
        : 'cadastrar.php');
}
