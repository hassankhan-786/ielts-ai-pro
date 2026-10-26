<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = get_db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $fullName   = trim((string)($_POST['full_name'] ?? $user['full_name']));
    $examType   = in_array($_POST['exam_type'] ?? '', ['academic', 'general'], true) ? $_POST['exam_type'] : 'academic';
    $targetBand = max(4.0, min(9.0, (float)($_POST['target_band'] ?? 7.5)));
    $newPass    = (string)($_POST['new_password'] ?? '');

    if ($newPass !== '') {
        if (strlen($newPass) < 8) {
            set_flash('error', 'New password must be at least 8 characters.');
            header('Location: ' . BASE_URL . '/profile.php');
            exit;
        }
        $stmt = $pdo->prepare('UPDATE users SET full_name = :n, exam_type = :e, target_band = :b, password_hash = :h WHERE id = :id');
        $stmt->execute([
            ':n'  => $fullName,
            ':e'  => $examType,
            ':b'  => $targetBand,
            ':h'  => password_hash($newPass, PASSWORD_BCRYPT),
            ':id' => current_user_id(),
        ]);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET full_name = :n, exam_type = :e, target_band = :b WHERE id = :id');
        $stmt->execute([
            ':n'  => $fullName,
            ':e'  => $examType,
            ':b'  => $targetBand,
            ':id' => current_user_id(),
        ]);
    }
    $_SESSION['user_name'] = $fullName;
    set_flash('success', 'Profile preferences updated.');
    header('Location: ' . BASE_URL . '/profile.php');
    exit;
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:600px;margin:0 auto;">
    <h2>Student Profile &amp; Target Band Settings</h2>
    <form method="post" action="profile.php">
        <?= csrf_field() ?>
        <div class="form-group">
            <label>Email Address (Read-only)</label>
            <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
        </div>
        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" required value="<?= htmlspecialchars($user['full_name']) ?>">
        </div>
        <div class="grid-2">
            <div class="form-group">
                <label for="exam_type">Exam Track</label>
                <select id="exam_type" name="exam_type">
                    <option value="academic" <?= $user['exam_type'] === 'academic' ? 'selected' : '' ?>>Academic</option>
                    <option value="general" <?= $user['exam_type'] === 'general' ? 'selected' : '' ?>>General Training</option>
                </select>
            </div>
            <div class="form-group">
                <label for="target_band">Target Band</label>
                <select id="target_band" name="target_band">
                    <?php foreach ([6.0, 6.5, 7.0, 7.5, 8.0, 8.5, 9.0] as $b): ?>
                        <option value="<?= $b ?>" <?= ((float)$user['target_band'] == $b) ? 'selected' : '' ?>>Band <?= number_format($b, 1) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="new_password">Change Password (leave blank to keep current)</label>
            <input type="password" id="new_password" name="new_password" minlength="8">
        </div>
        <button type="submit" class="btn btn-primary">Save Profile Changes</button>
    </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
