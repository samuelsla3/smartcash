<?php

declare(strict_types=1);
function pageHead(string $title, string $desc, string $actions = ''): void
{
    echo '<div class="page-header"><div><span class="eyebrow">SEU DINHEIRO, COM MAIS CLAREZA</span><h1>' .
        e($title) .
        '</h1><p class="muted">' .
        e($desc) .
        '</p></div><div class="page-actions">' .
        $actions .
        '</div></div>';
}

function linkButton(string $href, string $text, string $style = '', string $ic = ''): string
{
    return '<a class="btn ' .
        e($style) .
        '" href="' .
        e($href) .
        '">' .
        ($ic ? icon($ic) : '') .
        e($text) .
        '</a>';
}

function emptyState(
    string $title,
    string $message,
    string $action = '',
    string $ic = 'objetivos'
): void {
    echo '<div class="empty"><span class="empty-symbol">' .
        icon($ic) .
        '</span><h3>' .
        e($title) .
        '</h3><p>' .
        e($message) .
        '</p>' .
        $action .
        '</div>';
}

function postButton(
    string $action,
    array $values,
    string $text,
    string $confirm = '',
    string $style = 'btn-secondary btn-small'
): void {
    echo '<form class="inline-form" method="post" action="' .
        e($action) .
        '"' .
        ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') .
        '>';
    csrfField();
    formNonce();
    foreach ($values as $k => $v) {
        echo '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    }
    echo '<button class="btn ' . e($style) . '" type="submit">' . e($text) . '</button></form>';
}

function metric(
    string $label,
    string $value,
    string $hint = '',
    string $class = '',
    string $ic = 'contas'
): void {
    echo '<div class="metric-card ' .
        e($class) .
        '"><div class="label">' .
        e($label) .
        icon($ic) .
        '</div><div class="value money">' .
        money($value) .
        '</div><div class="hint">' .
        e($hint) .
        '</div></div>';
}

function metrics(array $s): void
{
    echo '<section class="metric-grid" aria-label="Resumo financeiro">';
    metric(
        'Saldo no fim do período',
        $s['saldo_total'],
        'Até ' . formatDateBr($s['fim_periodo']) . ' • todas as contas',
        'featured'
    );
    metric(
        'Receitas do período',
        $s['receitas'],
        'Entradas + rendimentos realizados',
        '',
        'recebimentos'
    );
    metric('Despesas do período', $s['despesas'], 'Compras + perdas realizadas', '', 'despesas');
    metric('Resultado do período', $s['resultado'], 'Receitas menos despesas', '', 'investimentos');
    echo '</section>';
}

function periodControls(string $i, string $f, bool $allowAll = false): void
{
    $mode =
        $_GET['modo_periodo'] ??
        ($allowAll && empty($_GET['inicio']) && empty($_GET['fim']) ? 'todos' : 'datas');
    $month = (int) ($_GET['mes'] ?? substr($i ?: date('Y-m-d'), 5, 2));
    $year = (int) ($_GET['ano'] ?? substr($i ?: date('Y-m-d'), 0, 4));
    echo '<div class="period-mode field">' .
        '<label for="period-mode">Período</label>' .
        '<select id="period-mode" name="modo_periodo" data-period-mode>';
    foreach (
        array_filter([
            'todos' => $allowAll ? 'Todos os períodos' : null,
            'mes' => 'Por mês',
            'datas' => 'Entre datas'
        ]) as $key => $label
    ) {
        echo '<option value="' .
            $key .
            '"' .
            ($mode === $key ? ' selected' : '') .
            '>' .
            $label .
            '</option>';
    }
    echo '</select>' .
        '</div>' .
        '<div class="period-month-group" data-period-group="mes">' .
        '<div class="field">' .
        '<label for="period-month">Mês</label>' .
        '<select id="period-month" name="mes">';
    foreach (
        [
            1 => 'Janeiro',
            'Fevereiro',
            'Março',
            'Abril',
            'Maio',
            'Junho',
            'Julho',
            'Agosto',
            'Setembro',
            'Outubro',
            'Novembro',
            'Dezembro'
        ] as $n => $name
    ) {
        echo '<option value="' .
            $n .
            '"' .
            ($month === $n ? ' selected' : '') .
            '>' .
            $name .
            '</option>';
    }
    echo '</select>' .
        '</div>' .
        '<div class="field">' .
        '<label for="period-year">Ano</label>' .
        '<input id="period-year" name="ano" type="number" min="1900" max="2100" value="' .
        $year .
        '"></div></div><div class="period-date-group" data-period-group="datas">';
    foreach (['inicio' => ['Início', $i], 'fim' => ['Até', $f]] as $key => [$label, $v]) {
        echo '<div class="field"><label for="period-' .
            $key .
            '">' .
            $label .
            '</label><input id="period-' .
            $key .
            '" type="date" name="' .
            $key .
            '" value="' .
            e($v) .
            '" required></div>';
    }
    echo '</div>';
}

function periodForm(string $i, string $f): void
{
    echo '<form class="period-filter" method="get" data-period-form>';
    periodControls($i, $f);
    echo '<button class="btn btn-secondary" type="submit">Filtrar</button></form>';
}

function fieldOptions(string|array $source, mixed $selected, string $key, int $exclude = 0): void
{
    if (!is_scalar($selected)) {
        $selected = '';
    }
    if (is_array($source)) {
        foreach ($source as $id => $name) {
            echo '<option value="' .
                e($id) .
                '"' .
                ((string) $selected === (string) $id ? ' selected' : '') .
                '>' .
                e($name) .
                '</option>';
        }
        return;
    }
    $items = match ($source) {
        'conta' => rows(
            'SELECT c.id_conta AS id,c.nome_conta AS label,c.saldo_atual,b.nome AS banco FROM conta c JOIN banco b ON b.id_banco=c.id_banco WHERE c.id_usuario=? ORDER BY ' .
                accountOrder('c'),
            [usuarioLogado()]
        ),
        'cartao' => rows(
            'SELECT id_cartao AS id,nome_cartao AS label,limite_disponivel FROM cartao WHERE id_usuario=? ORDER BY nome_cartao',
            [usuarioLogado()]
        ),
        'categoria' => array_map(
            fn ($r) => [
                'id' => $r['id_categoria'],
                'label' => $r['nome'] . ($r['id_categoria_pai'] ? ' · subcategoria' : '')
            ],
            categories()
        ),
        default => rows("SELECT id_$source AS id,nome AS label FROM $source ORDER BY nome")
    };
    foreach ($items as $r) {
        if ((int) $r['id'] === $exclude) {
            continue;
        }
        $extra = isset($r['saldo_atual'])
            ? ' · ' . money($r['saldo_atual'])
            : (isset($r['limite_disponivel'])
                ? ' · disponível ' . money($r['limite_disponivel'])
                : '');
        echo '<option' .
            (isset($r['banco']) ? ' data-bank="' . e($r['banco']) . '"' : '') .
            ' value="' .
            (int) $r['id'] .
            '"' .
            ((string) $selected === (string) $r['id'] ? ' selected' : '') .
            '>' .
            e($r['label'] . $extra) .
            '</option>';
    }
}

function renderField(string $key, array $def, mixed $value, int $id = 0): void
{
    [$label, $type, $opt] = $def;
    $optional = in_array($type, ['optional', 'deadline', 'parent'], true);
    echo '<div class="field" data-field="' .
        e($key) .
        '"><label for="f-' .
        e($key) .
        '">' .
        e($label) .
        '</label>';
    if ($type === 'select' || $type === 'parent') {
        echo '<select id="f-' .
            e($key) .
            '" name="' .
            e($key) .
            '"' .
            (!$optional ? ' required' : '') .
            '><option value="">' .
            ($optional ? 'Nenhuma' : 'Selecione') .
            '</option>';
        fieldOptions($opt, $value, $key, $key === 'id_categoria_pai' ? $id : 0);
        echo '</select>';
    } else {
        $inputType = match ($type) {
            'date', 'deadline' => 'date',
            'day' => 'number',
            default => 'text'
        };
        echo '<input id="f-' .
            e($key) .
            '" name="' .
            e($key) .
            '" type="' .
            $inputType .
            '" value="' .
            e($value) .
            '"' .
            (!$optional ? ' required' : '');
        if (in_array($type, ['money', 'signed'])) {
            echo ' data-money' .
                ($type === 'signed' ? ' data-signed' : '') .
                ' inputmode="decimal" placeholder="0,00" maxlength="22"';
        }
        if (in_array($type, ['text', 'optional'])) {
            echo ' maxlength="' . (int) $opt . '"';
        }
        if ($type === 'day') {
            echo ' min="1" max="31"';
        }
        if ($type === 'date') {
            echo ' max="' . date('Y-m-d') . '"';
        }
        echo '>';
    }
    echo '</div>';
}

function recordForm(string $m, int $id, array $item, array $errors = []): void
{
    $cfg = modules()[$m];
    pageHead(
        ($id
            ? 'Editar '
            : (in_array($m, ['contas', 'despesas', 'transferencias', 'categorias'], true)
                ? 'Nova '
                : 'Novo ')) . $cfg['singular'],
        'Os saldos são atualizados ao salvar.',
        linkButton(url($m . '/index.php'), 'Voltar', 'btn-secondary')
    );
    echo '<div class="form-card">';
    foreach ($errors as $er) {
        echo '<div class="alert error" role="alert">' . e($er) . '</div>';
    }
    $notes = match ($m) {
        'contas' => $id
            ? 'O saldo atual é calculado pelas movimentações. Edite as movimentações para corrigir o saldo.'
            : 'Informe o saldo disponível antes de começar os lançamentos. O saldo inicial não é uma receita.',
        'despesas'
            => 'No cartão, a compra consome limite. Na conta, o valor sai imediatamente. Uma compra quitada precisa ter a quitação estornada antes de ser alterada.',
        'recebimentos'
            => 'Registre aqui valores já recebidos. Transferências entre suas próprias contas têm uma área própria.',
        'cartoes'
            => 'O limite disponível é calculado pelas compras e quitações. Alterar o limite total preserva o valor já utilizado. O vencimento funciona como referência; não há fechamento automático por mês.',
        'investimentos'
            => 'Aplicar retira o principal da conta. O rendimento é acumulado e informado em reais, podendo ser negativo. Para devolver principal e rendimento à conta, use Resgatar.',
        'objetivos'
            => 'O valor reservado é um acompanhamento manual. Ele não debita suas contas nem representa dinheiro adicional no patrimônio. A conclusão é calculada pelo valor alcançado.',
        'categorias'
            => 'Categorias do catálogo são compartilhadas e somente leitura. As novas categorias são pessoais. A categoria principal pode ser do catálogo ou sua.',
        default
        => 'Transferências mudam os saldos das contas e não entram como receita ou despesa. O estorno exige saldo na conta de destino.'
    };
    echo '<p class="form-note">' .
        e($notes) .
        '</p><form method="post" action="' .
        e(url($m . '/' . ($id ? 'editar.php?id=' . $id : 'cadastrar.php'))) .
        '">';
    csrfField();
    formNonce();
    echo '<div class="form-grid">';
    foreach ($cfg['fields'] as $key => $def) {
        if ($m === 'contas' && $id && $key === 'saldo_atual') {
            continue;
        }
        $default = match ($def[1]) {
            'date' => date('Y-m-d'),
            'signed' => '0',
            default => ''
        };
        if (in_array($key, ['saldo_atual', 'valor_atual'])) {
            $default = '0';
        }
        if ($key === 'status') {
            $default = 'Em andamento';
        }
        if ($key === 'forma_pagamento') {
            $default = empty($item['id_cartao']) ? 'conta' : 'cartao';
        }
        $v = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST[$key] ?? '' : $item[$key] ?? $default;
        renderField($key, $def, $v, $id);
    }
    echo '</div>';
    if ($m === 'contas') {
        $meta = $id
            ? one('SELECT * FROM sc_conta_meta WHERE id_conta=? AND id_usuario=?', [
                $id,
                usuarioLogado()
            ])
            : null;
        echo '<div class="form-grid account-options">';
        renderField(
            'data_abertura',
            ['Data do saldo inicial', 'date', null],
            $_POST['data_abertura'] ?? ($meta['data_abertura'] ?? date('Y-m-d'))
        );
        $main =
            $_SERVER['REQUEST_METHOD'] === 'POST'
                ? isset($_POST['principal'])
                : !empty($meta['principal']);
        echo '<label class="checkbox"><input type="checkbox" name="principal" value="1"' .
            ($main ? ' checked' : '') .
            '> Usar como conta principal</label></div>';
        if (!empty($meta['origem_legada'])) {
            echo '<p class="muted small">A data e o saldo inicial desta conta antiga foram estimados com os registros existentes. Confira a data do saldo inicial.</p>';
        }
    }
    if ($m === 'cartoes') {
        echo '<p class="muted small" data-bank-preview>A instituição será a mesma da conta associada.</p>';
    }
    if (in_array($m, ['despesas', 'recebimentos'])) {
        $next = $m === 'despesas' ? 'proxima_cobranca' : 'proximo_recebimento';
        $checked =
            $_SERVER['REQUEST_METHOD'] === 'POST'
                ? isset($_POST['recorrente'])
                : !empty($item['recorrente']);
        echo '<fieldset>' .
            '<legend>Próximas ocorrências</legend>' .
            '<label class="checkbox">' .
            '<input type="checkbox" name="recorrente" value="1"' .
            ($checked ? ' checked' : '') .
            ('> Repetir este lançamento</label>' .
                '<p class="muted small">Cria um lembrete. Cada ocorrência só altera o saldo após sua confirmação.</p>' .
                '<div id="recurring-fields" class="form-grid">');
        renderField(
            'frequencia',
            [
                'Frequência',
                'select',
                [
                    'diaria' => 'Diária',
                    'semanal' => 'Semanal',
                    'mensal' => 'Mensal',
                    'anual' => 'Anual'
                ]
            ],
            $_POST['frequencia'] ?? ($item['frequencia'] ?? 'mensal')
        );
        renderField(
            $next,
            ['Próxima data', 'deadline', null],
            $_POST[$next] ?? ($item[$next] ?? date('Y-m-d', strtotime('+1 month')))
        );
        echo '</div></fieldset>';
    }
    echo '<div class="actions"><button class="btn" type="submit">' .
        icon('check') .
        'Salvar ' .
        e($cfg['singular']) .
        '</button>' .
        linkButton(url($m . '/index.php'), 'Cancelar', 'btn-secondary') .
        '</div></form></div>';
}

function rowActions(string $m, array $r): void
{
    $cfg = modules()[$m];
    $id = (int) $r[$cfg['pk']];
    if ($m === 'categorias' && empty($r['owner'])) {
        echo '<span class="badge badge-neutral">Catálogo</span>';
        return;
    }
    if ($m === 'despesas' && !empty($r['id_pagamento'])) {
        echo '<span class="badge">Quitada</span>';
        return;
    }
    echo '<div class="row-actions">' .
        linkButton(url($m . '/editar.php?id=' . $id), 'Editar', 'btn-secondary btn-small');
    if ($m === 'investimentos') {
        echo linkButton(url($m . '/resgatar.php?id=' . $id), 'Resgatar', 'btn-secondary btn-small');
    }
    $confirm = match ($m) {
        'investimentos'
            => 'Estornar a aplicação? Apenas o principal será devolvido à conta; rendimentos não serão realizados.',
        'recebimentos' => 'Excluir esta entrada e retirar o valor da conta?',
        'despesas' => 'Excluir esta despesa e restaurar o saldo ou limite?',
        'transferencias' => 'Estornar esta transferência? O valor será devolvido à origem.',
        default => 'Excluir este registro? Esta ação não pode ser desfeita.'
    };
    postButton(
        url($m . '/excluir.php'),
        ['id' => $id],
        $m === 'transferencias' ? 'Estornar' : 'Excluir',
        $confirm,
        'btn-danger btn-small'
    );
    echo '</div>';
}

function listFilters(string $m): void
{
    echo '<form class="filter-bar" method="get" data-live-filter data-period-form>' .
        '<div class="filter-field">' .
        '<label for="filter-q">Buscar</label>' .
        '<input id="filter-q" name="q" placeholder="Buscar por descrição..." value="' .
        e($_GET['q'] ?? '') .
        '"></div>';
    if (in_array($m, ['recebimentos', 'despesas', 'transferencias', 'investimentos'])) {
        if (
            ($_GET['modo_periodo'] ?? '') === 'mes' ||
            !empty($_GET['inicio']) ||
            !empty($_GET['fim'])
        ) {
            [$i, $f] = period();
        } else {
            [$i, $f] = [date('Y-m-01'), date('Y-m-t')];
        }
        periodControls($i, $f, true);
    }
    if ($m === 'despesas') {
        echo '<div class="filter-field">' .
            '<label for="filter-category">Categoria</label>' .
            '<select id="filter-category" name="categoria">' .
            '<option value="">Todas</option>';
        fieldOptions('categoria', $_GET['categoria'] ?? '', '');
        echo '</select></div>';
    }
    if (in_array($m, ['despesas', 'recebimentos', 'investimentos'])) {
        echo '<div class="filter-field">' .
            '<label for="filter-account">Conta</label>' .
            '<select id="filter-account" name="conta">' .
            '<option value="">Todas</option>';
        fieldOptions('conta', $_GET['conta'] ?? '', '');
        echo '</select></div>';
    }
    echo '<button class="btn btn-secondary" type="submit">' .
        icon('search') .
        'Filtrar</button><a class="text-link" href="' .
        e(url($m . '/index.php')) .
        '">Limpar</a></form>';
}

function recordList(string $m): void
{
    $cfg = modules()[$m];
    $l = listData($m);
    pageHead(
        $cfg['title'],
        $cfg['desc'],
        linkButton(url($m . '/cadastrar.php'), 'Adicionar ' . $cfg['singular'], '', 'plus')
    );
    if ($m === 'cartoes') {
        cardList($l);
        return;
    }
    if ($m === 'objetivos') {
        goalList($l);
        return;
    }
    if ($m === 'contas') {
        $s = summary(date('Y-m-01'), date('Y-m-t'));
        echo '<div class="grid grid-3" style="margin-bottom:24px">';
        metric('Disponível em contas', $s['saldo_atual'], 'Dinheiro disponível agora', 'featured');
        metric(
            'Investido',
            $s['investido'],
            'Principal + rendimento acumulado',
            '',
            'investimentos'
        );
        metric('Saldo utilizado dos cartões', $s['divida'], 'Valor a quitar', '', 'cartoes');
        echo '</div>';
    }
    echo '<div class="table-card"><div class="table-toolbar"><h2>' .
        e($cfg['title']) .
        ' <span class="badge badge-neutral">' .
        $l['count'] .
        '</span></h2>' .
        ($l['total'] !== null
            ? '<span class="small muted">Total filtrado: <strong class="money">' .
                money($l['total']) .
                '</strong></span>'
            : '') .
        '</div>';
    listFilters($m);
    $heads = match ($m) {
        'contas' => ['Conta', 'Instituição / tipo', 'Saldo atual', 'Ações'],
        'recebimentos' => ['Descrição', 'Conta / tipo', 'Data', 'Valor', 'Ações'],
        'despesas' => ['Descrição', 'Categoria', 'Pagamento', 'Data', 'Valor', 'Ações'],
        'transferencias' => ['Descrição', 'Origem → destino', 'Data', 'Valor', 'Ações'],
        'investimentos' => [
            'Aplicação',
            'Conta / tipo',
            'Aplicado',
            'Rendimento',
            'Valor atual',
            'Ações'
        ],
        'categorias' => ['Nome', 'Descrição', 'Categoria principal', 'Ações'],
        default => []
    };
    echo '<div class="table-scroll"><table><thead><tr>';
    foreach ($heads as $h) {
        echo '<th>' . e($h) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($l['data'] as $r) {
        echo '<tr>';
        switch ($m) {
            case 'contas':
                echo '<td class="primary">' .
                    e($r['nome_conta']) .
                    (scalar('SELECT principal FROM sc_conta_meta WHERE id_conta=?', [
                        $r['id_conta']
                    ])
                        ? ' <span class="badge">Principal</span>'
                        : '') .
                    '<small>' .
                    e($r['num_conta'] ? 'Conta ' . $r['num_conta'] : '') .
                    '</small></td><td>' .
                    e($r['banco']) .
                    '<small>' .
                    e($r['tipo']) .
                    '</small></td><td class="money">' .
                    money($r['saldo_atual']) .
                    '</td>';
                break;
            case 'recebimentos':
                echo '<td class="primary">' . e($r['descricao']);
                recurrenceBadge($m, $r);
                echo '</td><td>' .
                    e($r['nome_conta']) .
                    '<small>' .
                    e($r['tipo']) .
                    '</small></td><td>' .
                    formatDateBr($r['data_recebimento']) .
                    '</td><td class="money positive">+ ' .
                    money($r['valor']) .
                    '</td>';
                break;
            case 'despesas':
                echo '<td class="primary">' . e($r['descricao']);
                recurrenceBadge($m, $r);
                echo '</td><td><span class="badge badge-neutral">' .
                    e($r['categoria']) .
                    '</span></td><td>' .
                    e($r['nome_cartao'] ?: $r['nome_conta']) .
                    '<small>' .
                    ($r['id_cartao'] ? 'Crédito' : 'Débito em conta') .
                    '</small></td><td>' .
                    formatDateBr($r['data']) .
                    '</td><td class="money negative">− ' .
                    money($r['valor']) .
                    '</td>';
                break;
            case 'transferencias':
                echo '<td class="primary">' .
                    e($r['descricao'] ?: 'Transferência') .
                    '</td><td>' .
                    e($r['origem']) .
                    ' → ' .
                    e($r['destino']) .
                    '</td><td>' .
                    formatDateBr($r['data_transferencia']) .
                    '</td><td class="money">' .
                    money($r['valor']) .
                    '</td>';
                break;
            case 'investimentos':
                echo '<td class="primary">' .
                    e($r['nome']) .
                    '<small>Aplicado em ' .
                    formatDateBr($r['data_aplicacao']) .
                    '</small></td><td>' .
                    e($r['nome_conta']) .
                    '<small>' .
                    e($r['tipo']) .
                    '</small></td><td class="money">' .
                    money($r['valor_aplicado']) .
                    '</td><td class="money ' .
                    (cents($r['rendimento'], true) < 0 ? 'negative' : 'positive') .
                    '">' .
                    money($r['rendimento']) .
                    '</td><td class="money">' .
                    money(dec(cents($r['valor_aplicado']) + cents($r['rendimento'], true))) .
                    '</td>';
                break;
            case 'categorias':
                echo '<td class="primary">' .
                    e($r['nome']) .
                    '</td><td>' .
                    e($r['descricao'] ?: '—') .
                    '</td><td>' .
                    e($r['parent_name'] ?: 'Principal') .
                    '</td>';
                break;
        }
        echo '<td>';
        rowActions($m, $r);
        echo '</td></tr>';
    }
    echo '</tbody></table></div>';
    if (!$l['data']) {
        emptyState(
            'Nada por aqui ainda',
            'Adicione seu primeiro registro ou ajuste os filtros.',
            linkButton(
                url($m . '/cadastrar.php'),
                'Adicionar ' . $cfg['singular'],
                'btn-secondary'
            ),
            $m
        );
    }
    pagination($l);
    echo '</div>';
    if ($m === 'investimentos') {
        redemptionHistory();
    }
}

function pagination(array $l): void
{
    if ($l['count'] <= 20) {
        return;
    }
    echo '<div class="pagination"><span>Página ' .
        $l['page'] .
        ' de ' .
        $l['pages'] .
        ' · ' .
        $l['count'] .
        ' registros</span><div class="row-actions">';
    foreach ([-1 => 'Anterior', 1 => 'Próxima'] as $step => $label) {
        $n = $l['page'] + $step;
        if ($n >= 1 && $n <= $l['pages']) {
            echo linkButton(
                '?' . http_build_query(array_merge($_GET, ['pagina' => $n])),
                $label,
                'btn-secondary btn-small'
            );
        }
    }
    echo '</div></div>';
}

function recurrenceBadge(string $m, array $r): void
{
    if (!$r['recorrente']) {
        return;
    }
    $next = $m === 'despesas' ? 'proxima_cobranca' : 'proximo_recebimento';
    echo '<small>Recorrente · próxima: ' . formatDateBr($r[$next]) . '</small>';
    if ($r[$next] && $r[$next] <= date('Y-m-d')) {
        postButton(
            url($m . '/confirmar.php'),
            ['id' => $r[modules()[$m]['pk']], 'ocorrencia' => $r[$next]],
            'Confirmar próxima',
            'Registrar a ocorrência de ' . formatDateBr($r[$next]) . ' e atualizar o saldo?'
        );
    }
}

function goalCard(array $r, bool $actions = true): void
{
    $pct = min(100, max(0, (cents($r['valor_atual']) / max(1, cents($r['valor_objetivo']))) * 100));
    echo '<div class="goal-card"><div class="section-head" style="margin-bottom:5px"><h3>' .
        e($r['descricao']) .
        '</h3><span class="badge ' .
        ($r['status'] === 'Cancelado' ? 'badge-neutral' : '') .
        '">' .
        e($r['status']) .
        '</span></div><span class="muted">' .
        ($r['data_limite'] ? 'Até ' . formatDateBr($r['data_limite']) : 'No seu tempo') .
        '</span><div class="progress" role="progressbar" aria-label="Progresso de ' .
        e($r['descricao']) .
        '" aria-valuenow="' .
        round($pct) .
        '" aria-valuemin="0" aria-valuemax="100"><span style="width:' .
        $pct .
        '%"></span></div><div class="goal-value"><span><strong class="money">' .
        money($r['valor_atual']) .
        '</strong> de <span class="money">' .
        money($r['valor_objetivo']) .
        '</span></span><span>' .
        round($pct) .
        '%</span></div>';
    if ($actions) {
        echo '<div style="margin-top:20px">';
        rowActions('objetivos', $r);
        echo '</div>';
    }
    echo '</div>';
}

function goalList(array $l): void
{
    echo '<p class="muted small">Reservas manuais para acompanhar seus planos. Os valores não são somados ao patrimônio.</p>' .
        '<div class="grid grid-3">';
    foreach ($l['data'] as $r) {
        echo '<div class="card">';
        goalCard($r);
        echo '</div>';
    }
    if (!$l['data']) {
        emptyState(
            'Qual é sua próxima conquista?',
            'Defina uma meta e acompanhe seu progresso.',
            linkButton(url('objetivos/cadastrar.php'), 'Criar objetivo')
        );
    }
    echo '</div>';
    pagination($l);
}

function cardList(array $l): void
{
    echo '<div class="grid grid-3">';
    foreach ($l['data'] as $r) {
        $used = cents($r['limite_total']) - cents($r['limite_disponivel']);
        $pct = min(100, ($used / max(1, cents($r['limite_total']))) * 100);
        echo '<article class="card credit-card">' .
            '<div class="card-visual">' .
            '<div class="section-head" style="margin-bottom:0">' .
            '<h3>' .
            e($r['nome_cartao']) .
            '</h3>' .
            icon('cartoes') .
            '</div><small>' .
            e($r['banco']) .
            ' · vence dia ' .
            (int) $r['dia_vencimento'] .
            '</small><div class="card-limit"><div><small>Limite disponível</small><strong class="money">' .
            money($r['limite_disponivel']) .
            '</strong></div><div><small>Limite total</small><strong class="money">' .
            money($r['limite_total']) .
            '</strong></div></div><div class="progress"><span style="width:' .
            $pct .
            ('%">' .
                '</span>' .
                '</div>' .
                '</div>' .
                '<div class="card-body">' .
                '<span class="amount-label">SALDO UTILIZADO</span>' .
                '<h2 class="money" style="margin:4px 0 15px">') .
            money(dec($used)) .
            '</h2><div class="muted small">Conta padrão: ' .
            e($r['nome_conta']) .
            '</div><div class="row-actions">' .
            linkButton(
                url('cartoes/quitar.php?id=' . $r['id_cartao']),
                'Quitar saldo',
                'btn-small'
            );
        rowActions('cartoes', $r);
        echo '</div></div></article>';
    }
    echo '</div>';
    if (!$l['data']) {
        emptyState(
            'Todos os seus cartões, juntos',
            'Cadastre um cartão para organizar compras e limites.',
            linkButton(url('cartoes/cadastrar.php'), 'Adicionar cartão')
        );
    }
    pagination($l);
    echo '<div class="form-note">As compras são despesas na data do lançamento. A quitação sai da conta e libera limite, sem contar a despesa novamente. O controle é do saldo total em aberto, sem fechamento de faturas mensais ou parcelamento automático.</div>';
    $payments = rows(
        'SELECT p.*,c.nome_cartao,a.nome_conta FROM sc_pagamento_cartao p JOIN cartao c ON c.id_cartao=p.id_cartao JOIN conta a ON a.id_conta=p.id_conta WHERE p.id_usuario=? ORDER BY p.id_pagamento DESC LIMIT 30',
        [usuarioLogado()]
    );
    echo '<div class="table-card" style="margin-top:25px">' .
        '<div class="table-toolbar">' .
        '<h2>Últimas quitações</h2>' .
        '</div>' .
        '<div class="table-scroll">' .
        '<table>' .
        '<thead>' .
        '<tr>' .
        '<th>Cartão</th>' .
        '<th>Conta</th>' .
        '<th>Data</th>' .
        '<th>Valor</th>' .
        '<th>Ação</th>' .
        '</tr>' .
        '</thead>' .
        '<tbody>';
    foreach ($payments as $r) {
        echo '<tr><td>' .
            e($r['nome_cartao']) .
            '</td><td>' .
            e($r['nome_conta']) .
            '</td><td>' .
            formatDateBr($r['data']) .
            '</td><td class="money">' .
            money($r['valor']) .
            '</td><td>';
        postButton(
            url('cartoes/estornar.php'),
            ['id' => $r['id_pagamento']],
            'Estornar',
            'Devolver este pagamento à conta e consumir novamente o limite? A ação exige limite disponível suficiente.',
            'btn-danger btn-small'
        );
        echo '</td></tr>';
    }
    echo '</tbody></table></div>';
    if (!$payments) {
        emptyState(
            'Nenhuma quitação registrada',
            'O histórico aparecerá quando você quitar um cartão.',
            '',
            'cartoes'
        );
    }
    echo '</div>';
}

function redemptionHistory(): void
{
    $r = rows(
        'SELECT s.*,c.nome_conta FROM sc_resgate s JOIN conta c ON c.id_conta=s.id_conta WHERE s.id_usuario=? ORDER BY s.id_resgate DESC LIMIT 30',
        [usuarioLogado()]
    );
    if (!$r) {
        return;
    }
    echo '<div class="table-card" style="margin-top:24px">' .
        '<div class="table-toolbar">' .
        '<h2>Últimos resgates realizados</h2>' .
        '</div>' .
        '<div class="table-scroll">' .
        '<table>' .
        '<thead>' .
        '<tr>' .
        '<th>Aplicação</th>' .
        '<th>Conta</th>' .
        '<th>Principal</th>' .
        '<th>Rendimento</th>' .
        '<th>Creditado</th>' .
        '<th>Data</th>' .
        '<th>Ação</th>' .
        '</tr>' .
        '</thead>' .
        '<tbody>';
    foreach ($r as $v) {
        echo '<tr><td>' .
            e($v['nome']) .
            '</td><td>' .
            e($v['nome_conta']) .
            '</td><td class="money">' .
            money($v['principal']) .
            '</td><td class="money">' .
            money($v['rendimento']) .
            '</td><td class="money">' .
            money(dec(cents($v['principal']) + cents($v['rendimento'], true))) .
            '</td><td>' .
            formatDateBr($v['data']) .
            '</td><td>';
        postButton(
            url('investimentos/estornar_resgate.php'),
            ['id' => $v['id_resgate']],
            'Estornar',
            'Retirar o valor creditado da conta e restaurar esta aplicação?',
            'btn-danger btn-small'
        );
        echo '</td></tr>';
    }
    echo '</tbody></table></div></div>';
}
