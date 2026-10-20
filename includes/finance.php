<?php

declare(strict_types=1);
function modules(): array
{
    return [
        'contas' => [
            'table' => 'conta',
            'pk' => 'id_conta',
            'title' => 'Contas',
            'singular' => 'conta',
            'desc' => 'Seu dinheiro, organizado em um só lugar.',
            'fields' => [
                'nome_conta' => ['Nome da conta', 'text', 100],
                'id_banco' => ['Instituição', 'select', 'banco'],
                'id_tipo_conta' => ['Tipo de conta', 'select', 'tipo_conta'],
                'saldo_atual' => ['Saldo inicial', 'money', null],
                'agencia' => ['Agência (opcional)', 'optional', 20],
                'num_conta' => ['Número (opcional)', 'optional', 30]
            ]
        ],
        'recebimentos' => [
            'table' => 'recebimento',
            'pk' => 'id_recebimento',
            'title' => 'Recebimentos',
            'singular' => 'recebimento',
            'desc' => 'Acompanhe cada entrada e planeje o próximo passo.',
            'fields' => [
                'descricao' => ['Descrição', 'text', 255],
                'valor' => ['Valor recebido', 'money', null],
                'id_conta' => ['Conta de destino', 'select', 'conta'],
                'id_tipo_recebimento' => ['Tipo de recebimento', 'select', 'tipo_recebimento'],
                'data_recebimento' => ['Data do recebimento', 'date', null]
            ]
        ],
        'despesas' => [
            'table' => 'despesa',
            'pk' => 'id_despesa',
            'title' => 'Despesas',
            'singular' => 'despesa',
            'desc' => 'Entenda para onde seu dinheiro está indo.',
            'fields' => [
                'descricao' => ['Descrição', 'text', 255],
                'valor' => ['Valor da despesa', 'money', null],
                'id_categoria' => ['Categoria', 'select', 'categoria'],
                'id_tipo_despesa' => ['Tipo de despesa', 'select', 'tipo_despesa'],
                'forma_pagamento' => [
                    'Forma de pagamento',
                    'select',
                    ['conta' => 'Débito em conta', 'cartao' => 'Cartão de crédito']
                ],
                'id_conta' => ['Conta de pagamento', 'select', 'conta'],
                'id_cartao' => ['Cartão', 'select', 'cartao'],
                'data' => ['Data da despesa', 'date', null]
            ]
        ],
        'transferencias' => [
            'table' => 'transferencia',
            'pk' => 'id_transferencia',
            'title' => 'Transferências',
            'singular' => 'transferência',
            'desc' => 'Mova seu dinheiro entre suas contas sem gerar despesas.',
            'fields' => [
                'id_conta_origem' => ['Conta de origem', 'select', 'conta'],
                'id_conta_destino' => ['Conta de destino', 'select', 'conta'],
                'valor' => ['Valor a transferir', 'money', null],
                'data_transferencia' => ['Data', 'date', null],
                'descricao' => ['Descrição (opcional)', 'optional', 255]
            ]
        ],
        'cartoes' => [
            'table' => 'cartao',
            'pk' => 'id_cartao',
            'title' => 'Cartões',
            'singular' => 'cartão',
            'desc' => 'Compras, limites e quitação do saldo em aberto.',
            'fields' => [
                'nome_cartao' => ['Nome do cartão', 'text', 100],
                'id_conta' => ['Conta de pagamento padrão', 'select', 'conta'],
                'dia_vencimento' => ['Dia do vencimento', 'day', null],
                'limite_total' => ['Limite total', 'money', null]
            ]
        ],
        'investimentos' => [
            'table' => 'investimento',
            'pk' => 'id_investimento',
            'title' => 'Investimentos',
            'singular' => 'investimento',
            'desc' => 'Cuide do patrimônio que está construindo.',
            'fields' => [
                'nome' => ['Nome da aplicação', 'text', 100],
                'id_tipo_investimento' => ['Tipo de investimento', 'select', 'tipo_investimento'],
                'id_conta' => ['Conta de origem', 'select', 'conta'],
                'valor_aplicado' => ['Valor aplicado', 'money', null],
                'rendimento' => ['Rendimento acumulado em R$', 'signed', null],
                'data_aplicacao' => ['Data de aplicação', 'date', null]
            ]
        ],
        'objetivos' => [
            'table' => 'objetivo_financeiro',
            'pk' => 'id_objetivo',
            'title' => 'Objetivos',
            'singular' => 'objetivo',
            'desc' => 'Transforme seus planos em conquistas.',
            'fields' => [
                'descricao' => ['O que você quer conquistar?', 'text', 255],
                'valor_objetivo' => ['Quanto precisa guardar?', 'money', null],
                'valor_atual' => ['Quanto já tem reservado?', 'money', null],
                'data_limite' => ['Prazo (opcional)', 'deadline', null],
                'status' => [
                    'Status',
                    'select',
                    [
                        'Em andamento' => 'Em andamento',
                        'Concluído' => 'Concluído',
                        'Cancelado' => 'Cancelado'
                    ]
                ]
            ]
        ],
        'categorias' => [
            'table' => 'categoria',
            'pk' => 'id_categoria',
            'title' => 'Categorias',
            'singular' => 'categoria',
            'desc' => 'Dê mais significado à organização dos seus gastos.',
            'fields' => [
                'nome' => ['Nome da categoria', 'text', 100],
                'descricao' => ['Descrição (opcional)', 'optional', 255],
                'id_categoria_pai' => ['Categoria principal (opcional)', 'parent', 'categoria']
            ]
        ]
    ];
}

