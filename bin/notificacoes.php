<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
if (!schemaReady()) {
    exit("Atualize o banco pela interface antes de executar.\n");
}
foreach (rows('SELECT id_usuario FROM usuario ORDER BY id_usuario') as $u) {
    $_SESSION['id_usuario'] = (int) $u['id_usuario'];
    generateReminders((int) $u['id_usuario']);
}
if (!mailConfigured()) {
    exit("Lembretes internos atualizados. Configure config/mail.local.php para enviar e-mails.\n");
}
echo json_encode(processEmailQueue(100), JSON_UNESCAPED_UNICODE) . "\n";
