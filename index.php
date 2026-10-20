<?php

require_once __DIR__ . '/includes/functions.php';
redirect(url(usuarioLogado() ? 'dashboard/index.php' : 'login.php'));
