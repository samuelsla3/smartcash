<?php

declare(strict_types=1);

function mailConfig(): array
{
    $local = is_file(__DIR__ . '/../config/mail.local.php')
        ? require __DIR__ . '/../config/mail.local.php'
        : [];
    $defaults = [
        'enabled' => filter_var(getenv('MAIL_ENABLED') ?: 'false', FILTER_VALIDATE_BOOLEAN),
        'host' => getenv('MAIL_HOST') ?: '',
        'port' => (int) (getenv('MAIL_PORT') ?: 587),
        'username' => getenv('MAIL_USERNAME') ?: '',
        'password' => getenv('MAIL_PASSWORD') ?: '',
        'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
        'from_email' => getenv('MAIL_FROM_ADDRESS') ?: '',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'SmartCash',
        'app_url' => getenv('APP_URL') ?: ''
    ];
    return array_replace($defaults, is_array($local) ? $local : []);
}

function mailConfigured(): bool
{
    $c = mailConfig();
    return (bool) $c['enabled'] &&
        $c['host'] !== '' &&
        filter_var($c['from_email'], FILTER_VALIDATE_EMAIL) &&
        in_array($c['encryption'], ['tls', 'ssl', 'none'], true) &&
        ($c['encryption'] !== 'none' ||
            in_array($c['host'], ['localhost', '127.0.0.1', '::1'], true));
}

function notificationFor(int $user, string $message): int
{
    query('INSERT INTO notificacao (id_usuario,mensagem) VALUES (?,?)', [
        $user,
        mb_substr($message, 0, 255)
    ]);
    $id = (int) db()->lastInsertId();
    if (
        scalar(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='sc_email_fila'"
        ) &&
        scalar('SELECT receber_email FROM usuario WHERE id_usuario=?', [$user])
    ) {
        query('INSERT INTO sc_email_fila (id_notificacao,id_usuario) VALUES (?,?)', [$id, $user]);
    }
    return $id;
}

function sendNotificationEmail(array $user, string $message, int $notification): void
{
    if (!mailConfigured()) {
        throw new DomainException('O envio por e-mail ainda precisa ser configurado.');
    }
    foreach (['Exception', 'SMTP', 'PHPMailer'] as $class) {
        require_once __DIR__ . '/../vendor/phpmailer/src/' . $class . '.php';
    }
    $c = mailConfig();
    $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = $c['host'];
    $mailer->Port = (int) $c['port'];
    $mailer->SMTPAuth = $c['username'] !== '';
    $mailer->Username = $c['username'];
    $mailer->Password = $c['password'];
    $mailer->SMTPSecure = $c['encryption'] === 'none' ? '' : $c['encryption'];
    $mailer->SMTPAutoTLS = $c['encryption'] !== 'none';
    $mailer->Timeout = 10;
    $mailer->CharSet = 'UTF-8';
    $mailer->setFrom($c['from_email'], $c['from_name']);
    $mailer->addAddress($user['email'], $user['nome']);
    $mailer->Subject = 'SmartCash — nova notificação';
    $mailer->MessageID =
        '<smartcash-' . $notification . '@' . substr(strrchr($c['from_email'], '@'), 1) . '>';
    $mailer->isHTML(true);
    $mailer->Body =
        '<div style="font:16px/1.7 Arial,sans-serif;color:#213129;max-width:560px;margin:auto;padding:30px">' .
        '<h2 style="color:#284630">SmartCash</h2>' .
        '<p>Olá, ' .
        e($user['nome']) .
        '.</p><p>' .
        nl2br(e($message)) .
        '</p>';
    if (
        filter_var($c['app_url'], FILTER_VALIDATE_URL) &&
        preg_match('#^https?://#i', $c['app_url'])
    ) {
        $mailer->Body .=
            '<p><a href="' .
            e(rtrim($c['app_url'], '/') . '/notificacoes/index.php') .
            '">Abrir minhas notificações</a></p>';
    }
    $mailer->Body .=
        '<p style="font-size:12px;color:#67786a">Você pode desativar comunicações por e-mail no seu perfil.</p>' .
        '</div>';
    $mailer->AltBody =
        'Olá, ' .
        $user['nome'] .
        ".\n\n" .
        $message .
        "\n\nVocê pode desativar comunicações por e-mail no seu perfil.";
    $mailer->send();
}

