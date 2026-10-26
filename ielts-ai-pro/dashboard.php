<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/scoring.php';
require_login();

$pdo  = get_db();
$user = current_user();
$uid  = current_user_id();

// Fetch per-module average band scores strictly for the logged-in user
$stmtStats = $pdo->prepare('
    SELECT module, COUNT(*) AS attempts_count, AVG(band_score) AS avg_band, MAX(band_score) AS best_band
    FROM attempts
    WHERE user_id = :uid AND status = "completed"
    GROUP BY module
');
$stmtStats->execute([':uid' => $uid]);
$moduleStats = [
    'reading'   => ['count' => 0, 'avg' => 0.0, 'best' => 0.0],
    'listening' => ['count' => 0, 'avg' => 0.0, 'best' => 0.0],
    'writing'   => ['count' => 0, 'avg' => 0.0, 'best' => 0.0],
    'speaking'  => ['count' => 0, 'avg' => 0.0, 'best' => 0.0],
];
foreach ($stmtStats->fetchAll() as $row) {
    $m = $row['module'];
    if (isset($moduleStats[$m])) {
        $moduleStats[$m] = [
            'count' => (int)$row['attempts_count'],
            'avg'   => round_ielts_band((float)$row['avg_band']),
            'best'  => round_ielts_band((float)$row['best_band']),
        ];
    }
}

$activeBands = array_filter(array_column($moduleStats, 'avg'), fn($b) => $b > 0);
$overallProjected = count($activeBands) > 0
    ? round_ielts_band(array_sum($activeBands) / count($activeBands))
    : 0.0;

// Recent attempts strictly isolated to logged-in user
$stmtRecent = $pdo->prepare('
    SELECT a.id, a.module, a.raw_score, a.max_score, a.band_score, a.time_spent_seconds, a.completed_at,
           t.title AS test_title
    FROM attempts a
    JOIN tests t ON t.id = a.test_id
    WHERE a.user_id = :uid AND a.status = "completed"
    ORDER BY a.completed_at DESC
    LIMIT 10
');
$stmtRecent->execute([':uid' => $uid]);
$recentAttempts = $stmtRecent->fetchAll();

// Latest study plan for this user
$stmtPlan = $pdo->prepare('SELECT * FROM study_plans WHERE user_id = :uid ORDER BY created_at DESC LIMIT 1');
$stmtPlan->execute([':uid' => $uid]);
$latestPlan = $stmtPlan->fetch();

$pageTitle = 'Student Dashboard';
require_once __DIR__ . '/includes/header.php';
?>
<div class="dashboard-header">
    <div>
        <h1>Welcome, <?= htmlspecialchars($user['full_name']) ?></h1>
        <p class="text-muted">
            Track: <strong><?= ucfirst(htmlspecialchars($user['exam_type'])) ?> IELTS</strong> |
            Target Band: <strong><?= number_format((float)$user['target_band'], 1) ?></strong> |
            Current Projected Overall Band: <strong><?= $overallProjected > 0 ? number_format($overallProjected, 1) : 'N/A (Take a test)' ?></strong>
        </p>
    </div>
    <div>
        <button type="button" id="btn-generate-plan" class="btn btn-primary">Generate AI Study Plan</button>
    </div>
</div>

<div class="grid-4" style="margin-top:1.25rem;">
    <?php foreach (['reading' => 'Reading', 'listening' => 'Listening', 'writing' => 'Writing', 'speaking' => 'Speaking'] as $key => $label): ?>
        <div class="card stat-card">
            <span class="stat-label"><?= $label ?> Module</span>
            <div class="stat-band">Band <?= number_format($moduleStats[$key]['avg'], 1) ?></div>
            <p class="text-muted">Best: <?= number_format($moduleStats[$key]['best'], 1) ?> &bull; <?= $moduleStats[$key]['count'] ?> attempt(s)</p>
            <a href="<?= htmlspecialchars(BASE_URL . '/' . $key . '.php') ?>" class="btn btn-sm btn-outline">Start <?= $label ?> &rarr;</a>
        </div>
    <?php endforeach; ?>
</div>

<div id="study-plan-container" style="margin-top:1.5rem;">
    <?php if ($latestPlan):
        $planItems = json_decode((string)$latestPlan['plan_json'], true) ?: [];
    ?>
        <div class="card">
            <h3>Your Personalized AI 7-Day Study Plan (Focus: <?= ucfirst(htmlspecialchars($latestPlan['weakest_module'])) ?>)</h3>
            <ul class="plan-list">
                <?php foreach ($planItems as $item): ?>
                    <li><strong><?= htmlspecialchars($item['day'] ?? '') ?>:</strong> <?= htmlspecialchars($item['task'] ?? '') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<div class="card" style="margin-top:1.5rem;">
    <h3>My Recent Exam Attempts (Private to Your Account)</h3>
    <?php if (empty($recentAttempts)): ?>
        <p class="text-muted">You haven't completed any practice tests yet. Choose a module above to begin!</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Attempt ID</th>
                    <th>Test Title</th>
                    <th>Module</th>
                    <th>Raw Score</th>
                    <th>Band Score</th>
                    <th>Time Spent</th>
                    <th>Completed At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentAttempts as $att): ?>
                    <tr>
                        <td>#<?= (int)$att['id'] ?></td>
                        <td><?= htmlspecialchars($att['test_title']) ?></td>
                        <td><span class="pill"><?= ucfirst(htmlspecialchars($att['module'])) ?></span></td>
                        <td><?= (int)$att['raw_score'] ?> / <?= (int)$att['max_score'] ?></td>
                        <td><strong>Band <?= number_format((float)$att['band_score'], 1) ?></strong></td>
                        <td><?= gmdate('i:s', (int)$att['time_spent_seconds']) ?></td>
                        <td><?= htmlspecialchars((string)$att['completed_at']) ?></td>
                        <td><a href="<?= htmlspecialchars(BASE_URL) ?>/attempt_view.php?id=<?= (int)$att['id'] ?>" class="btn btn-sm btn-outline">Review</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