function owned(string $table, int $id, bool $lock = true): array
{
    $pk = match ($table) {
        'conta' => 'id_conta',
        'cartao' => 'id_cartao',
        'despesa' => 'id_despesa',
        'recebimento' => 'id_recebimento',
        'investimento' => 'id_investimento',
        'transferencia' => 'id_transferencia',
        'objetivo_financeiro' => 'id_objetivo',
        default => throw new LogicException('Modelo inválido')
    };
    $r = one("SELECT * FROM $table WHERE $pk=? AND id_usuario=?" . ($lock ? ' FOR UPDATE' : ''), [
        $id,
        usuarioLogado()
    ]);
    if (!$r) {
        throw new DomainException('Registro não encontrado ou sem permissão de acesso.');
    }
    return $r;
}

function categories(): array
{
    return rows(
        'SELECT c.*, o.id_usuario AS owner FROM categoria c LEFT JOIN sc_categoria_usuario o ON o.id_categoria=c.id_categoria WHERE o.id_usuario IS NULL OR o.id_usuario=? ORDER BY c.nome',
        [usuarioLogado()]
    );
}

function categoryAllowed(int $id): void
{
    foreach (categories() as $r) {
        if ((int) $r['id_categoria'] === $id) {
            return;
        }
    }
    throw new DomainException('Categoria inválida.');
}

function editableCategory(int $id): array
{
    $r = one(
        'SELECT c.* FROM categoria c JOIN sc_categoria_usuario o ON o.id_categoria=c.id_categoria WHERE c.id_categoria=? AND o.id_usuario=? FOR UPDATE',
        [$id, usuarioLogado()]
    );
    if (!$r) {
        throw new DomainException(
            'Categorias do catálogo compartilhado são somente leitura. Crie uma categoria pessoal.'
        );
    }
    return $r;
}

function reference(string $table, int $id): void
{
    $allowed = [
        'banco' => 'id_banco',
        'tipo_conta' => 'id_tipo_conta',
        'tipo_despesa' => 'id_tipo_despesa',
        'tipo_recebimento' => 'id_tipo_recebimento',
        'tipo_investimento' => 'id_tipo_investimento'
    ];
    if (
        !isset($allowed[$table]) ||
        !one("SELECT {$allowed[$table]} FROM $table WHERE {$allowed[$table]}=?", [$id])
    ) {
        throw new DomainException('Selecione uma opção válida.');
    }
}

