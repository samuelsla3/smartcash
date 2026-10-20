<?php

require_once __DIR__ . '/../includes/app.php';
try {
    [$i, $f] = period();
    $s = summary($i, $f);
    $cards = rows('SELECT * FROM cartao WHERE id_usuario=? ORDER BY nome_cartao', [
        usuarioLogado()
    ]);
    $invest = rows(
        'SELECT *,valor_aplicado+rendimento AS valor_atualizado FROM investimento WHERE id_usuario=? ORDER BY id_investimento DESC',
        [usuarioLogado()]
    );
    $goals = rows(
        'SELECT *,LEAST(100,valor_atual/valor_objetivo*100) AS progresso FROM objetivo_financeiro WHERE id_usuario=? ORDER BY id_objetivo DESC',
        [usuarioLogado()]
    );
    jsonResponse(
        array_merge($s, [
            'periodo' => ['inicio' => $i, 'fim' => $f],
            'categorias' => categoryTotals($i, $f),
            'movimentacoes' => recentMovements($i, $f, 10),
            'cartoes' => $cards,
            'investimentos' => $invest,
            'objetivos' => $goals,
            'evolucao' => chartSeries($i, $f)
        ])
    );
} catch (Throwable $ex) {
    jsonResponse(['erro' => safeError($ex)], 422);
}
