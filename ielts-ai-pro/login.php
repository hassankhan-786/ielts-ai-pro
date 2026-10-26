<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $email    = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter both your email address and password.';
    } else {
        $pdo = get_db();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, (string)$user['password_hash'])) {
            login_user($user);
            set_flash('success', 'Welcome back, ' . $user['full_name'] . '!');
            if ($user['role'] === 'admin') {
                header('Location: ' . BASE_URL . '/admin/index.php');
            } else {
                header('Location: ' . BASE_URL . '/dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid email or password. If this is a fresh XAMPP setup, run the 1-Click Installer first.';
        }
    }
}

$pageTitle = 'Sign In';
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
    <div class="card auth-card">
        <h2>Sign In to <?= htmlspecialchars(APP_NAME) ?></h2>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" action="login.php" novalidate>
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? 'student@ieltspro.local') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required value="Student@12345">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>
        <div class="demo-box">
            <strong>Quick XAMPP Demo Accounts:</strong>
            <ul>
                <li>Student: <code>student@ieltspro.local</code> / <code>Student@12345</code></li>
                <li>Admin: <code>admin@ieltspro.local</code> / <code>Admin@12345</code></li>
            </ul>
            <p style="margin-top:0.5rem;"><a href="<?= htmlspecialchars(BASE_URL) ?>/install.php">First time on XAMPP? Click here to initialize DB</a></p>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