function adjustAccount(int $id, int $delta): void
{
    $a = owned('conta', $id);
    $new = cents($a['saldo_atual'], true) + $delta;
    if ($delta < 0 && $new < 0) {
        throw new DomainException(
            'Saldo insuficiente. Confira a conta escolhida. Estornos de entradas também precisam de saldo.'
        );
    }
    if (abs($new) > 999999999999999) {
        throw new DomainException('Saldo fora do limite permitido.');
    }
    query('UPDATE conta SET saldo_atual=? WHERE id_conta=?', [dec($new), $id]);
}

function adjustCard(int $id, int $delta): void
{
    $a = owned('cartao', $id);
    $new = cents($a['limite_disponivel']) + $delta;
    if ($new < 0 || $new > cents($a['limite_total'])) {
        throw new DomainException('Limite disponível insuficiente ou inconsistente.');
    }
    query('UPDATE cartao SET limite_disponivel=? WHERE id_cartao=?', [dec($new), $id]);
}

function expenseEffect(array $r, int $direction): void
{
    if ($r['id_cartao']) {
        adjustCard((int) $r['id_cartao'], -$direction * cents($r['valor']));
    } else {
        adjustAccount((int) $r['id_conta'], -$direction * cents($r['valor']));
    }
}

function notPaid(int $id): void
{
    if (one('SELECT id_despesa FROM sc_pagamento_item WHERE id_despesa=?', [$id])) {
        throw new DomainException(
            'Esta compra já foi quitada. Estorne a quitação no cartão antes de editar ou excluir a compra.'
        );
    }
}

function recurrence(array &$data, string $m): void
{
    if (!in_array($m, ['despesas', 'recebimentos'], true)) {
        return;
    }
    $next = $m === 'despesas' ? 'proxima_cobranca' : 'proximo_recebimento';
    $date = $m === 'despesas' ? 'data' : 'data_recebimento';
    $data['recorrente'] = isset($_POST['recorrente']) ? 1 : 0;
    $data['frequencia'] = null;
    $data[$next] = null;
    if ($data['recorrente']) {
        $freq = $_POST['frequencia'] ?? '';
        if (!in_array($freq, ['diaria', 'semanal', 'mensal', 'anual'], true)) {
            throw new DomainException('Selecione a frequência.');
        }
        $n = dateValue($next, false, true);
        if ($n <= $data[$date]) {
            throw new DomainException(
                'A próxima ocorrência precisa ser posterior à data deste lançamento.'
            );
        }
        $data['frequencia'] = $freq;
        $data[$next] = $n;
    }
}

function nextOccurrence(string $date, string $freq, int $anchorDay): string
{
    $d = new DateTimeImmutable($date);
    return match ($freq) {
        'diaria' => $d->modify('+1 day')->format('Y-m-d'),
        'semanal' => $d->modify('+7 days')->format('Y-m-d'),
        'mensal' => ($x = $d->modify('first day of next month'))
            ->setDate(
                (int) $x->format('Y'),
                (int) $x->format('m'),
                min($anchorDay, (int) $x->format('t'))
            )
            ->format('Y-m-d'),
        'anual' => ($x = $d->modify('first day of this month')->modify('+1 year'))
            ->setDate(
                (int) $x->format('Y'),
                (int) $x->format('m'),
                min($anchorDay, (int) $x->format('t'))
            )
            ->format('Y-m-d'),
        default => throw new DomainException('Frequência inválida.')
    };
}

