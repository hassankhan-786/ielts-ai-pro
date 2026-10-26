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
    $fullName   = trim((string)($_POST['full_name'] ?? ''));
    $email      = strtolower(trim((string)($_POST['email'] ?? '')));
    $password   = (string)($_POST['password'] ?? '');
    $examType   = in_array($_POST['exam_type'] ?? '', ['academic', 'general'], true) ? $_POST['exam_type'] : 'academic';
    $targetBand = max(4.0, min(9.0, (float)($_POST['target_band'] ?? 7.5)));

    if (strlen($fullName) < 2) {
        $error = 'Please enter your full name (at least 2 characters).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        $pdo = get_db();
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $check->execute([':email' => $email]);
        if ($check->fetch()) {
            $error = 'An account with that email address already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins = $pdo->prepare('
                INSERT INTO users (full_name, email, password_hash, role, exam_type, target_band)
                VALUES (:name, :email, :hash, "student", :etype, :tband)
            ');
            $ins->execute([
                ':name'  => $fullName,
                ':email' => $email,
                ':hash'  => $hash,
                ':etype' => $examType,
                ':tband' => $targetBand,
            ]);
            $newId = (int)$pdo->lastInsertId();
            login_user([
                'id'        => $newId,
                'full_name' => $fullName,
                'email'     => $email,
                'role'      => 'student',
            ]);
            set_flash('success', 'Account created! Welcome to IELTS AI Pro.');
            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        }
    }
}

$pageTitle = 'Create Student Account';
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
    <div class="card auth-card">
        <h2>Create Your Student Account</h2>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" action="register.php">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password (minimum 8 characters)</label>
                <input type="password" id="password" name="password" required minlength="8">
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label for="exam_type">IELTS Module</label>
                    <select id="exam_type" name="exam_type">
                        <option value="academic">IELTS Academic</option>
                        <option value="general">IELTS General Training</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="target_band">Target Band Score</label>
                    <select id="target_band" name="target_band">
                        <?php foreach ([6.0, 6.5, 7.0, 7.5, 8.0, 8.5, 9.0] as $b): ?>
                            <option value="<?= $b ?>" <?= ($b == 7.5) ? 'selected' : '' ?>>Band <?= number_format($b, 1) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Register &amp; Start Practicing</button>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
