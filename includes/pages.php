<?php

declare(strict_types=1);
function dashboard(): void
{
    [$i, $f] = period();
    $s = summary($i, $f);
    $name = explode(' ', $_SESSION['nome_usuario'] ?? '')[0];
    $actions =
        linkButton(url('recebimentos/cadastrar.php'), 'Recebimento', 'btn-secondary', 'plus') .
        linkButton(url('despesas/cadastrar.php'), 'Nova despesa', '', 'plus');
    pageHead(
        'Seu dinheiro, sob controle.',
        'Olá, ' . $name . '. Vamos cuidar do que importa?',
        $actions
    );
    $pending = cardPendingRows(usuarioLogado());
    if ($pending) {
        echo '<section class="card pending-card">' .
            '<div class="section-head">' .
            '<h2>Cartões com saldo a quitar</h2>' .
            '<span class="badge badge-warning">' .
            count($pending) .
            ' ' .
            (count($pending) === 1 ? 'pendência' : 'pendências') .
            '</span></div><ul class="mini-list">';
        foreach ($pending as $card) {
            echo '<li><span class="movement-icon transfer">' .
                icon('cartoes') .
                '</span><div class="details"><strong>' .
                e($card['nome_cartao']) .
                '</strong><small>Vencimento de referência: dia ' .
                (int) $card['dia_vencimento'] .
                '</small></div><strong class="money">' .
                money($card['utilizado']) .
                '</strong>' .
                linkButton(
                    url('cartoes/quitar.php?id=' . $card['id_cartao']),
                    'Quitar saldo',
                    'btn-small'
                ) .
                '</li>';
        }
        echo '</ul></section>';
    }
    echo '<div class="section-head"><span class="small muted">VISÃO DO PERÍODO</span>';
    periodForm($i, $f);
    echo '</div>';
    metrics($s);
    $due = dueRecurring();
    if ($due) {
        echo '<div class="card" style="margin-bottom:24px">' .
            '<div class="section-head">' .
            '<h2>Recorrências para confirmar <span class="badge badge-warning">' .
            count($due) .
            ('</span>' .
                '</h2>' .
                '<span class="muted small">Só serão lançadas após confirmar</span>' .
                '</div>' .
                '<ul class="mini-list">');
        foreach ($due as $r) {
            echo '<li><span class="movement-icon">' .
                icon($r['modulo']) .
                '</span><div class="details"><strong>' .
                e($r['descricao']) .
                '</strong><small>Ocorrência de ' .
                formatDateBr($r['proxima']) .
                '</small></div><span class="money">' .
                money($r['valor']) .
                '</span>';
            postButton(
                url($r['modulo'] . '/confirmar.php'),
                ['id' => $r['id'], 'ocorrencia' => $r['proxima']],
                'Confirmar',
                'Registrar esta ocorrência e atualizar o saldo?',
                'btn-secondary btn-small'
            );
            echo '</li>';
        }
        echo '</ul></div>';
    }
    $accounts = rows(
        'SELECT c.*,b.nome AS banco FROM conta c JOIN banco b ON b.id_banco=c.id_banco WHERE c.id_usuario=? ORDER BY ' .
            accountOrder('c') .
            ' LIMIT 4',
        [usuarioLogado()]
    );
    if (!$accounts) {
        echo '<div class="welcome-note" style="margin:0 0 24px">' .
            '<div>' .
            '<strong>Seu primeiro passo começa aqui.</strong>Adicione uma conta com o saldo disponível para começar.</div>' .
            linkButton(url('contas/cadastrar.php'), 'Adicionar conta', '', 'plus') .
            '</div>';
    }
    echo '<section class="dashboard-grid">' .
        '<div class="card">' .
        '<div class="section-head">' .
        '<h2>Entradas e saídas</h2>' .
        '<div class="legend">' .
        '<span>Receitas</span>' .
        '<span>Despesas</span>' .
        '</div>' .
        '</div>' .
        '<p class="muted small">Evolução mensal no período selecionado</p>' .
        '<div class="chart-box">' .
        '<canvas id="flowChart" aria-label="Gráfico de receitas e despesas por mês" role="img">' .
        '</canvas>' .
        '</div>' .
        '<details class="small muted" style="margin-top:15px">' .
        '<summary>Ver dados do gráfico</summary>' .
        '<div class="table-scroll">' .
        '<table id="flow-table">' .
        '<thead>' .
        '<tr>' .
        '<th>Mês</th>' .
        '<th>Receitas</th>' .
        '<th>Despesas</th>' .
        '</tr>' .
        '</thead>' .
        '<tbody>';
    $series = chartSeries($i, $f);
    foreach ($series['labels'] as $k => $label) {
        echo '<tr><td>' .
            e($label) .
            '</td><td class="money">' .
            money($series['receitas'][$k]) .
            '</td><td class="money">' .
            money($series['despesas'][$k]) .
            '</td></tr>';
    }
    echo '</tbody></table></div></details></div>';
    $cat = categoryTotals($i, $f);
    echo '<div class="card"><div class="section-head"><h2>Gastos por categoria</h2>' .
        linkButton(
            url('relatorios/categorias.php?' . http_build_query(['inicio' => $i, 'fim' => $f])),
            'Ver relatório',
            'btn-secondary btn-small'
        ) .
        '</div>';
    if (!$cat) {
        emptyState('Um mês para começar', 'Nenhuma despesa neste período.', '', 'categorias');
    } else {
        echo '<div class="chart-box donut">' .
            '<canvas id="categoryChart" aria-label="Distribuição das despesas por categoria" role="img">' .
            '</canvas>' .
            '</div>' .
            '<div class="category-legend">';
        $colors = ['#c0ed8c', '#b2a5ee', '#79c6df', '#e8bc83', '#8fd5b5', '#e3a0bd'];
        foreach ($cat as $k => $c) {
            echo '<div><span class="dot" style="background:' .
                $colors[$k % 6] .
                '"></span><span>' .
                e($c['categoria']) .
                '</span><strong class="money">' .
                money($c['total']) .
                '</strong></div>';
        }
        echo '</div>';
    }
    echo '</div>' .
        '<div class="card">' .
        '<div class="section-head">' .
        '<h2>Últimas movimentações</h2>' .
        '<span class="small muted">No período</span>' .
        '</div>';
    $mov = recentMovements($i, $f);
    if (!$mov) {
        emptyState(
            'Tudo começa com um lançamento',
            'Suas movimentações aparecerão aqui.',
            linkButton(url('recebimentos/cadastrar.php'), 'Adicionar recebimento', 'btn-secondary'),
            'transferencias'
        );
    } else {
        echo '<ul class="mini-list">';
        foreach ($mov as $r) {
            $out = in_array($r['tipo'], ['despesa', 'quitacao']);
            $ic =
                $r['tipo'] === 'transferencia'
                    ? 'transferencias'
                    : ($out
                        ? 'despesas'
                        : 'recebimentos');
            $href = url($r['modulo'] . '/index.php');
            echo '<li><span class="movement-icon ' .
                ($out ? 'out' : ($ic === 'transferencias' ? 'transfer' : '')) .
                '">' .
                icon($ic) .
                '</span><div class="details"><strong><a href="' .
                e($href) .
                '">' .
                e($r['descricao']) .
                '</a></strong><small>' .
                formatDateBr($r['data']) .
                ' · ' .
                e(
                    match ($r['tipo']) {
                        'despesa' => 'Despesa',
                        'recebimento' => 'Recebimento',
                        'transferencia' => 'Entre contas',
                        'quitacao' => 'Quitação (sem nova despesa)',
                        default => 'Resgate de aplicação'
                    }
                ) .
                '</small></div><span class="money ' .
                ($out ? 'negative' : ($ic === 'transferencias' ? '' : 'positive')) .
                '">' .
                ($out ? '− ' : ($ic === 'transferencias' ? '' : '+ ')) .
                money($r['valor']) .
                '</span></li>';
        }
        echo '</ul>';
    }
    echo '</div>' .
        '<div class="card">' .
        '<div class="section-head">' .
        '<h2>Minhas contas <small class="muted">agora</small>' .
        '</h2>' .
        '<a class="text-link" href="' .
        e(url('contas/index.php')) .
        '">Ver todas ' .
        icon('arrow') .
        '</a></div>';
    foreach ($accounts as $r) {
        echo '<div class="account-item"><span class="bank-tile">' .
            e(mb_substr($r['banco'], 0, 1)) .
            '</span><div class="details"><strong>' .
            e($r['nome_conta']) .
            '</strong><small>' .
            e($r['banco']) .
            '</small></div><span class="money">' .
            money($r['saldo_atual']) .
            '</span></div>';
    }
    if (!$accounts) {
        emptyState(
            'Nenhuma conta cadastrada',
            'Organize suas contas em um só lugar.',
            '',
            'contas'
        );
    }
    $pat = cents($s['saldo_atual'], true) + cents($s['investido'], true) - cents($s['divida']);
    echo '<div class="portfolio-summary"><a class="portfolio-row" href="' .
        e(url('investimentos/index.php')) .
        '"><span class="movement-icon">' .
        icon('investimentos') .
        ('</span>' .
            '<span class="portfolio-label">Em investimentos<small>Principal + rendimento</small>' .
            '</span>' .
            '<strong class="money">') .
        money($s['investido']) .
        '</strong></a><a class="portfolio-row" href="' .
        e(url('cartoes/index.php')) .
        '"><span class="movement-icon transfer">' .
        icon('cartoes') .
        ('</span>' .
            '<span class="portfolio-label">Cartões em aberto<small>Saldo utilizado</small>' .
            '</span>' .
            '<strong class="money">') .
        money($s['divida']) .
        '</strong></a></div>';
    echo '<div class="form-note" style="margin-bottom:0">Patrimônio líquido registrado<br>' .
        '<strong class="money" style="font-size:22px">' .
        money(dec($pat)) .
        '</strong><br>Contas + investimentos − saldo utilizado dos cartões</div></div>';
    echo '<div class="card full">' .
        '<div class="section-head">' .
        '<h2>Um pouco mais perto dos seus planos</h2>' .
        '<a class="text-link" href="' .
        e(url('objetivos/index.php')) .
        '">Meus objetivos ' .
        icon('arrow') .
        '</a></div>';
    $goals = rows(
        "SELECT * FROM objetivo_financeiro WHERE id_usuario=? AND status<>'Cancelado' ORDER BY data_limite IS NULL,data_limite LIMIT 3",
        [usuarioLogado()]
    );
    if (!$goals) {
        emptyState(
            'O que você quer conquistar?',
            'Defina seu primeiro objetivo e acompanhe cada avanço.',
            linkButton(url('objetivos/cadastrar.php'), 'Criar objetivo', 'btn-secondary')
        );
    } else {
        echo '<div class="grid grid-3">';
        foreach ($goals as $g) {
            goalCard($g, false);
        }
        echo '</div>';
    }
    echo '</div></section>';
    echo '<div class="welcome-note">' .
        '<div>' .
        '<strong>Pequenas escolhas. Grandes conquistas.</strong>Revisar suas finanças é um cuidado com o seu futuro.</div>' .
        '<a class="text-link" href="' .
        e(url('relatorios/index.php')) .
        '">Explorar relatórios ' .
        icon('arrow') .
        '</a></div>';
    echo '<script type="application/json" id="chart-data">' .
        json_encode(
            ['flow' => $series, 'categories' => $cat],
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        ) .
        '</script><script src="' .
        e(assetUrl('assets/vendor/chart.umd.min.js')) .
        '" defer></script><script src="' .
        e(assetUrl('assets/js/dashboard.js')) .
        '" defer></script>';
}

