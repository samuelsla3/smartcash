<?php

// Gmail: use uma senha de aplicativo; nunca a senha normal da conta.
return [
    'enabled' => true,
    'host' => 'smtp.seu-provedor.com',
    'port' => 587,
    'encryption' => 'tls', // tls (STARTTLS, 587), ssl (465); none só em teste local.
    'username' => 'remetente@exemplo.com',
    'password' => 'SENHA_DE_APLICATIVO_OU_SMTP',
    'from_email' => 'remetente@exemplo.com',
    'from_name' => 'SmartCash',
    'app_url' => 'http://localhost/smartcash'
];