function saveRecord(string $m, int $id = 0): int
{
    $cfg = modules()[$m];
    $t = $cfg['table'];
    $pk = $cfg['pk'];
    $old = $id ? ($m === 'categorias' ? editableCategory($id) : owned($t, $id)) : null;
    $d = [];
    foreach ($cfg['fields'] as $key => [$label, $type, $opt]) {
        if ($m === 'contas' && $id && $key === 'saldo_atual') {
            continue;
        }
        if ($m === 'despesas' && in_array($key, ['id_conta', 'id_cartao', 'forma_pagamento'])) {
            continue;
        }
        $d[$key] = match ($type) {
            'text' => textValue($key, $opt),
            'optional' => textValue($key, $opt, false),
            'money' => amount($key, !in_array($key, ['saldo_atual', 'valor_atual'], true)),
            'signed' => amount($key, false, true),
            'date' => dateValue($key),
            'deadline' => dateValue($key, true, true),
            'day' => (int) ($_POST[$key] ?? 0),
            'parent' => empty($_POST[$key]) ? null : postId($key),
            'select' => is_array($opt) ? (string) ($_POST[$key] ?? '') : postId($key)
        };
        if ($type === 'select' && is_array($opt) && !array_key_exists($d[$key], $opt)) {
            throw new DomainException('Selecione uma opção válida.');
        }
        if (in_array($type, ['select', 'parent']) && is_string($opt) && $d[$key]) {
            if ($opt === 'conta' || $opt === 'cartao') {
                owned($opt, (int) $d[$key]);
            } elseif ($opt === 'categoria') {
                categoryAllowed((int) $d[$key]);
            } else {
                reference($opt, (int) $d[$key]);
            }
        }
    }
    if ($m === 'cartoes') {
        $d['id_banco'] = (int) owned('conta', $d['id_conta'])['id_banco'];
    }
    if ($m === 'despesas') {
        $forma = $_POST['forma_pagamento'] ?? '';
        if (!in_array($forma, ['conta', 'cartao'], true)) {
            throw new DomainException('Escolha uma forma de pagamento.');
        }
        $d['id_conta'] = $forma === 'conta' ? postId('id_conta') : null;
        $d['id_cartao'] = $forma === 'cartao' ? postId('id_cartao') : null;
        if ($d['id_conta']) {
            checkAccountDate((int) $d['id_conta'], $d['data']);
        }
        if ($old) {
            notPaid($id);
            expenseEffect($old, -1);
        }
        expenseEffect($d, 1);
    }
    if ($m === 'recebimentos') {
        checkAccountDate((int) $d['id_conta'], $d['data_recebimento']);
        $deltas = [(int) $d['id_conta'] => cents($d['valor'])];
        if ($old) {
            $deltas[(int) $old['id_conta']] =
                ($deltas[(int) $old['id_conta']] ?? 0) - cents($old['valor']);
        }
        foreach ($deltas as $account => $delta) {
            if ($delta) {
                adjustAccount($account, $delta);
            }
        }
    }
    if ($m === 'transferencias') {
        checkAccountDate((int) $d['id_conta_origem'], $d['data_transferencia']);
        checkAccountDate((int) $d['id_conta_destino'], $d['data_transferencia']);
        if ($d['id_conta_origem'] === $d['id_conta_destino']) {
            throw new DomainException('Escolha contas de origem e destino diferentes.');
        }
        $deltas = [];
        foreach (
            [
                [$d['id_conta_origem'], -cents($d['valor'])],
                [$d['id_conta_destino'], cents($d['valor'])]
            ] as [$account, $delta]
        ) {
            $deltas[$account] = ($deltas[$account] ?? 0) + $delta;
        }
        if ($old) {
            $a = (int) $old['id_conta_origem'];
            $deltas[$a] = ($deltas[$a] ?? 0) + cents($old['valor']);
            $a = (int) $old['id_conta_destino'];
            $deltas[$a] = ($deltas[$a] ?? 0) - cents($old['valor']);
        }
        foreach ($deltas as $account => $delta) {
            if ($delta) {
                adjustAccount($account, $delta);
            }
        }
    }
    if ($m === 'cartoes') {
        if ($d['dia_vencimento'] < 1 || $d['dia_vencimento'] > 31) {
            throw new DomainException('O vencimento deve ser um dia entre 1 e 31.');
        }
        $used = $old ? cents($old['limite_total']) - cents($old['limite_disponivel']) : 0;
        if (cents($d['limite_total']) < $used) {
            throw new DomainException('O novo limite é menor que o saldo utilizado.');
        }
        $d['limite_disponivel'] = dec(cents($d['limite_total']) - $used);
    }
    if ($m === 'investimentos') {
        checkAccountDate((int) $d['id_conta'], $d['data_aplicacao']);
        if (cents($d['valor_aplicado']) + cents($d['rendimento'], true) < 0) {
            throw new DomainException('A perda não pode ser maior que o principal aplicado.');
        }
        if ($old) {
            adjustAccount((int) $old['id_conta'], cents($old['valor_aplicado']));
        }
        adjustAccount($d['id_conta'], -cents($d['valor_aplicado']));
    }
    if ($m === 'objetivos') {
        if ($d['status'] !== 'Cancelado') {
            $d['status'] =
                cents($d['valor_atual']) >= cents($d['valor_objetivo'])
                    ? 'Concluído'
                    : 'Em andamento';
        }
    }
    if ($m === 'categorias' && $d['id_categoria_pai']) {
        $visited = [];
        $parent = $d['id_categoria_pai'];
        while ($parent) {
            if ($parent === $id || isset($visited[$parent])) {
                throw new DomainException('Uma categoria não pode ser descendente de si mesma.');
            }
            $visited[$parent] = true;
            categoryAllowed($parent);
            $parent = (int) scalar('SELECT id_categoria_pai FROM categoria WHERE id_categoria=?', [
                $parent
            ]);
        }
    }
    recurrence($d, $m);
    if ($id) {
        $set = implode(',', array_map(fn ($key) => "`$key`=?", array_keys($d)));
        query("UPDATE $t SET $set WHERE $pk=?", [...array_values($d), $id]);
    } else {
        if ($m !== 'categorias') {
            $d['id_usuario'] = usuarioLogado();
        }
        $fields = '`' . implode('`,`', array_keys($d)) . '`';
        $marks = implode(',', array_fill(0, count($d), '?'));
        query("INSERT INTO $t ($fields) VALUES ($marks)", array_values($d));
        $id = (int) db()->lastInsertId();
        if ($m === 'categorias') {
            query('INSERT INTO sc_categoria_usuario (id_categoria,id_usuario) VALUES (?,?)', [
                $id,
                usuarioLogado()
            ]);
        }
    }
    if ($m === 'contas') {
        saveAccountMeta($id, $old === null, $d['saldo_atual'] ?? '0.00');
    }
    if (in_array($m, ['despesas', 'recebimentos']) && $d['recorrente']) {
        notify(
            'Recorrência de ' . $d['descricao'] . ' programada. Confirme cada ocorrência no painel.'
        );
    }
    return $id;
}