function operationForm(string $m, string $action, int $id, array $errors = []): void
{
    $r = owned($m === 'cartoes' ? 'cartao' : 'investimento', $id, false);
    $pay = $m === 'cartoes';
    $value = $pay
        ? dec(cents($r['limite_total']) - cents($r['limite_disponivel']))
        : dec(cents($r['valor_aplicado']) + cents($r['rendimento'], true));
    pageHead(
        $pay ? 'Quitar saldo do cartão' : 'Resgatar investimento',
        $pay ? $r['nome_cartao'] : $r['nome'],
        linkButton(url($m . '/index.php'), 'Voltar', 'btn-secondary')
    );
    echo '<div class="form-card">';
    foreach ($errors as $er) {
        echo '<div class="alert error" role="alert">' . e($er) . '</div>';
    }
    echo '<div class="amount-label">' .
        ($pay ? 'VALOR TOTAL A QUITAR' : 'VALOR A CREDITAR NA CONTA') .
        '</div><h1 class="money">' .
        money($value) .
        '</h1><p class="form-note">' .
        ($pay
            ? 'A quitação debita a conta escolhida e libera o limite utilizado. As compras continuam no histórico. O valor inclui eventual saldo utilizado anterior à atualização.'
            : 'O principal e o rendimento informado serão creditados na conta de origem. O investimento sairá da lista de aplicações ativas e ficará registrado no histórico de resgates.') .
        '</p><form method="post" data-confirm="' .
        ($pay
            ? 'Confirmar a quitação e debitar a conta?'
            : 'Confirmar o resgate e encerrar esta aplicação?') .
        '">';
    csrfField();
    formNonce();
    if ($pay) {
        echo '<input type="hidden" name="valor_confirmado" value="' . e($value) . '">';
        renderField(
            'id_conta',
            ['Conta de pagamento', 'select', 'conta'],
            $_POST['id_conta'] ?? $r['id_conta']
        );
    } else {
        echo '<p>Conta de destino: <strong>' .
            e(owned('conta', (int) $r['id_conta'], false)['nome_conta']) .
            '</strong></p>';
    }
    echo '<div style="margin-top:20px">';
    renderField('data', ['Data da operação', 'date', null], $_POST['data'] ?? date('Y-m-d'));
    echo '</div><div class="actions"><button class="btn" type="submit">' .
        ($pay ? 'Quitar saldo utilizado' : 'Confirmar resgate') .
        '</button></div></form></div>';
}

