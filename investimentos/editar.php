<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$uid = usuarioLogado();
$errors = [];

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    redirect('index.php');
}

/*
 * Busca o investimento atual
 */
$stmt = db()->prepare(
    'SELECT *
     FROM INVESTIMENTO
     WHERE id_investimento = :id
       AND id_usuario = :u'
);

$stmt->execute([
    'id' => $id,
    'u' => $uid
]);

$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    flash('error', 'Investimento não encontrado.');
    redirect('index.php');
}

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
 * Edição
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $conta = intPost('id_conta');
    $tipo = intPost('id_tipo_investimento');

    $nome = trim(
        (string) ($_POST['nome'] ?? $item['nome'])
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
     * Confere tipo de investimento
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
     * Atualização financeira
     */
    if (!$errors) {

        $pdo = db();

        try {

            $pdo->beginTransaction();

            /*
             * Busca novamente o investimento e bloqueia o registro.
             */
            $stmt = $pdo->prepare(
                'SELECT *
                 FROM INVESTIMENTO
                 WHERE id_investimento = :id
                   AND id_usuario = :usuario
                 FOR UPDATE'
            );

            $stmt->execute([
                'id' => $id,
                'usuario' => $uid
            ]);

            $investimentoAtual = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$investimentoAtual) {
                throw new RuntimeException(
                    'Investimento não encontrado.'
                );
            }

            $contaAntiga = (int) $investimentoAtual['id_conta'];
            $valorAntigo = (float) $investimentoAtual['valor_aplicado'];

            /*
             * CASO 1:
             * Continua usando a mesma conta.
             */
            if ($contaAntiga === $conta) {

                $stmt = $pdo->prepare(
                    'SELECT saldo_atual
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
                        'Conta não encontrada.'
                    );
                }

                /*
                 * O dinheiro disponível para uma edição é:
                 *
                 * saldo atual + valor antigo do investimento
                 *
                 * porque primeiro desfazemos a aplicação anterior.
                 */
                $saldoDisponivel =
                    (float) $contaBanco['saldo_atual']
                    + $valorAntigo;

                if ($saldoDisponivel < (float) $aplicado) {
                    throw new RuntimeException(
                        'Saldo insuficiente para alterar o valor do investimento.'
                    );
                }

                /*
                 * Desfaz aplicação antiga e aplica a nova.
                 */
                $stmt = $pdo->prepare(
                    'UPDATE CONTA
                     SET saldo_atual =
                         saldo_atual + :valor_antigo - :valor_novo
                     WHERE id_conta = :conta
                       AND id_usuario = :usuario'
                );

                $stmt->execute([
                    'valor_antigo' => $valorAntigo,
                    'valor_novo' => $aplicado,
                    'conta' => $conta,
                    'usuario' => $uid
                ]);

            } else {

                /*
                 * CASO 2:
                 * O investimento mudou de conta.
                 */

                /*
                 * Bloqueia a conta antiga.
                 */
                $stmt = $pdo->prepare(
                    'SELECT saldo_atual
                     FROM CONTA
                     WHERE id_conta = :conta
                       AND id_usuario = :usuario
                     FOR UPDATE'
                );

                $stmt->execute([
                    'conta' => $contaAntiga,
                    'usuario' => $uid
                ]);

                $oldAccount = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$oldAccount) {
                    throw new RuntimeException(
                        'Conta anterior não encontrada.'
                    );
                }

                /*
                 * Bloqueia a nova conta.
                 */
                $stmt->execute([
                    'conta' => $conta,
                    'usuario' => $uid
                ]);

                $newAccount = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$newAccount) {
                    throw new RuntimeException(
                        'Nova conta não encontrada.'
                    );
                }

                if ((float) $newAccount['saldo_atual'] < (float) $aplicado) {
                    throw new RuntimeException(
                        'Saldo insuficiente na nova conta.'
                    );
                }

                /*
                 * Devolve o investimento antigo.
                 */
                $stmt = $pdo->prepare(
                    'UPDATE CONTA
                     SET saldo_atual = saldo_atual + :valor
                     WHERE id_conta = :conta
                       AND id_usuario = :usuario'
                );

                $stmt->execute([
                    'valor' => $valorAntigo,
                    'conta' => $contaAntiga,
                    'usuario' => $uid
                ]);

                /*
                 * Retira o novo investimento da nova conta.
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
            }

            /*
             * Atualiza o investimento
             */
            $stmt = $pdo->prepare(
                'UPDATE INVESTIMENTO
                 SET
                    id_conta = :conta,
                    id_tipo_investimento = :tipo,
                    nome = :nome,
                    valor_aplicado = :valor,
                    rendimento = :rendimento,
                    data_aplicacao = :data
                 WHERE id_investimento = :id
                   AND id_usuario = :usuario'
            );

            $stmt->execute([
                'conta' => $conta,
                'tipo' => $tipo,
                'nome' => $nome,
                'valor' => $aplicado,
                'rendimento' => $rend,
                'data' => $data,
                'id' => $id,
                'usuario' => $uid
            ]);

            $pdo->commit();

            flash(
                'success',
                'Investimento atualizado.'
            );

            redirect('index.php');

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Não foi possível atualizar o investimento.';
        }
    }
}

$pageTitle = 'Editar investimento';
$basePath = '../';

require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Editar investimento</h1>

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
                            <?= (
                                (int) ($_POST['id_conta'] ?? $item['id_conta'])
                                === (int) $c['id_conta']
                            ) ? 'selected' : '' ?>
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
                            <?= (
                                (int) (
                                    $_POST['id_tipo_investimento']
                                    ?? $item['id_tipo_investimento']
                                )
                                === (int) $t['id_tipo_investimento']
                            ) ? 'selected' : '' ?>
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
                value="<?= e(
                    $_POST['nome']
                    ?? $item['nome']
                ) ?>"
            >

        </div>

        <div class="form-row">

            <div class="form-group">

                <label>Valor aplicado</label>

                <input
                    name="valor_aplicado"
                    inputmode="decimal"
                    required
                    value="<?= e(
                        $_POST['valor_aplicado']
                        ?? $item['valor_aplicado']
                    ) ?>"
                >

            </div>

            <div class="form-group">

                <label>Rendimento</label>

                <input
                    name="rendimento"
                    inputmode="decimal"
                    required
                    value="<?= e(
                        $_POST['rendimento']
                        ?? $item['rendimento']
                    ) ?>"
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
                    $_POST['data_aplicacao']
                    ?? $item['data_aplicacao']
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