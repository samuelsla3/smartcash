<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
requireLogin();
$id_usuario = usuarioLogado();