function deleteRecord(string $m, int $id): void
{
    $cfg = modules()[$m];
    $r = $m === 'categorias' ? editableCategory($id) : owned($cfg['table'], $id);
    if ($m === 'contas') {
        if (cents($r['saldo_atual'], true) !== 0) {
            throw new DomainException(
                'Transfira ou registre a saída do saldo antes de excluir a conta.'
            );
        }
        foreach (
            [
                'recebimento' => 'id_conta',
                'despesa' => 'id_conta',
                'cartao' => 'id_conta',
                'investimento' => 'id_conta',
                'transferencia' => 'id_conta_origem',
                'sc_pagamento_cartao' => 'id_conta',
                'sc_resgate' => 'id_conta'
            ] as $t => $col
        ) {
            if (scalar("SELECT COUNT(*) FROM $t WHERE $col=?", [$id])) {
                throw new DomainException(
                    'Esta conta tem histórico vinculado e precisa ser preservada.'
                );
            }
        }
        if (scalar('SELECT COUNT(*) FROM transferencia WHERE id_conta_destino=?', [$id])) {
            throw new DomainException('Esta conta tem transferências vinculadas.');
        }
    }
    if (
        $m === 'cartoes' &&
        (cents($r['limite_disponivel']) !== cents($r['limite_total']) ||
            scalar('SELECT COUNT(*) FROM despesa WHERE id_cartao=?', [$id]) ||
            scalar('SELECT COUNT(*) FROM sc_pagamento_cartao WHERE id_cartao=?', [$id]))
    ) {
        throw new DomainException(
            'Cartões com saldo utilizado ou histórico devem ser preservados.'
        );
    }
    if ($m === 'categorias') {
        if (
            scalar('SELECT COUNT(*) FROM despesa WHERE id_categoria=?', [$id]) ||
            scalar('SELECT COUNT(*) FROM categoria WHERE id_categoria_pai=?', [$id])
        ) {
            throw new DomainException(
                'Categoria em uso. Reorganize as despesas ou subcategorias antes de excluir.'
            );
        }
        query('DELETE FROM sc_categoria_usuario WHERE id_categoria=?', [$id]);
    }
    if ($m === 'despesas') {
        notPaid($id);
        expenseEffect($r, -1);
    }
    if ($m === 'recebimentos') {
        adjustAccount((int) $r['id_conta'], -cents($r['valor']));
    }
    if ($m === 'transferencias') {
        adjustAccount((int) $r['id_conta_destino'], -cents($r['valor']));
        adjustAccount((int) $r['id_conta_origem'], cents($r['valor']));
    }
    if ($m === 'investimentos') {
        if (scalar('SELECT COUNT(*) FROM investimento_objetivo WHERE id_investimento=?', [$id])) {
            throw new DomainException(
                'Remova os vínculos legados com objetivos antes de estornar esta aplicação.'
            );
        }
        adjustAccount((int) $r['id_conta'], cents($r['valor_aplicado']));
    }
    if (
        $m === 'objetivos' &&
        scalar('SELECT COUNT(*) FROM investimento_objetivo WHERE id_objetivo=?', [$id])
    ) {
        throw new DomainException('Este objetivo tem aplicações vinculadas no banco legado.');
    }
    if ($m === 'contas') {
        query('DELETE FROM sc_conta_meta WHERE id_conta=? AND id_usuario=?', [
            $id,
            usuarioLogado()
        ]);
    }
    query("DELETE FROM {$cfg['table']} WHERE {$cfg['pk']}=?", [$id]);
    if (
        $m === 'contas' &&
        !scalar('SELECT COUNT(*) FROM sc_conta_meta WHERE id_usuario=? AND principal=1', [
            usuarioLogado()
        ])
    ) {
        $next = one(
            'SELECT c.id_conta FROM conta c WHERE c.id_usuario=? ORDER BY c.saldo_atual DESC,c.id_conta LIMIT 1',
            [usuarioLogado()]
        );
        if ($next) {
            query('UPDATE sc_conta_meta SET principal=1 WHERE id_conta=?', [$next['id_conta']]);
        }
    }
}

