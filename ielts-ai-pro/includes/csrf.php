<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

function ensure_session_started(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (($_SERVER['SERVER_PORT'] ?? '') == 443) ||
            (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        );
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function csrf_token(): string {
    ensure_session_started();
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION[CSRF_TOKEN_NAME];
}

function csrf_field(): string {
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    $name  = htmlspecialchars(CSRF_TOKEN_NAME, ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="' . $name . '" value="' . $token . '">';
}

function verify_csrf_token(?string $submittedToken = null): bool {
    ensure_session_started();
    if ($submittedToken === null) {
        $submittedToken = $_POST[CSRF_TOKEN_NAME]
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? '';
    }
    $sessionToken = $_SESSION[CSRF_TOKEN_NAME] ?? '';
    if ($sessionToken === '' || $submittedToken === '') {
        return false;
    }
    return hash_equals((string)$sessionToken, (string)$submittedToken);
}

function require_valid_csrf(): void {
    if (!verify_csrf_token()) {
        $isJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
               || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');
        http_response_code(403);
        if ($isJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Invalid or expired CSRF security token. Please refresh the page.']);
        } else {
            echo '403 Forbidden: Invalid or expired CSRF security token.';
        }
        exit;
    }
}