/** Reserva uma mensagem por vez; o envio SMTP ocorre fora da transação do banco. */
function processEmailQueue(
    int $limit = 50,
    ?int $onlyUser = null,
    ?int $onlyNotification = null
): array {
    $result = ['enviados' => 0, 'falhas' => 0, 'cancelados' => 0];
    if (!mailConfigured()) {
        return $result;
    }
    query(
        "UPDATE sc_email_fila SET status='falhou',bloqueado_ate=NULL WHERE status='enviando' AND bloqueado_ate<NOW() AND tentativas>=5"
    );
    for ($n = 0; $n < $limit; $n++) {
        db()->beginTransaction();
        try {
            $extra = $onlyUser !== null ? ' AND id_usuario=?' : '';
            $params = $onlyUser !== null ? [$onlyUser] : [];
            if ($onlyNotification !== null) {
                $extra .= ' AND id_notificacao=?';
                $params[] = $onlyNotification;
            }
            $job = one(
                "SELECT * FROM sc_email_fila WHERE tentativas<5 AND proxima_tentativa<=NOW()
                AND (status='pendente' OR (status='enviando' AND bloqueado_ate<NOW())) $extra
                ORDER BY id_email LIMIT 1 FOR UPDATE SKIP LOCKED",
                $params
            );
            if (!$job) {
                db()->commit();
                break;
            }
            query(
                "UPDATE sc_email_fila SET status='enviando',tentativas=tentativas+1,bloqueado_ate=DATE_ADD(NOW(),INTERVAL 5 MINUTE) WHERE id_email=?",
                [$job['id_email']]
            );
            db()->commit();
        } catch (Throwable $ex) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            throw $ex;
        }
        $user = one('SELECT nome,email,receber_email FROM usuario WHERE id_usuario=?', [
            $job['id_usuario']
        ]);
        if (
            !$user ||
            !$user['receber_email'] ||
            !filter_var($user['email'], FILTER_VALIDATE_EMAIL)
        ) {
            query(
                "UPDATE sc_email_fila SET status='cancelado',bloqueado_ate=NULL WHERE id_email=?",
                [$job['id_email']]
            );
            $result['cancelados']++;
            continue;
        }
        try {
            $message = scalar('SELECT mensagem FROM notificacao WHERE id_notificacao=?', [
                $job['id_notificacao']
            ]);
            sendNotificationEmail($user, (string) $message, (int) $job['id_notificacao']);
            query(
                "UPDATE sc_email_fila SET status='enviado',enviado_em=NOW(),bloqueado_ate=NULL,ultimo_erro=NULL WHERE id_email=?",
                [$job['id_email']]
            );
            $result['enviados']++;
        } catch (Throwable $ex) {
            $attempt = (int) $job['tentativas'] + 1;
            $delay = min(3600, 60 * 2 ** $attempt);
            // Respostas SMTP e credenciais não ficam nos registros visíveis da aplicação.
            query(
                "UPDATE sc_email_fila SET status=?,bloqueado_ate=NULL,proxima_tentativa=DATE_ADD(NOW(),INTERVAL $delay SECOND),ultimo_erro='Falha de conexão, autenticação ou entrega SMTP.' WHERE id_email=?",
                [$attempt >= 5 ? 'falhou' : 'pendente', $job['id_email']]
            );
            $result['falhas']++;
        }
    }
    return $result;
}

function notificationOnce(int $uid, string $key, string $message): void
{
    transaction(function () use ($uid, $key, $message) {
        $insert = query('INSERT IGNORE INTO sc_aviso_chave (id_usuario,chave) VALUES (?,?)', [
            $uid,
            $key
        ]);
        if ($insert->rowCount()) {
            notificationFor($uid, $message);
        }
    });
}

function cardPendingRows(int $uid): array
{
    return rows(
        'SELECT *,limite_total-limite_disponivel AS utilizado FROM cartao WHERE id_usuario=? AND limite_total>limite_disponivel ORDER BY dia_vencimento,nome_cartao',
        [$uid]
    );
}

function generateReminders(int $uid): void
{
    foreach (cardPendingRows($uid) as $card) {
        $now = new DateTimeImmutable('today');
        $due = $now->setDate(
            (int) $now->format('Y'),
            (int) $now->format('n'),
            min((int) $card['dia_vencimento'], (int) $now->format('t'))
        );
        if ((int) $now->diff($due)->format('%r%a') <= 3) {
            notificationOnce(
                $uid,
                'cartao:' . $card['id_cartao'] . ':' . $now->format('Y-m'),
                'O cartão ' .
                    $card['nome_cartao'] .
                    ' tem ' .
                    money($card['utilizado']) .
                    ' em aberto. Vencimento de referência: ' .
                    $due->format('d/m/Y') .
                    '.'
            );
        }
    }
    foreach (
        [
            'despesa' => ['id_despesa', 'proxima_cobranca'],
            'recebimento' => ['id_recebimento', 'proximo_recebimento']
        ] as $table => [$pk, $date]
    ) {
        foreach (
            rows("SELECT * FROM $table WHERE id_usuario=? AND recorrente=1 AND $date<=?", [
                $uid,
                date('Y-m-d')
            ]) as $r
        ) {
            notificationOnce(
                $uid,
                $table . ':' . $r[$pk] . ':' . $r[$date],
                'Confirme a ocorrência de ' .
                    $r['descricao'] .
                    ' prevista para ' .
                    formatDateBr($r[$date]) .
                    ' no SmartCash.'
            );
        }
    }
}
