<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

function get_db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        if (php_sapi_name() === 'cli') {
            throw $e;
        }
        $installUrl = BASE_URL . '/install.php';
        http_response_code(503);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Database Setup Required - IELTS AI Pro</title>';
        echo '<style>body{font-family:system-ui,sans-serif;background:#f8fafc;color:#1e293b;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;}';
        echo '.card{background:#fff;padding:2rem;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,0.08);max-width:540px;width:90%;}';
        echo '.btn{display:inline-block;background:#2563eb;color:#fff;padding:0.75rem 1.25rem;border-radius:8px;text-decoration:none;font-weight:600;margin-top:1rem;}</style></head><body>';
        echo '<div class="card"><h2>Welcome to IELTS AI Pro (XAMPP Setup)</h2>';
        echo '<p>The MySQL database <code>' . htmlspecialchars(DB_NAME) . '</code> has not been initialized yet or MySQL is not running.</p>';
        echo '<p><strong>Error Detail:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p>Click below to automatically create the database, tables, and demo accounts:</p>';
        echo '<a class="btn" href="' . htmlspecialchars($installUrl) . '">Run 1-Click XAMPP Installer &rarr;</a></div></body></html>';
        exit;
    }
}

function get_setting(string $key, string $default = ''): string {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = :k LIMIT 1');
        $stmt->execute([':k' => $key]);
        $val = $stmt->fetchColumn();
        return ($val !== false && $val !== null && $val !== '') ? (string)$val : $default;
    } catch (Throwable $t) {
        return $default;
    }
}
