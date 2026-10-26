<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

ensure_session_started();

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

function is_admin(): bool {
    return is_logged_in() && ($_SESSION['user_role'] === 'admin');
}

function current_user_id(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function current_user_role(): string {
    return (string)($_SESSION['user_role'] ?? 'guest');
}

function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    static $cachedUser = null;
    if ($cachedUser !== null && (int)$cachedUser['id'] === current_user_id()) {
        return $cachedUser;
    }
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT id, full_name, email, role, exam_type, target_band, created_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => current_user_id()]);
    $user = $stmt->fetch();
    if (!$user) {
        logout_user();
        return null;
    }
    $cachedUser = $user;
    return $user;
}

function login_user(array $user): void {
    ensure_session_started();
    session_regenerate_id(true);
    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_name']  = (string)$user['full_name'];
    $_SESSION['user_email'] = (string)$user['email'];
    $_SESSION['user_role']  = (string)$user['role'];
    $_SESSION['login_time'] = time();
}

function logout_user(): void {
    ensure_session_started();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}

function require_login(bool $isApi = false): void {
    if (!is_logged_in()) {
        if ($isApi) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Authentication required. Please log in.']);
            exit;
        }
        $_SESSION['flash_error'] = 'Please sign in to access that page.';
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function require_admin(bool $isApi = false): void {
    require_login($isApi);
    if (!is_admin()) {
        http_response_code(403);
        if ($isApi) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Access denied. Administrator privileges required.']);
            exit;
        }
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>403 Access Denied</title>';
        echo '<link rel="stylesheet" href="' . htmlspecialchars(BASE_URL) . '/assets/css/style.css"></head><body>';
        echo '<div class="container" style="padding:4rem 1rem;text-align:center;">';
        echo '<div class="card" style="max-width:520px;margin:0 auto;">';
        echo '<h1 style="color:#dc2626;margin-bottom:0.5rem;">403 Forbidden</h1>';
        echo '<p>Access Denied: Student accounts cannot access administrator pages.</p>';
        echo '<a href="' . htmlspecialchars(BASE_URL) . '/dashboard.php" class="btn btn-primary" style="margin-top:1rem;">Return to Student Dashboard</a>';
        echo '</div></div></body></html>';
        exit;
    }
}

function fetch_authorized_attempt(PDO $pdo, int $attemptId): array {
    require_login();
    if (is_admin()) {
        $stmt = $pdo->prepare('
            SELECT a.*, t.title AS test_title, t.exam_type, t.duration_minutes, t.passage_or_script,
                   u.full_name AS student_name, u.email AS student_email
            FROM attempts a
            JOIN tests t ON t.id = a.test_id
            JOIN users u ON u.id = a.user_id
            WHERE a.id = :id
            LIMIT 1
        ');
        $stmt->execute([':id' => $attemptId]);
    } else {
        $stmt = $pdo->prepare('
            SELECT a.*, t.title AS test_title, t.exam_type, t.duration_minutes, t.passage_or_script,
                   u.full_name AS student_name, u.email AS student_email
            FROM attempts a
            JOIN tests t ON t.id = a.test_id
            JOIN users u ON u.id = a.user_id
            WHERE a.id = :id AND a.user_id = :uid
            LIMIT 1
        ');
        $stmt->execute([':id' => $attemptId, ':uid' => current_user_id()]);
    }

    $attempt = $stmt->fetch();
    if (!$attempt) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>403 Forbidden</title>';
        echo '<link rel="stylesheet" href="' . htmlspecialchars(BASE_URL) . '/assets/css/style.css"></head><body>';
        echo '<div class="container" style="padding:4rem 1rem;text-align:center;">';
        echo '<div class="card" style="max-width:520px;margin:0 auto;">';
        echo '<h1 style="color:#dc2626;">403 Access Denied</h1>';
        echo '<p>You do not have permission to view another student's private exam attempt or the attempt does not exist.</p>';
        echo '<a href="' . htmlspecialchars(BASE_URL) . '/dashboard.php" class="btn btn-primary">Back to Dashboard</a>';
        echo '</div></div></body></html>';
        exit;
    }
    return $attempt;
}

function set_flash(string $type, string $message): void {
    ensure_session_started();
    $_SESSION['flash_' . $type] = $message;
}

function get_flash(string $type): ?string {
    ensure_session_started();
    $key = 'flash_' . $type;
    if (!empty($_SESSION[$key])) {
        $msg = (string)$_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}