function notifications(): void
{
    pageHead('Notificações', 'As novidades da sua organização financeira.');
    echo '<div class="card"><div class="section-head"><h2>Central de avisos</h2>';
    postButton(url('notificacoes/ler_todas.php'), [], 'Marcar todas como lidas');
    echo '</div>';
    $page = max(1, (int) ($_GET['pagina'] ?? 1));
    $count = (int) scalar('SELECT COUNT(*) FROM notificacao WHERE id_usuario=?', [usuarioLogado()]);
    $pages = max(1, (int) ceil($count / 20));
    $page = min($page, $pages);
    $offset = ($page - 1) * 20;
    $r = rows(
        "SELECT * FROM notificacao WHERE id_usuario=? ORDER BY id_notificacao DESC LIMIT 20 OFFSET $offset",
        [usuarioLogado()]
    );
    foreach ($r as $n) {
        echo '<article class="notification ' .
            (!$n['data_leitura'] ? 'unread' : '') .
            '"><span class="movement-icon">' .
            icon('notificacoes') .
            '</span><div class="details"><p>' .
            e($n['mensagem']) .
            '</p><small>' .
            date('d/m/Y H:i', strtotime($n['data_envio'])) .
            '</small></div>';
        if (!$n['data_leitura']) {
            postButton(
                url('notificacoes/ler.php'),
                ['id' => $n['id_notificacao']],
                'Marcar como lida'
            );
        } else {
            echo '<span class="badge badge-neutral">Lida</span>';
        }
        echo '</article>';
    }
    if (!$r) {
        emptyState('Tudo em dia', 'Seus avisos aparecerão aqui.', '', 'notificacoes');
    }
    pagination(compact('count', 'page', 'pages'));
    echo '</div>';
}

