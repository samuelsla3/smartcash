<?php

require_once __DIR__ . '/../includes/app.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit();
}
verifyCsrf();
try {
    verifyNonce();
    if (!mailConfigured()) {
        throw new DomainException('Configure o remetente antes de testar o envio.');
    }
    if (!scalar('SELECT receber_email FROM usuario WHERE id_usuario=?', [usuarioLogado()])) {
        throw new DomainException('Ative Receber comunicações por e-mail no seu perfil.');
    }
    $id = transaction(
        fn () => notificationFor(
            usuarioLogado(),
            'Este é um e-mail de teste do SmartCash. Suas notificações por e-mail estão configuradas.'
        )
    );
    consumeNonce();
    $result = processEmailQueue(1, usuarioLogado(), $id);
    if (!$result['enviados']) {
        throw new DomainException(
            'O e-mail não foi enviado. Confira a configuração SMTP; o teste permanece na fila para nova tentativa.'
        );
    }
    flash('success', 'E-mail de teste enviado ao endereço do seu perfil.');
} catch (Throwable $ex) {
    flash('error', safeError($ex));
}
redirect(url('usuario/perfil.php'));
