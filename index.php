<?php
declare(strict_types=1);

session_start();

if (!empty($_SESSION['id_usuario'])) {
    header('Location: /smartcash/dashboard/index.php');
} else {
    header('Location: /smartcash/login.php');
}

exit;