function reportData(string $kind, string $i, string $f): array
{
    $u = usuarioLogado();
    if ($kind === 'categorias') {
        return [
            'headers' => ['Categoria', 'Lançamentos', 'Total'],
            'rows' => array_map(
                fn ($r) => [$r['categoria'], $r['quantidade'] ?? '—', $r['total']],
                categoryTotals($i, $f)
            ),
            'money' => [2]
        ];
    }
    if ($kind === 'receitas') {
        $r = rows(
            "SELECT r.descricao,c.nome_conta AS conta,t.nome AS tipo,r.data_recebimento AS data,r.valor FROM recebimento r JOIN conta c ON c.id_conta=r.id_conta JOIN tipo_recebimento t ON t.id_tipo_recebimento=r.id_tipo_recebimento WHERE r.id_usuario=? AND r.data_recebimento BETWEEN ? AND ? UNION ALL SELECT CONCAT('Rendimento: ',s.nome),c.nome_conta,'Rendimento realizado',s.data,s.rendimento FROM sc_resgate s JOIN conta c ON c.id_conta=s.id_conta WHERE s.id_usuario=? AND s.data BETWEEN ? AND ? AND s.rendimento>0 ORDER BY data DESC",
            [$u, $i, $f, $u, $i, $f]
        );
        return [
            'headers' => ['Descrição', 'Conta', 'Tipo', 'Data', 'Valor'],
            'rows' => array_map(
                fn ($r) => [$r['descricao'], $r['conta'], $r['tipo'], $r['data'], $r['valor']],
                $r
            ),
            'money' => [4],
            'dates' => [3]
        ];
    }
    if ($kind === 'saldo') {
        $r = rows(
            "SELECT r.descricao,c.nome_conta AS conta,'Recebimento' AS tipo,r.data_recebimento AS data,r.valor AS valor FROM recebimento r JOIN conta c ON c.id_conta=r.id_conta WHERE r.id_usuario=? AND r.data_recebimento BETWEEN ? AND ?
  UNION ALL SELECT d.descricao,c.nome_conta,'Despesa em conta',d.data,-d.valor FROM despesa d JOIN conta c ON c.id_conta=d.id_conta WHERE d.id_usuario=? AND d.data BETWEEN ? AND ?
  UNION ALL SELECT CONCAT('Quitação: ',ca.nome_cartao),c.nome_conta,'Quitação de cartão',p.data,-p.valor FROM sc_pagamento_cartao p JOIN conta c ON c.id_conta=p.id_conta JOIN cartao ca ON ca.id_cartao=p.id_cartao WHERE p.id_usuario=? AND p.data BETWEEN ? AND ?
  UNION ALL SELECT CONCAT('Aplicação: ',s.nome),c.nome_conta,'Investimento',s.data_aplicacao,-s.valor_aplicado FROM investimento s JOIN conta c ON c.id_conta=s.id_conta WHERE s.id_usuario=? AND s.data_aplicacao BETWEEN ? AND ?
  UNION ALL SELECT CONCAT('Aplicação: ',s.nome),c.nome_conta,'Investimento já resgatado',s.data_aplicacao,-s.principal FROM sc_resgate s JOIN conta c ON c.id_conta=s.id_conta WHERE s.id_usuario=? AND s.data_aplicacao BETWEEN ? AND ?
  UNION ALL SELECT CONCAT('Resgate: ',s.nome),c.nome_conta,'Resgate',s.data,s.principal+s.rendimento FROM sc_resgate s JOIN conta c ON c.id_conta=s.id_conta WHERE s.id_usuario=? AND s.data BETWEEN ? AND ? ORDER BY data DESC",
            [$u, $i, $f, $u, $i, $f, $u, $i, $f, $u, $i, $f, $u, $i, $f, $u, $i, $f]
        );
        return [
            'headers' => ['Descrição', 'Conta', 'Tipo', 'Data', 'Variação'],
            'rows' => array_map(
                fn ($r) => [$r['descricao'], $r['conta'], $r['tipo'], $r['data'], $r['valor']],
                $r
            ),
            'money' => [4],
            'dates' => [3]
        ];
    }
    $r = rows(
        "SELECT d.descricao,ct.nome AS categoria,COALESCE(ca.nome_cartao,c.nome_conta) AS origem,d.data,d.valor FROM despesa d JOIN categoria ct ON ct.id_categoria=d.id_categoria LEFT JOIN conta c ON c.id_conta=d.id_conta LEFT JOIN cartao ca ON ca.id_cartao=d.id_cartao WHERE d.id_usuario=? AND d.data BETWEEN ? AND ?
 UNION ALL SELECT CONCAT('Perda: ',s.nome),'Perda realizada em investimento',c.nome_conta,s.data,-s.rendimento FROM sc_resgate s JOIN conta c ON c.id_conta=s.id_conta WHERE s.id_usuario=? AND s.data BETWEEN ? AND ? AND s.rendimento<0 ORDER BY data DESC",
        [$u, $i, $f, $u, $i, $f]
    );
    return [
        'headers' => ['Descrição', 'Categoria', 'Conta / cartão', 'Data', 'Valor'],
        'rows' => array_map(
            fn ($r) => [$r['descricao'], $r['categoria'], $r['origem'], $r['data'], $r['valor']],
            $r
        ),
        'money' => [4],
        'dates' => [3]
    ];
}

