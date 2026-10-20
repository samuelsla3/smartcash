<?php

declare(strict_types=1);
function listQuery(string $m, bool $filtered = true): array
{
    $uid = usuarioLogado();
    $cfg = modules()[$m];
    $joins = match ($m) {
        'contas'
            => ' LEFT JOIN banco b ON b.id_banco=s.id_banco LEFT JOIN tipo_conta tc ON tc.id_tipo_conta=s.id_tipo_conta',
        'recebimentos'
            => ' LEFT JOIN conta c ON c.id_conta=s.id_conta LEFT JOIN tipo_recebimento tp ON tp.id_tipo_recebimento=s.id_tipo_recebimento',
        'despesas'
            => ' LEFT JOIN conta c ON c.id_conta=s.id_conta LEFT JOIN cartao ca ON ca.id_cartao=s.id_cartao LEFT JOIN categoria ct ON ct.id_categoria=s.id_categoria LEFT JOIN sc_pagamento_item pi ON pi.id_despesa=s.id_despesa',
        'transferencias'
            => ' LEFT JOIN conta co ON co.id_conta=s.id_conta_origem LEFT JOIN conta cd ON cd.id_conta=s.id_conta_destino',
        'cartoes'
            => ' LEFT JOIN banco b ON b.id_banco=s.id_banco LEFT JOIN conta c ON c.id_conta=s.id_conta',
        'investimentos'
            => ' LEFT JOIN conta c ON c.id_conta=s.id_conta LEFT JOIN tipo_investimento tp ON tp.id_tipo_investimento=s.id_tipo_investimento',
        'categorias'
            => ' LEFT JOIN sc_categoria_usuario o ON o.id_categoria=s.id_categoria LEFT JOIN categoria parent ON parent.id_categoria=s.id_categoria_pai',
        default => ''
    };
    $extra = match ($m) {
        'contas' => ',b.nome AS banco,tc.nome AS tipo',
        'recebimentos' => ',c.nome_conta,tp.nome AS tipo',
        'despesas' => ',c.nome_conta,ca.nome_cartao,ct.nome AS categoria,pi.id_pagamento',
        'transferencias' => ',co.nome_conta AS origem,cd.nome_conta AS destino',
        'cartoes' => ',b.nome AS banco,c.nome_conta',
        'investimentos' => ',c.nome_conta,tp.nome AS tipo',
        'categorias' => ',o.id_usuario AS owner,parent.nome AS parent_name',
        default => ''
    };
    $sql =
        "SELECT s.* $extra FROM {$cfg['table']} s $joins WHERE " .
        ($m === 'categorias' ? '(o.id_usuario IS NULL OR o.id_usuario=?)' : 's.id_usuario=?');
    $params = [$uid];
    $date = match ($m) {
        'recebimentos' => 'data_recebimento',
        'despesas' => 'data',
        'transferencias' => 'data_transferencia',
        'investimentos' => 'data_aplicacao',
        default => null
    };
    if (
        $filtered &&
        $date &&
        (($_GET['modo_periodo'] ?? '') === 'mes' ||
            (($_GET['modo_periodo'] ?? '') !== 'todos' &&
                (!empty($_GET['inicio']) || !empty($_GET['fim']))))
    ) {
        [$i, $f] = period();
        $sql .= " AND s.`$date` BETWEEN ? AND ?";
        array_push($params, $i, $f);
    }
    $q = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
    if ($filtered && $q !== '') {
        $col = match ($m) {
            'contas' => 'nome_conta',
            'cartoes' => 'nome_cartao',
            'categorias', 'investimentos' => 'nome',
            default => 'descricao'
        };
        $sql .= " AND s.$col LIKE ?";
        $params[] = '%' . mb_substr($q, 0, 100) . '%';
    }
    if ($filtered && $m === 'despesas' && !empty($_GET['categoria'])) {
        $sql .= ' AND s.id_categoria=?';
        $params[] = (int) $_GET['categoria'];
    }
    if (
        $filtered &&
        in_array($m, ['despesas', 'recebimentos', 'investimentos']) &&
        !empty($_GET['conta'])
    ) {
        $sql .= ' AND s.id_conta=?';
        $params[] = (int) $_GET['conta'];
    }
    return [$sql, $params];
}

