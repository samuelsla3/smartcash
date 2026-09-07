<?php
// Mantido como endpoint de compatibilidade. Os formulários atuais processam
// cadastro/edição diretamente nas páginas correspondentes.
require_once __DIR__.'/../includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
redirect('index.php');
