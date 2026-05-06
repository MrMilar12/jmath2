<?php
declare(strict_types=1);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/repository.php';

secure_headers();

$configFile = dirname(__DIR__) . '/config/config.php';
$isInstalled = is_file($configFile);

if ($isInstalled) {
    $config = require $configFile;
    $pdo = db_connect($config);
    ensure_seed_data($pdo);
}
