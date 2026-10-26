<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $action = (string)($_POST['action'] ?? '');
    $targetUserId = (int)($_POST['user_id'] ?? 0);

    if ($action === 'toggle_role' && $targetUserId > 0 && $targetUserId !== current_user_id()) {
        $stmt = $pdo->prepare('UPDATE users SET role = IF(role="admin", "student", "admin") WHERE id = :id');
        $stmt->execute([':id' => $targetUserId]);
        set_flash('success', 'Updated user role.');
    }
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

$users = $pdo->query('
    SELECT u.*, COUNT(a.id) AS attempt_count, COALESCE(AVG(a.band_score), 0) AS avg_band
    FROM users u
    LEFT JOIN attempts a ON a.user_id = u.id AND a.status = "completed"
    GROUP BY u.id
    ORDER BY u.created_at DESC
')->fetchAll();

$pageTitle = 'Manage Users - Admin';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;">
        <h2>Manage Registered Users</h2>
        <a href="<?= htmlspecialchars(BASE_URL) ?>/admin/index.php" class="btn btn-sm btn-outline">&larr; Back to Admin</a>
    </div>
    <table class="data-table" style="margin-top:1rem;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Track</th>
                <th>Target</th>
                <th>Attempts</th>
                <th>Avg Band</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td>#<?= (int)$u['id'] ?></td>
                    <td><?= htmlspecialchars($u['full_name']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="pill"><?= htmlspecialchars($u['role']) ?></span></td>
                    <td><?= ucfirst(htmlspecialchars($u['exam_type'])) ?></td>
                    <td>Band <?= number_format((float)$u['target_band'], 1) ?></td>
                    <td><?= (int)$u['attempt_count'] ?></td>
                    <td>Band <?= number_format((float)$u['avg_band'], 1) ?></td>
                    <td>
                        <?php if ((int)$u['id'] !== current_user_id()): ?>
                            <form method="post" action="users.php" style="display:inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle_role">
                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline">Toggle Admin/Student</button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted">(You)</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
