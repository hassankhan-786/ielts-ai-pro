<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$pdo = get_db();
$totalUsers    = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE role = "student"')->fetchColumn();
$totalTests    = (int)$pdo->query('SELECT COUNT(*) FROM tests')->fetchColumn();
$totalAttempts = (int)$pdo->query('SELECT COUNT(*) FROM attempts WHERE status = "completed"')->fetchColumn();
$avgBand       = (float)$pdo->query('SELECT COALESCE(AVG(band_score), 0) FROM attempts WHERE status = "completed"')->fetchColumn();

$recentAttempts = $pdo->query('
    SELECT a.*, u.full_name, u.email, t.title AS test_title
    FROM attempts a
    JOIN users u ON u.id = a.user_id
    JOIN tests t ON t.id = a.test_id
    ORDER BY a.completed_at DESC
    LIMIT 15
')->fetchAll();

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-header">
    <div>
        <h1>Administrator Control Panel</h1>
        <p class="text-muted">Manage students, IELTS practice tests, AI API keys, and all exam submissions.</p>
    </div>
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
        <a href="<?= htmlspecialchars(BASE_URL) ?>/admin/users.php" class="btn btn-sm btn-outline">Manage Users</a>
        <a href="<?= htmlspecialchars(BASE_URL) ?>/admin/tests.php" class="btn btn-sm btn-outline">Manage Tests</a>
        <a href="<?= htmlspecialchars(BASE_URL) ?>/admin/settings.php" class="btn btn-sm btn-primary">AI &amp; System Settings</a>
    </div>
</div>

<div class="grid-4" style="margin-top:1.25rem;">
    <div class="card stat-card">
        <span class="stat-label">Registered Students</span>
        <div class="stat-band"><?= $totalUsers ?></div>
    </div>
    <div class="card stat-card">
        <span class="stat-label">Active IELTS Tests</span>
        <div class="stat-band"><?= $totalTests ?></div>
    </div>
    <div class="card stat-card">
        <span class="stat-label">Completed Attempts</span>
        <div class="stat-band"><?= $totalAttempts ?></div>
    </div>
    <div class="card stat-card">
        <span class="stat-label">Platform Average Band</span>
        <div class="stat-band">Band <?= number_format($avgBand, 1) ?></div>
    </div>
</div>

<div class="card" style="margin-top:1.5rem;">
    <h3>All Student Submissions</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Student</th>
                <th>Test</th>
                <th>Module</th>
                <th>Band Score</th>
                <th>Completed</th>
                <th>Inspect</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentAttempts as $att): ?>
                <tr>
                    <td>#<?= (int)$att['id'] ?></td>
                    <td><?= htmlspecialchars($att['full_name']) ?> (<?= htmlspecialchars($att['email']) ?>)</td>
                    <td><?= htmlspecialchars($att['test_title']) ?></td>
                    <td><span class="pill"><?= ucfirst(htmlspecialchars($att['module'])) ?></span></td>
                    <td><strong>Band <?= number_format((float)$att['band_score'], 1) ?></strong></td>
                    <td><?= htmlspecialchars((string)$att['completed_at']) ?></td>
                    <td><a href="<?= htmlspecialchars(BASE_URL) ?>/attempt_view.php?id=<?= (int)$att['id'] ?>" class="btn btn-sm btn-outline">Inspect</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
