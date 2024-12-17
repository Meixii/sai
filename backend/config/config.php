<?php
// Load environment variables
$envPath = __DIR__ . '/../../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Remove quotes if present
        if (strpos($value, '"') === 0) {
            $value = trim($value, '"');
        } elseif (strpos($value, "'") === 0) {
            $value = trim($value, "'");
        }

        putenv(sprintf('%s=%s', $name, $value));
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

// Database configuration
define('DB_HOST', getenv('DB_HOST'));
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));

// API Keys
define('WEATHER_API_KEY', getenv('WEATHER_API_KEY'));
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID'));
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET'));
define('GOOGLE_API_KEY', getenv('GOOGLE_API_KEY'));

// Email Configuration
define('SMTP_HOST', getenv('SMTP_HOST'));
define('SMTP_USER', getenv('SMTP_USER'));
define('SMTP_PASS', getenv('SMTP_PASS'));
define('SMTP_PORT', getenv('SMTP_PORT'));
define('SMTP_SECURE', getenv('SMTP_SECURE'));

// Application Settings
define('APP_URL', getenv('APP_URL'));
define('APP_NAME', getenv('APP_NAME'));
define('JWT_SECRET', getenv('JWT_SECRET'));
define('TOKEN_EXPIRY', getenv('JWT_EXPIRY'));

// Load Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Time settings
define('DEFAULT_TIMEZONE', getenv('DEFAULT_TIMEZONE'));
date_default_timezone_set(DEFAULT_TIMEZONE);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/../../logs')) {
    mkdir(__DIR__ . '/../../logs', 0755, true);
}

// Custom error handler to prevent output of errors in response
function jsonErrorHandler($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno] $errstr on line $errline in file $errfile");
    return true;
}
set_error_handler('jsonErrorHandler');

// Fatal error handler
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
        exit;
    }
});

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

// Cross-Origin Resource Sharing (CORS) settings
header('Access-Control-Allow-Origin: ' . APP_URL);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');