function exportCSV(array $data, string $kind): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header(
        'Content-Disposition: attachment; filename="smartcash-' .
            $kind .
            '-' .
            date('Y-m-d') .
            '.csv"'
    );
    header('X-Content-Type-Options: nosniff');
    $f = fopen('php://output', 'w');
    fwrite($f, "\xEF\xBB\xBF");
    fputcsv($f, $data['headers'], ';', '"', '');
    foreach ($data['rows'] as $r) {
        foreach ($r as $k => &$v) {
            if (in_array($k, $data['money'])) {
                $v = number_format((float) $v, 2, ',', '');
            } else {
                if (in_array($k, $data['dates'] ?? [])) {
                    $v = formatDateBr($v);
                }
                if (preg_match('/^[\s]*[=+@\-\t\r]/u', (string) $v)) {
                    $v = "'" . $v;
                }
            }
        }
        unset($v);
        fputcsv($f, $r, ';', '"', '');
    }
    fclose($f);
    exit();
}

function reportNote(string $kind): string
{
    return match ($kind) {
        'despesas'
            => 'Inclui compras no cartão na data do lançamento e perdas realizadas em resgates. Quitar cartão não gera outra despesa.',
        'receitas'
            => 'Inclui recebimentos e rendimentos positivos realizados nos resgates. O principal resgatado não é receita.',
        'categorias'
            => 'Os totais incluem compras em conta, cartão e perdas realizadas em investimentos.',
        'saldo'
            => 'Variação do dinheiro em contas no período. Inclui aplicações, resgates e quitações; exclui compras a crédito ainda não quitadas e transferências internas. O saldo na data final é exibido no resumo; este relatório detalha as entradas e saídas de caixa do período.'
    };
}