function listData(string $m): array
{
    [$sql, $params] = listQuery($m);
    $pk = modules()[$m]['pk'];
    $count = (int) scalar("SELECT COUNT(*) FROM ($sql) sc_count", $params);
    $pages = max(1, (int) ceil($count / 20));
    $page = min($pages, max(1, (int) ($_GET['pagina'] ?? 1)));
    $offset = ($page - 1) * 20;
    $order = $m === 'contas' ? accountOrder('s') : "s.$pk DESC";
    $data = rows($sql . " ORDER BY $order LIMIT 20 OFFSET $offset", $params);
    $col = match ($m) {
        'recebimentos', 'despesas', 'transferencias' => 'valor',
        'contas' => 'saldo_atual',
        'investimentos' => 'valor_aplicado',
        'objetivos' => 'valor_objetivo',
        'cartoes' => 'limite_total',
        default => null
    };
    $total = $col ? scalar("SELECT COALESCE(SUM($col),0) FROM ($sql) sc_total", $params) : null;
    return compact('data', 'count', 'page', 'pages', 'total');
}

function summary(string $i, string $f): array
{
    $u = usuarioLogado();
    $receitas = (string) scalar(
        'SELECT COALESCE(SUM(valor),0) FROM recebimento WHERE id_usuario=? AND data_recebimento BETWEEN ? AND ?',
        [$u, $i, $f]
    );
    $despesas = (string) scalar(
        'SELECT COALESCE(SUM(valor),0) FROM despesa WHERE id_usuario=? AND data BETWEEN ? AND ?',
        [$u, $i, $f]
    );
    $gain = (string) scalar(
        'SELECT COALESCE(SUM(GREATEST(rendimento,0)),0) FROM sc_resgate WHERE id_usuario=? AND data BETWEEN ? AND ?',
        [$u, $i, $f]
    );
    $loss = (string) scalar(
        'SELECT COALESCE(SUM(-LEAST(rendimento,0)),0) FROM sc_resgate WHERE id_usuario=? AND data BETWEEN ? AND ?',
        [$u, $i, $f]
    );
    $in = cents($receitas) + cents($gain);
    $out = cents($despesas) + cents($loss);
    return [
        'saldo_total' => balanceAt($f),
        'saldo_atual' => (string) scalar(
            'SELECT COALESCE(SUM(saldo_atual),0) FROM conta WHERE id_usuario=?',
            [$u]
        ),
        'fim_periodo' => $f,
        'receitas' => dec($in),
        'despesas' => dec($out),
        'resultado' => dec($in - $out),
        'recebimentos' => $receitas,
        'compras' => $despesas,
        'ganhos' => $gain,
        'perdas' => $loss,
        'investido' => (string) scalar(
            'SELECT COALESCE(SUM(valor_aplicado+rendimento),0) FROM investimento WHERE id_usuario=?',
            [$u]
        ),
        'divida' => (string) scalar(
            'SELECT COALESCE(SUM(limite_total-limite_disponivel),0) FROM cartao WHERE id_usuario=?',
            [$u]
        )
    ];
}

function categoryTotals(string $i, string $f): array
{
    $r = rows(
        'SELECT c.nome AS categoria,SUM(d.valor) AS total,COUNT(*) AS quantidade FROM despesa d JOIN categoria c ON c.id_categoria=d.id_categoria WHERE d.id_usuario=? AND d.data BETWEEN ? AND ? GROUP BY c.id_categoria,c.nome ORDER BY total DESC',
        [usuarioLogado(), $i, $f]
    );
    $loss = scalar(
        'SELECT COALESCE(SUM(-LEAST(rendimento,0)),0) FROM sc_resgate WHERE id_usuario=? AND data BETWEEN ? AND ?',
        [usuarioLogado(), $i, $f]
    );
    if (cents((string) $loss) > 0) {
        $r[] = [
            'categoria' => 'Perdas realizadas em investimentos',
            'total' => $loss,
            'quantidade' => null
        ];
    }
    return $r;
}

