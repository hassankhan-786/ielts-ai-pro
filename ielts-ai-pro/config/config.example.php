<?php
/**
 * IELTS AI Pro - Master Configuration File
 * Pre-configured for Local XAMPP Server (MySQL root with empty password).
 * For online hosting, update DB_HOST, DB_NAME, DB_USER, DB_PASS, and APP_ENV.
 */

declare(strict_types=1);

if (basename($_SERVER['PHP_SELF'] ?? '') === basename(__FILE__)) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

define('APP_NAME', 'IELTS AI Pro');
define('APP_VERSION', '2.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'local');

// Database Configuration (Default XAMPP settings)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'ielts_ai_pro');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

if (!defined('BASE_URL')) {
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (($_SERVER['SERVER_PORT'] ?? '') == 443) ||
        (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    );
    $protocol = $isHttps ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $basePath = preg_replace('#/(admin|api|includes|tests)$#', '', rtrim($scriptDir, '/'));
    define('BASE_URL', rtrim($protocol . $host . $basePath, '/'));
}

// External AI API Configuration (Optional - Offline Heuristic Evaluator is built in)
define('AI_PROVIDER', getenv('AI_PROVIDER') ?: 'offline');
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
define('GEMINI_MODEL', 'gemini-2.5-flash');
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: '');
define('OPENAI_MODEL', 'gpt-4o-mini');

define('CSRF_TOKEN_NAME', '_csrf_token');
define('SESSION_LIFETIME', 7200);

if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

date_default_timezone_set('Asia/Karachi');
