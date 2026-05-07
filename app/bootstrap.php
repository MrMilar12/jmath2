<?php
declare(strict_types=1);

/**
 * Application Bootstrap
 * Initialize core components and configuration
 */

// Start session
session_start();

// Define application root path
define('APP_ROOT', dirname(__DIR__));
define('APP_PATH', APP_ROOT . '/app');
define('CONFIG_PATH', APP_ROOT . '/config');
define('STORAGE_PATH', APP_ROOT . '/storage');

// Load environment variables (if .env file exists)
if (file_exists(APP_ROOT . '/.env')) {
    $dotenv = parse_ini_file(APP_ROOT . '/.env');
    foreach ($dotenv as $key => $value) {
        putenv("$key=$value");
    }
}

// Load helper functions
require_once APP_PATH . '/helpers.php';

// Load legacy compatibility functions
require_once APP_PATH . '/database.php';
require_once APP_PATH . '/security.php';
require_once APP_PATH . '/repository.php';

// Set security headers
secure_headers();

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = APP_PATH . '/';
    
    if (strpos($class, $prefix) === 0) {
        $relativeClass = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        
        if (file_exists($file)) {
            require $file;
        }
    }
});

// Check if application is installed
$configFile = CONFIG_PATH . '/config.php';
$isInstalled = file_exists($configFile);
$pdo = null;

// Initialize database connection if installed
if ($isInstalled) {
    try {
        $config = require CONFIG_PATH . '/config.php';
        
        // Create database instance for legacy code
        require_once APP_PATH . '/database/Database.php';
        $pdo = db_connect($config);
        
        // Check schema
        ensure_schema_ready($pdo);
    } catch (Throwable $e) {
        $isInstalled = false;
        $_SESSION['install_error'] = $e->getMessage();
        error_log('Bootstrap Error: ' . $e->getMessage());
    }
} else {
    // Redirect to install if not installed
    if (strpos($_SERVER['REQUEST_URI'] ?? '/', '/install') === false) {
        // Allow specific routes even if not installed
        $publicRoutes = ['/install', '/install/index.php', '/health'];
        $currentRoute = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        
        if (!in_array($currentRoute, $publicRoutes)) {
            // Uncomment to force install redirect:
            // header('Location: /install');
            // exit;
        }
    }
}

// Configuration
$appConfig = [
    'APP_NAME' => getenv('APP_NAME') ?: 'jmath2',
    'APP_ENV' => getenv('APP_ENV') ?: 'production',
    'APP_DEBUG' => getenv('APP_DEBUG') === 'true',
    'SESSION_LIFETIME' => (int)(getenv('SESSION_LIFETIME') ?: 7200),
    'SESSION_NAME' => getenv('SESSION_NAME') ?: 'jmath2_session',
];

// Set session configuration
ini_set('session.name', $appConfig['SESSION_NAME']);
ini_set('session.gc_maxlifetime', $appConfig['SESSION_LIFETIME']);

// Error handling
if ($appConfig['APP_DEBUG']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}
