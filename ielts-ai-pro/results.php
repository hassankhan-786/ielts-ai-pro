<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = get_db();
$uid = current_user_id();

// Strict student isolation: only fetch attempts belonging to current_user_id()
$stmt = $pdo->prepare('
    SELECT a.*, t.title AS test_title
    FROM attempts a
    JOIN tests t ON t.id = a.test_id
    WHERE a.user_id = :uid AND a.status = "completed"
    ORDER BY a.completed_at DESC
');
$stmt->execute([':uid' => $uid]);
$attempts = $stmt->fetchAll();

$pageTitle = 'My Exam Results';
require_once __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h2>My IELTS Exam History &amp; Band Reports</h2>
    <p class="text-muted">All test results below are strictly private to your student account.</p>

    <?php if (empty($attempts)): ?>
        <p>No completed exam attempts found yet. Take a Reading, Listening, Writing, or Speaking test to see your detailed band breakdown.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Test</th>
                    <th>Module</th>
                    <th>Raw Score</th>
                    <th>Band Score</th>
                    <th>Duration</th>
                    <th>Date</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attempts as $a): ?>
                    <tr>
                        <td>#<?= (int)$a['id'] ?></td>
                        <td><?= htmlspecialchars($a['test_title']) ?></td>
                        <td><span class="pill"><?= ucfirst(htmlspecialchars($a['module'])) ?></span></td>
                        <td><?= (int)$a['raw_score'] ?> / <?= (int)$a['max_score'] ?></td>
                        <td><strong>Band <?= number_format((float)$a['band_score'], 1) ?></strong></td>
                        <td><?= gmdate('i:s', (int)$a['time_spent_seconds']) ?></td>
                        <td><?= htmlspecialchars((string)$a['completed_at']) ?></td>
                        <td>
                            <a href="<?= htmlspecialchars(BASE_URL) ?>/attempt_view.php?id=<?= (int)$a['id'] ?>" class="btn btn-sm btn-primary">View Report</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