function payCard(int $id): void
{
    $r = owned('cartao', $id);
    $account = postId('id_conta');
    $date = dateValue('data');
    checkAccountDate($account, $date);
    $value = cents($r['limite_total']) - cents($r['limite_disponivel']);
    if ($value <= 0) {
        throw new DomainException('Este cartão não tem saldo a quitar.');
    }
    if (cents($_POST['valor_confirmado'] ?? '') !== $value) {
        throw new DomainException('O saldo do cartão mudou. Atualize a tela antes de quitar.');
    }
    adjustAccount($account, -$value);
    query(
        'INSERT INTO sc_pagamento_cartao (id_usuario,id_cartao,id_conta,valor,data) VALUES (?,?,?,?,?)',
        [usuarioLogado(), $id, $account, dec($value), $date]
    );
    $payment = (int) db()->lastInsertId();
    query(
        'INSERT INTO sc_pagamento_item (id_despesa,id_pagamento) SELECT d.id_despesa,? FROM despesa d LEFT JOIN sc_pagamento_item p ON p.id_despesa=d.id_despesa WHERE d.id_cartao=? AND d.id_usuario=? AND p.id_despesa IS NULL',
        [$payment, $id, usuarioLogado()]
    );
    adjustCard($id, $value);
    notify('Saldo de ' . $r['nome_cartao'] . ' quitado: ' . money(dec($value)) . '.');
}

function undoPayment(int $id): void
{
    $p = one('SELECT * FROM sc_pagamento_cartao WHERE id_pagamento=? AND id_usuario=? FOR UPDATE', [
        $id,
        usuarioLogado()
    ]);
    if (!$p) {
        throw new DomainException('Quitação não encontrada.');
    }
    adjustCard((int) $p['id_cartao'], -cents($p['valor']));
    adjustAccount((int) $p['id_conta'], cents($p['valor']));
    query('DELETE FROM sc_pagamento_item WHERE id_pagamento=?', [$id]);
    query('DELETE FROM sc_pagamento_cartao WHERE id_pagamento=?', [$id]);
}