function reportKinds(): array
{
    return [
        'despesas' => 'Despesas',
        'receitas' => 'Receitas',
        'categorias' => 'Categorias',
        'saldo' => 'Fluxo de caixa'
    ];
}

function reportSections(string $i, string $f): array
{
    $sections = [];
    foreach (reportKinds() as $kind => $label) {
        $sections[$kind] = reportData($kind, $i, $f);
    }
    return $sections;
}

function reportTable(
    string $kind,
    array $data,
    bool $preview = false,
    array $periodParams = []
): void {
    $sum = 0;
    foreach ($data['rows'] as $r) {
        $sum += cents((string) $r[array_last_index($data['headers'])], true);
    }
    echo '<div class="form-note">' .
        e(reportNote($kind)) .
        '</div><div class="table-card"><div class="table-toolbar"><h2>' .
        e(
            [
                'despesas' => 'Detalhamento de despesas',
                'receitas' => 'Entradas do período',
                'categorias' => 'Distribuição por categoria',
                'saldo' => 'Movimentações de caixa'
            ][$kind]
        ) .
        '</h2><strong class="money">' .
        money(dec($sum)) .
        '</strong></div><div class="table-scroll"><table><thead><tr>';
    foreach ($data['headers'] as $h) {
        echo '<th>' . e($h) . '</th>';
    }
    echo '</tr></thead><tbody>';
    $count = count($data['rows']);
    $pages = max(1, (int) ceil($count / 20));
    $page = $preview ? 1 : min($pages, max(1, (int) ($_GET['pagina'] ?? 1)));
    foreach (array_slice($data['rows'], ($page - 1) * 20, $preview ? 5 : 20) as $r) {
        echo '<tr>';
        foreach ($r as $key => $v) {
            echo '<td class="' .
                (in_array($key, $data['money']) ? 'money' : '') .
                '">' .
                (in_array($key, $data['money'])
                    ? money($v)
                    : (in_array($key, $data['dates'] ?? [])
                        ? formatDateBr($v)
                        : e($v))) .
                '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
    if (!$data['rows']) {
        emptyState(
            'Nenhuma movimentação no período',
            'Escolha outro período ou adicione seus primeiros lançamentos.',
            '',
            'relatorios'
        );
    }
    if ($preview) {
        echo '<div class="table-toolbar"><span class="muted small">' .
            min(5, $count) .
            ' de ' .
            $count .
            ' registros</span>' .
            linkButton(
                url('relatorios/' . $kind . '.php?' . http_build_query($periodParams)),
                'Ver relatório individual',
                'btn-secondary btn-small'
            ) .
            '</div>';
    } else {
        pagination(compact('count', 'page', 'pages'));
    }
    echo '</div>';
}

function reports(string $kind): void
{
    [$i, $f] = period();
    $complete = $kind === 'completo';
    $s = summary($i, $f);
    $periodParams =
        ($_GET['modo_periodo'] ?? '') === 'mes'
            ? [
                'modo_periodo' => 'mes',
                'mes' => (int) substr($i, 5, 2),
                'ano' => (int) substr($i, 0, 4)
            ]
            : ['inicio' => $i, 'fim' => $f];
    $pdfComplete = url(
        'relatorios/completo.php?' . http_build_query($periodParams + ['exportar' => 'pdf'])
    );
    $actions = $complete
        ? linkButton($pdfComplete, 'Exportar PDF completo', '', 'download')
        : linkButton(
            '?' . http_build_query($periodParams + ['exportar' => 'csv']),
            'Exportar CSV',
            'btn-secondary',
            'download'
        ) .
            linkButton(
                '?' . http_build_query($periodParams + ['exportar' => 'pdf']),
                'Exportar PDF',
                '',
                'download'
            ) .
            linkButton($pdfComplete, 'PDF completo', 'btn-secondary', 'download');
    pageHead(
        $complete ? 'Relatório completo' : 'Relatórios',
        $complete
            ? 'Quatro áreas, um só período e uma visão completa das suas finanças.'
            : 'Enxergue o caminho que seu dinheiro percorreu.',
        $actions
    );
    echo '<nav class="tabs" aria-label="Tipos de relatório">';
    foreach (reportKinds() + ['completo' => 'Completo'] as $key => $label) {
        echo '<a class="' .
            ($kind === $key ? 'active' : '') .
            '" href="' .
            e(url('relatorios/' . $key . '.php?' . http_build_query($periodParams))) .
            '">' .
            e($label) .
            '</a>';
    }
    echo '</nav>';
    echo '<div class="section-head"><span class="small muted">' .
        formatDateBr($i) .
        ' a ' .
        formatDateBr($f) .
        '</span>';
    periodForm($i, $f);
    echo '</div>';
    metrics($s);
    if ($complete) {
        echo '<p class="form-note">Despesas, receitas, categorias e fluxo de caixa no mesmo relatório. A tela mostra uma prévia de até 5 registros por área; o PDF completo inclui todos os resultados filtrados. Cada área tem seu próprio total, sem somá-los entre si.</p>';
        foreach (reportSections($i, $f) as $section => $data) {
            echo '<section aria-label="' .
                e(reportKinds()[$section]) .
                '" style="margin-bottom:28px">';
            reportTable($section, $data, true, $periodParams);
            echo '</section>';
        }
    } else {
        reportTable($kind, reportData($kind, $i, $f));
    }
    echo '<p class="muted small" style="margin-top:15px">' .
        ($complete
            ? 'O PDF completo reúne as quatro áreas e todos os registros do período filtrado.'
            : 'CSV e PDF incluem todos os registros do período filtrado.') .
        ' O PDF é gerado pelo sistema e baixado diretamente.</p>';
}

function array_last_index(array $a): int
{
    return count($a) - 1;
}

function profile(string $action, array $errors = []): void
{
    $r = one('SELECT * FROM usuario WHERE id_usuario=?', [usuarioLogado()]);
    pageHead(
        'Meu perfil',
        'Seu espaço pessoal no SmartCash.',
        linkButton(url('usuario/editar.php'), 'Editar perfil', 'btn-secondary') .
            linkButton(url('usuario/alterar_senha.php'), 'Alterar senha', 'btn-secondary')
    );
    if ($action === 'perfil') {
        echo '<div class="grid grid-2"><div class="card"><h2>Dados pessoais</h2><dl class="profile-details">';
        foreach (
            [
                'nome' => 'Nome',
                'email' => 'E-mail',
                'data_nascimento' => 'Nascimento',
                'telefone' => 'Telefone',
                'data_cadastro' => 'Membro desde'
            ] as $k => $label
        ) {
            echo '<div><dt>' .
                $label .
                '</dt><dd>' .
                (str_starts_with($k, 'data_')
                    ? formatDateBr($r[$k])
                    : e($r[$k] ?: 'Não informado')) .
                '</dd></div>';
        }
        echo '</dl></div><div class="card"><h2>Seu controle financeiro</h2><div class="grid grid-2">';
        foreach (
            [
                'conta' => 'Contas',
                'cartao' => 'Cartões',
                'investimento' => 'Investimentos',
                'objetivo_financeiro' => 'Objetivos'
            ] as $t => $label
        ) {
            echo '<div><span class="muted small">' .
                $label .
                '</span><h1>' .
                (int) scalar("SELECT COUNT(*) FROM $t WHERE id_usuario=?", [usuarioLogado()]) .
                '</h1></div>';
        }
        echo '</div></div></div>';
        return;
    }
    echo '<div class="form-card"><h2>' .
        ($action === 'alterar_senha' ? 'Alterar senha' : 'Editar dados pessoais') .
        '</h2>';
    foreach ($errors as $err) {
        echo '<div class="alert error" role="alert">' . e($err) . '</div>';
    }
    echo '<form method="post">';
    csrfField();
    formNonce();
    echo '<div class="form-grid">';
    if ($action === 'alterar_senha') {
        foreach (
            [
                'senha_atual' => 'Senha atual',
                'nova_senha' => 'Nova senha (mínimo de 8 caracteres)',
                'confirmacao' => 'Confirmar nova senha'
            ] as $key => $label
        ) {
            echo '<div class="field full"><label for="' .
                $key .
                '">' .
                $label .
                '</label><input id="' .
                $key .
                '" type="password" name="' .
                $key .
                '" required autocomplete="' .
                ($key === 'senha_atual' ? 'current-password' : 'new-password') .
                '" maxlength="72"></div>';
        }
    } else {
        foreach (
            [
                'nome' => ['Nome', 'text', 100],
                'email' => ['E-mail', 'text', 150],
                'data_nascimento' => ['Nascimento', 'date', null],
                'telefone' => ['Telefone (opcional)', 'optional', 20]
            ] as $key => $def
        ) {
            renderField($key, $def, $_POST[$key] ?? $r[$key]);
        }
        $checked =
            $_SERVER['REQUEST_METHOD'] === 'POST'
                ? isset($_POST['receber_email'])
                : $r['receber_email'];
        echo '<label class="checkbox full"><input type="checkbox" name="receber_email" value="1"' .
            ($checked ? ' checked' : '') .
            '> Receber comunicações por e-mail</label>';
    }
    echo '</div><div class="actions"><button class="btn" type="submit">Salvar alterações</button>' .
        linkButton(url('usuario/perfil.php'), 'Cancelar', 'btn-secondary') .
        '</div></form></div>';
}

function saveProfile(string $action): void
{
    if ($action === 'alterar_senha') {
        $r = one('SELECT senha FROM usuario WHERE id_usuario=? FOR UPDATE', [usuarioLogado()]);
        $new = $_POST['nova_senha'] ?? '';
        if (!password_verify((string) ($_POST['senha_atual'] ?? ''), $r['senha'])) {
            throw new DomainException('Senha atual incorreta.');
        }
        if (
            !is_string($new) ||
            strlen($new) < 8 ||
            strlen($new) > 72 ||
            $new !== ($_POST['confirmacao'] ?? '')
        ) {
            throw new DomainException(
                'A nova senha deve ter de 8 a 72 bytes e coincidir com a confirmação.'
            );
        }
        if (password_verify($new, $r['senha'])) {
            throw new DomainException('Use uma senha diferente da atual.');
        }
        $hash = password_hash($new, PASSWORD_DEFAULT);
        query('UPDATE usuario SET senha=? WHERE id_usuario=?', [$hash, usuarioLogado()]);
        $_SESSION['auth_fingerprint'] = hash('sha256', $hash);
        session_regenerate_id(true);
        notify('Sua senha foi alterada.');
        return;
    }
    $name = textValue('nome', 100);
    $email = textValue('email', 150);
    $birth = dateValue('data_nascimento');
    $phone = phoneValue();
    if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new DomainException('Informe nome e e-mail válidos.');
    }
    if (
        one('SELECT id_usuario FROM usuario WHERE email=? AND id_usuario<>?', [
            $email,
            usuarioLogado()
        ])
    ) {
        throw new DomainException('Este e-mail já está em uso.');
    }
    query(
        'UPDATE usuario SET nome=?,email=?,data_nascimento=?,telefone=?,receber_email=? WHERE id_usuario=?',
        [$name, $email, $birth, $phone, isset($_POST['receber_email']) ? 1 : 0, usuarioLogado()]
    );
    $_SESSION['nome_usuario'] = $name;
}
