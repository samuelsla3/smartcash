<?php

declare(strict_types=1);

/** Movimentos de caixa: compras no cartão entram somente na quitação. */
function accountCashEvents(int $account): array
{
    return rows(
        "SELECT data_recebimento AS data, valor AS delta FROM recebimento WHERE id_conta=?
        UNION ALL SELECT data, -valor FROM despesa WHERE id_conta=?
        UNION ALL SELECT data_transferencia, -valor FROM transferencia WHERE id_conta_origem=?
        UNION ALL SELECT data_transferencia, valor FROM transferencia WHERE id_conta_destino=?
        UNION ALL SELECT data_aplicacao, -valor_aplicado FROM investimento WHERE id_conta=?
        UNION ALL SELECT data, -valor FROM sc_pagamento_cartao WHERE id_conta=?
        UNION ALL SELECT data_aplicacao, -principal FROM sc_resgate WHERE id_conta=?
        UNION ALL SELECT data, principal+rendimento FROM sc_resgate WHERE id_conta=?",
        array_fill(0, 8, $account)
    );
}

/** Registra a posição inicial residual uma única vez, preservando os saldos atuais. */
function seedAccountMeta(): void
{
    $users = rows(
        'SELECT DISTINCT c.id_usuario FROM conta c LEFT JOIN sc_conta_meta m ON m.id_conta=c.id_conta WHERE m.id_conta IS NULL ORDER BY c.id_usuario'
    );
    foreach ($users as $user) {
        db()->beginTransaction();
        try {
            $uid = (int) $user['id_usuario'];
            $owner = one('SELECT data_cadastro FROM usuario WHERE id_usuario=? FOR UPDATE', [$uid]);
            $accounts = rows(
                "SELECT c.* FROM conta c LEFT JOIN sc_conta_meta m ON m.id_conta=c.id_conta
                WHERE c.id_usuario=? AND m.id_conta IS NULL ORDER BY (c.nome_conta LIKE '%principal%') DESC,c.saldo_atual DESC,c.id_conta",
                [$uid]
            );
            $hasMain = (bool) scalar(
                'SELECT COUNT(*) FROM sc_conta_meta WHERE id_usuario=? AND principal=1',
                [$uid]
            );
            foreach ($accounts as $a) {
                $events = accountCashEvents((int) $a['id_conta']);
                $opening = cents($a['saldo_atual'], true);
                $date = substr($owner['data_cadastro'], 0, 10);
                foreach ($events as $event) {
                    $opening -= cents($event['delta'], true);
                    $date = min($date, $event['data']);
                }
                query(
                    'INSERT INTO sc_conta_meta (id_conta,id_usuario,principal,data_abertura,saldo_inicial,origem_legada) VALUES (?,?,?,?,?,1)',
                    [$a['id_conta'], $uid, $hasMain ? 0 : 1, $date, dec($opening)]
                );
                $hasMain = true;
            }
            db()->commit();
        } catch (Throwable $ex) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            throw $ex;
        }
    }
}

function accountOrder(string $alias = 'c'): string
{
    return "COALESCE((SELECT m.principal FROM sc_conta_meta m WHERE m.id_conta=$alias.id_conta),0) DESC,$alias.saldo_atual DESC,$alias.nome_conta,$alias.id_conta";
}

function balanceAt(string $end): string
{
    $total = 0;
    $accounts = rows(
        'SELECT c.id_conta,m.data_abertura,m.saldo_inicial FROM conta c JOIN sc_conta_meta m ON m.id_conta=c.id_conta WHERE c.id_usuario=?',
        [usuarioLogado()]
    );
    foreach ($accounts as $a) {
        if ($a['data_abertura'] > $end) {
            continue;
        }
        $total += cents($a['saldo_inicial'], true);
        foreach (accountCashEvents((int) $a['id_conta']) as $event) {
            if ($event['data'] <= $end) {
                $total += cents($event['delta'], true);
            }
        }
    }
    return dec($total);
}

function checkAccountDate(int $id, string $date): void
{
    $start = scalar('SELECT data_abertura FROM sc_conta_meta WHERE id_conta=? AND id_usuario=?', [
        $id,
        usuarioLogado()
    ]);
    if (!$start || $date < $start) {
        throw new DomainException(
            'A data do lançamento é anterior à abertura da conta. Confira a data em Editar conta.'
        );
    }
}

function saveAccountMeta(int $id, bool $new, string $opening): void
{
    $date = dateValue('data_abertura');
    $current = one('SELECT * FROM sc_conta_meta WHERE id_conta=? AND id_usuario=? FOR UPDATE', [
        $id,
        usuarioLogado()
    ]);
    foreach (accountCashEvents($id) as $event) {
        if ($event['data'] < $date) {
            throw new DomainException('Há movimentações anteriores a essa data de abertura.');
        }
    }
    $main = isset($_POST['principal']);
    if (
        !$main &&
        !(bool) scalar(
            'SELECT COUNT(*) FROM sc_conta_meta WHERE id_usuario=? AND principal=1 AND id_conta<>?',
            [usuarioLogado(), $id]
        )
    ) {
        $main = true;
    }
    if ($main) {
        query('UPDATE sc_conta_meta SET principal=0 WHERE id_usuario=?', [usuarioLogado()]);
    }
    if ($new) {
        query(
            'INSERT INTO sc_conta_meta (id_conta,id_usuario,principal,data_abertura,saldo_inicial,origem_legada) VALUES (?,?,?,?,?,0)',
            [$id, usuarioLogado(), $main ? 1 : 0, $date, $opening]
        );
    } else {
        query(
            'UPDATE sc_conta_meta SET principal=?,data_abertura=?,origem_legada=0 WHERE id_conta=? AND id_usuario=?',
            [$main ? 1 : 0, $date, $id, usuarioLogado()]
        );
    }
}

function phoneValue(string $key = 'telefone'): ?string
{
    $value = textValue($key, 30, false);
    if ($value === null) {
        return null;
    }
    $digits = preg_replace('/\D/', '', $value);
    if (!in_array(strlen($digits), [10, 11], true)) {
        throw new DomainException('Informe um telefone com DDD e 10 ou 11 dígitos.');
    }
    $local = substr($digits, 2);
    return '(' . substr($digits, 0, 2) . ') ' . substr($local, 0, -4) . '-' . substr($local, -4);
}