function redeem(int $id): void
{
    $r = owned('investimento', $id);
    if (scalar('SELECT COUNT(*) FROM investimento_objetivo WHERE id_investimento=?', [$id])) {
        throw new DomainException('Esta aplicação tem vínculos legados com objetivos.');
    }
    $date = dateValue('data');
    if ($date < $r['data_aplicacao']) {
        throw new DomainException('O resgate não pode ser anterior à aplicação.');
    }
    checkAccountDate((int) $r['id_conta'], $date);
    $total = cents($r['valor_aplicado']) + cents($r['rendimento'], true);
    adjustAccount((int) $r['id_conta'], $total);
    query(
        'INSERT INTO sc_resgate (id_usuario,id_conta,id_tipo_investimento,nome,principal,rendimento,data_aplicacao,data) VALUES (?,?,?,?,?,?,?,?)',
        [
            usuarioLogado(),
            $r['id_conta'],
            $r['id_tipo_investimento'],
            $r['nome'],
            $r['valor_aplicado'],
            $r['rendimento'],
            $r['data_aplicacao'],
            $date
        ]
    );
    query('DELETE FROM investimento WHERE id_investimento=?', [$id]);
    notify('Resgate de ' . $r['nome'] . ' concluído: ' . money(dec($total)) . '.');
}

function confirmRecurring(string $m, int $id): void
{
    if (!in_array($m, ['despesas', 'recebimentos'], true)) {
        throw new DomainException('Recorrência inválida.');
    }
    $cfg = modules()[$m];
    $r = owned($cfg['table'], $id);
    $next = $m === 'despesas' ? 'proxima_cobranca' : 'proximo_recebimento';
    $date = $m === 'despesas' ? 'data' : 'data_recebimento';
    if (!$r['recorrente'] || !$r[$next] || $r[$next] > date('Y-m-d')) {
        throw new DomainException('Não existe ocorrência vencida para confirmar.');
    }
    if (($_POST['ocorrencia'] ?? '') !== $r[$next]) {
        throw new DomainException('Esta ocorrência já foi confirmada. Atualize a tela.');
    }
    $data = $r;
    unset($data[$cfg['pk']]);
    $data[$date] = $r[$next];
    $data['recorrente'] = 0;
    $data['frequencia'] = null;
    $data[$next] = null;
    if (!empty($data['id_conta'])) {
        checkAccountDate((int) $data['id_conta'], $data[$date]);
    }
    if ($m === 'despesas') {
        expenseEffect($data, 1);
    } else {
        adjustAccount((int) $data['id_conta'], cents($data['valor']));
    }
    $keys = '`' . implode('`,`', array_keys($data)) . '`';
    $marks = implode(',', array_fill(0, count($data), '?'));
    query("INSERT INTO {$cfg['table']} ($keys) VALUES ($marks)", array_values($data));
    $n = nextOccurrence(
        $r[$next],
        $r['frequencia'],
        max((int) substr($r[$date], 8, 2), (int) substr($r[$next], 8, 2))
    );
    query("UPDATE {$cfg['table']} SET $next=? WHERE {$cfg['pk']}=?", [$n, $id]);
    notify(
        'Ocorrência de ' . $r['descricao'] . ' confirmada para ' . formatDateBr($data[$date]) . '.'
    );
}

function undoRedemption(int $id): void
{
    $r = one('SELECT * FROM sc_resgate WHERE id_resgate=? AND id_usuario=? FOR UPDATE', [
        $id,
        usuarioLogado()
    ]);
    if (!$r) {
        throw new DomainException('Resgate não encontrado.');
    }
    adjustAccount((int) $r['id_conta'], -(cents($r['principal']) + cents($r['rendimento'], true)));
    query(
        'INSERT INTO investimento (id_usuario,id_conta,id_tipo_investimento,nome,valor_aplicado,rendimento,data_aplicacao) VALUES (?,?,?,?,?,?,?)',
        [
            usuarioLogado(),
            $r['id_conta'],
            $r['id_tipo_investimento'],
            $r['nome'],
            $r['principal'],
            $r['rendimento'],
            $r['data_aplicacao']
        ]
    );
    query('DELETE FROM sc_resgate WHERE id_resgate=?', [$id]);
}