function recentMovements(string $i, string $f, int $limit = 8): array
{
    $u = usuarioLogado();
    return rows(
        "SELECT 'despesas' AS modulo,id_despesa AS id,descricao,valor,data,'despesa' AS tipo FROM despesa WHERE id_usuario=? AND data BETWEEN ? AND ?
 UNION ALL SELECT 'recebimentos',id_recebimento,descricao,valor,data_recebimento,'recebimento' FROM recebimento WHERE id_usuario=? AND data_recebimento BETWEEN ? AND ?
 UNION ALL SELECT 'transferencias',id_transferencia,COALESCE(descricao,'Transferência entre contas'),valor,data_transferencia,'transferencia' FROM transferencia WHERE id_usuario=? AND data_transferencia BETWEEN ? AND ?
 UNION ALL SELECT 'cartoes',id_cartao,'Quitação de cartão',valor,data,'quitacao' FROM sc_pagamento_cartao WHERE id_usuario=? AND data BETWEEN ? AND ?
 UNION ALL SELECT 'investimentos',0,CONCAT('Resgate: ',nome),principal+rendimento,data,'resgate' FROM sc_resgate WHERE id_usuario=? AND data BETWEEN ? AND ?
 ORDER BY data DESC,id DESC LIMIT $limit",
        [$u, $i, $f, $u, $i, $f, $u, $i, $f, $u, $i, $f, $u, $i, $f]
    );
}

function dueRecurring(): array
{
    return rows(
        "SELECT id_despesa AS id,'despesas' AS modulo,descricao,valor,proxima_cobranca AS proxima FROM despesa WHERE id_usuario=? AND recorrente=1 AND proxima_cobranca<=?
 UNION ALL SELECT id_recebimento,'recebimentos',descricao,valor,proximo_recebimento FROM recebimento WHERE id_usuario=? AND recorrente=1 AND proximo_recebimento<=? ORDER BY proxima LIMIT 12",
        [usuarioLogado(), date('Y-m-d'), usuarioLogado(), date('Y-m-d')]
    );
}

function chartSeries(string $i, string $f): array
{
    $u = usuarioLogado();
    $r = rows(
        "SELECT DATE_FORMAT(data_recebimento,'%Y-%m') AS mes,SUM(valor) AS valor,'receita' AS tipo FROM recebimento WHERE id_usuario=? AND data_recebimento BETWEEN ? AND ? GROUP BY mes
 UNION ALL SELECT DATE_FORMAT(data,'%Y-%m'),SUM(valor),'despesa' FROM despesa WHERE id_usuario=? AND data BETWEEN ? AND ? GROUP BY DATE_FORMAT(data,'%Y-%m')
 UNION ALL SELECT DATE_FORMAT(data,'%Y-%m'),SUM(GREATEST(rendimento,0)),'receita' FROM sc_resgate WHERE id_usuario=? AND data BETWEEN ? AND ? GROUP BY DATE_FORMAT(data,'%Y-%m')
 UNION ALL SELECT DATE_FORMAT(data,'%Y-%m'),SUM(-LEAST(rendimento,0)),'despesa' FROM sc_resgate WHERE id_usuario=? AND data BETWEEN ? AND ? GROUP BY DATE_FORMAT(data,'%Y-%m')",
        [$u, $i, $f, $u, $i, $f, $u, $i, $f, $u, $i, $f]
    );
    $map = [];
    $start = (new DateTimeImmutable($i))->modify('first day of this month');
    $end = (new DateTimeImmutable($f))->modify('first day of this month');
    while ($start <= $end) {
        $map[$start->format('Y-m')] = ['receita' => 0, 'despesa' => 0];
        $start = $start->modify('+1 month');
    }
    foreach ($r as $v) {
        $map[$v['mes']][$v['tipo']] += cents($v['valor']);
    }
    $labels = [];
    $in = [];
    $out = [];
    foreach ($map as $month => $vals) {
        $labels[] = substr($month, 5) . '/' . substr($month, 2, 2);
        $in[] = $vals['receita'] / 100;
        $out[] = $vals['despesa'] / 100;
    }
    return ['labels' => $labels, 'receitas' => $in, 'despesas' => $out];
}
