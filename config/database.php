<?php

declare(strict_types=1);
$local = is_file(__DIR__ . '/local.php') ? require __DIR__ . '/local.php' : [];
$host = $local['host'] ?? (getenv('DB_HOST') ?: '127.0.0.1');
$port = $local['port'] ?? (getenv('DB_PORT') ?: '3306');
$dbname = $local['database'] ?? (getenv('DB_NAME') ?: 'smartcash');
$user = $local['user'] ?? (getenv('DB_USER') ?: 'root');
$password = $local['password'] ?? (getenv('DB_PASSWORD') ?: '');
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $ex) {
    error_log('SmartCash conexão: ' . $ex->getMessage());
    http_response_code(503);
    exit(
        'Não foi possível conectar ao banco. Confira config/database.php ou config/local.php e o serviço MySQL.'
    );
}
