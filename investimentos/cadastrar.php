<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$uid = usuarioLogado();
$errors = [];

/*
 * Tipos de investimento
 */
$types = db()
    ->query(
        'SELECT id_tipo_investimento, nome
         FROM TIPO_INVESTIMENTO
         ORDER BY nome'
    )
    ->fetchAll(PDO::FETCH_ASSOC);

/*
 * Contas do usuário
 */
$stmt = db()->prepare(
    'SELECT id_conta, nome_conta, saldo_atual
     FROM CONTA
     WHERE id_usuario = :u
     ORDER BY nome_conta'
);

$stmt->execute([
    'u' => $uid
]);

$contas = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
 * Cadastro
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $conta = intPost('id_conta');
    $tipo = intPost('id_tipo_investimento');

    $nome = trim(
        (string) ($_POST['nome'] ?? '')
    );

    $aplicado = decimalPost('valor_aplicado');
    $rend = decimalPost('rendimento');
    $data = datePost('data_aplicacao');

    /*
     * Validações
     */
    if (!$conta) {
        $errors[] = 'Selecione uma conta.';
    }

    if (!$tipo) {
        $errors[] = 'Selecione o tipo de investimento.';
    }

    if ($nome === '') {
        $errors[] = 'Informe o nome.';
    }

    if ($aplicado === null || $aplicado <= 0) {
        $errors[] = 'Informe um valor aplicado maior que zero.';
    }

    if ($rend === null || $rend < 0) {
        $errors[] = 'Rendimento inválido.';
    }

    if (!$data) {
        $errors[] = 'Data inválida.';
    }

    /*
     * Confere se o tipo de investimento existe
     */
    if (!$errors) {

        $checkTipo = db()->prepare(
            'SELECT id_tipo_investimento
             FROM TIPO_INVESTIMENTO
             WHERE id_tipo_investimento = :tipo'
        );

        $checkTipo->execute([
            'tipo' => $tipo
        ]);

        if (!$checkTipo->fetch()) {
            $errors[] = 'Tipo de investimento inválido.';
        }
    }

    /*
     * Operação financeira
     *
     * O investimento retira dinheiro da conta e
     * transforma esse valor em patrimônio investido.
     */
    if (!$errors) {

        $pdo = db();

        try {

            $pdo->beginTransaction();

            /*
             * Bloqueia a conta durante a operação.
             * Também garante que ela pertence ao usuário.
             */
            $stmt = $pdo->prepare(
                'SELECT id_conta, saldo_atual
                 FROM CONTA
                 WHERE id_conta = :conta
                   AND id_usuario = :usuario
                 FOR UPDATE'
            );

            $stmt->execute([
                'conta' => $conta,
                'usuario' => $uid
            ]);

            $contaBanco = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$contaBanco) {
                throw new RuntimeException(
                    'A conta selecionada não foi encontrada.'
                );
            }

            /*
             * Impede investir mais do que existe na conta.
             */
            if ((float) $contaBanco['saldo_atual'] < (float) $aplicado) {
                throw new RuntimeException(
                    'Saldo insuficiente para realizar este investimento.'
                );
            }

            /*
             * Cadastra o investimento
             */
            $stmt = $pdo->prepare(
                'INSERT INTO INVESTIMENTO
                    (
                        id_usuario,
                        id_conta,
                        id_tipo_investimento,
                        nome,
                        valor_aplicado,
                        rendimento,
                        data_aplicacao
                    )
                 VALUES
                    (
                        :usuario,
                        :conta,
                        :tipo,
                        :nome,
                        :valor,
                        :rendimento,
                        :data
                    )'
            );

            $stmt->execute([
                'usuario' => $uid,
                'conta' => $conta,
                'tipo' => $tipo,
                'nome' => $nome,
                'valor' => $aplicado,
                'rendimento' => $rend,
                'data' => $data
            ]);

            /*
             * Retira o valor aplicado da conta
             */
            $stmt = $pdo->prepare(
                'UPDATE CONTA
                 SET saldo_atual = saldo_atual - :valor
                 WHERE id_conta = :conta
                   AND id_usuario = :usuario'
            );

            $stmt->execute([
                'valor' => $aplicado,
                'conta' => $conta,
                'usuario' => $uid
            ]);

            $pdo->commit();

            flash(
                'success',
                'Investimento cadastrado e valor debitado da conta.'
            );

            redirect('index.php');

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Não foi possível cadastrar o investimento.';
        }
    }
}

$pageTitle = 'Novo investimento';
$basePath = '../';

require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">

    <h1>Novo investimento</h1>

    <a class="btn btn-secondary" href="index.php">
        Voltar
    </a>

</div>

<div class="form-card">

    <?php foreach ($errors as $er): ?>

        <div class="alert error">
            <?= e($er) ?>
        </div>

    <?php endforeach; ?>

    <form method="post">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrfToken()) ?>"
        >

        <div class="form-row">

            <div class="form-group">

                <label>Conta</label>

                <select name="id_conta" required>

                    <option value="">
                        Selecione
                    </option>

                    <?php foreach ($contas as $c): ?>

                        <option
                            value="<?= (int) $c['id_conta'] ?>"
                            <?= ((int) ($_POST['id_conta'] ?? 0) ===
                                (int) $c['id_conta'])
                                ? 'selected'
                                : '' ?>
                        >
                            <?= e($c['nome_conta']) ?>
                            —
                            R$ <?= number_format(
                                (float) $c['saldo_atual'],
                                2,
                                ',',
                                '.'
                            ) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="form-group">

                <label>Tipo de investimento</label>

                <select
                    name="id_tipo_investimento"
                    required
                >

                    <option value="">
                        Selecione
                    </option>

                    <?php foreach ($types as $t): ?>

                        <option
                            value="<?= (int) $t['id_tipo_investimento'] ?>"
                            <?= ((int) ($_POST['id_tipo_investimento'] ?? 0) ===
                                (int) $t['id_tipo_investimento'])
                                ? 'selected'
                                : '' ?>
                        >
                            <?= e($t['nome']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

        </div>

        <div class="form-group">

            <label>Nome</label>

            <input
                name="nome"
                maxlength="150"
                required
                value="<?= e($_POST['nome'] ?? '') ?>"
            >

        </div>

        <div class="form-row">

            <div class="form-group">

                <label>Valor aplicado</label>

                <input
                    name="valor_aplicado"
                    inputmode="decimal"
                    required
                    value="<?= e($_POST['valor_aplicado'] ?? '') ?>"
                >

            </div>

            <div class="form-group">

                <label>Rendimento</label>

                <input
                    name="rendimento"
                    inputmode="decimal"
                    required
                    value="<?= e($_POST['rendimento'] ?? '0') ?>"
                >

            </div>

        </div>

        <div class="form-group">

            <label>Data de aplicação</label>

            <input
                type="date"
                name="data_aplicacao"
                required
                value="<?= e(
                    $_POST['data_aplicacao'] ?? date('Y-m-d')
                ) ?>"
            >

        </div>

        <button class="btn">
            Salvar
        </button>

    </form>

</div>

<?php
require __DIR__ . '/../includes/footer.php';
?>