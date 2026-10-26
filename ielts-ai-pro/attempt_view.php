<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = get_db();
$attemptId = (int)($_GET['id'] ?? 0);

// Enforces strict ownership check: students get 403 Forbidden if accessing another user's attempt
$attempt = fetch_authorized_attempt($pdo, $attemptId);

$ansStmt = $pdo->prepare('
    SELECT aa.*, q.question_number, q.question_type, q.question_text, q.correct_answer, q.explanation
    FROM attempt_answers aa
    JOIN questions q ON q.id = aa.question_id
    WHERE aa.attempt_id = :aid
    ORDER BY q.question_number ASC
');
$ansStmt->execute([':aid' => $attemptId]);
$answers = $ansStmt->fetchAll();

$criteria = $attempt['criteria_scores_json'] ? json_decode((string)$attempt['criteria_scores_json'], true) : [];
$feedback = $attempt['ai_feedback_json'] ? json_decode((string)$attempt['ai_feedback_json'], true) : [];

$pageTitle = 'Band Report #' . $attemptId;
require_once __DIR__ . '/includes/header.php';
?>
<div class="card report-header-card">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
        <div>
            <span class="pill"><?= ucfirst(htmlspecialchars($attempt['module'])) ?> Band Report</span>
            <h2><?= htmlspecialchars($attempt['test_title']) ?></h2>
            <p class="text-muted">
                Candidate: <strong><?= htmlspecialchars($attempt['student_name']) ?></strong> &bull;
                Completed: <?= htmlspecialchars((string)$attempt['completed_at']) ?> &bull;
                Time Taken: <?= gmdate('i:s', (int)$attempt['time_spent_seconds']) ?>
            </p>
        </div>
        <div class="big-band-badge">
            <span>Band Score</span>
            <strong><?= number_format((float)$attempt['band_score'], 1) ?></strong>
        </div>
    </div>
</div>

<?php if (!empty($criteria)): ?>
    <div class="card" style="margin-top:1.25rem;">
        <h3>Official IELTS Criteria Breakdown</h3>
        <div class="grid-4" style="margin-top:0.75rem;">
            <?php foreach ($criteria as $k => $v): ?>
                <div class="stat-card" style="background:#f8fafc;padding:1rem;border-radius:8px;border:1px solid #e2e8f0;">
                    <span class="stat-label"><?= ucwords(str_replace('_', ' ', (string)$k)) ?></span>
                    <div class="stat-band"><?= is_numeric($v) ? 'Band ' . number_format((float)$v, 1) : htmlspecialchars((string)$v) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($feedback)): ?>
    <div class="card" style="margin-top:1.25rem;">
        <h3>AI Examiner Feedback</h3>
        <?php if (!empty($feedback['summary'])): ?>
            <p><strong>Summary:</strong> <?= htmlspecialchars((string)$feedback['summary']) ?></p>
        <?php endif; ?>
        <?php if (!empty($feedback['strengths']) && is_array($feedback['strengths'])): ?>
            <h4>Key Strengths</h4>
            <ul>
                <?php foreach ($feedback['strengths'] as $s): ?>
                    <li><?= htmlspecialchars((string)$s) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if (!empty($feedback['improvements']) && is_array($feedback['improvements'])): ?>
            <h4>Actionable Improvements for a Higher Band</h4>
            <ul>
                <?php foreach ($feedback['improvements'] as $imp): ?>
                    <li><?= htmlspecialchars((string)$imp) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="card" style="margin-top:1.25rem;">
    <h3>Question-by-Question Review</h3>
    <?php foreach ($answers as $ans): ?>
        <div class="question-block">
            <p class="question-title">
                <span class="q-num">Q<?= (int)$ans['question_number'] ?></span>
                <?= htmlspecialchars($ans['question_text']) ?>
            </p>
            <p><strong>Your Response:</strong> <?= nl2br(htmlspecialchars((string)($ans['user_answer'] ?: '(No answer submitted)'))) ?></p>
            <?php if ($ans['correct_answer'] !== null && $ans['correct_answer'] !== ''): ?>
                <p>
                    <strong>Status:</strong>
                    <?php if ((int)$ans['is_correct'] === 1): ?>
                        <span style="color:#16a34a;font-weight:700;">&check; Correct</span>
                    <?php else: ?>
                        <span style="color:#dc2626;font-weight:700;">&times; Incorrect</span>
                        (Correct Answer: <code><?= htmlspecialchars((string)$ans['correct_answer']) ?></code>)
                    <?php endif; ?>
                </p>
            <?php endif; ?>
            <?php if (!empty($ans['ai_comment'])): ?>
                <p class="text-muted"><em>Examiner Note: <?= htmlspecialchars((string)$ans['ai_comment']) ?></em></p>
            <?php elseif (!empty($ans['explanation'])): ?>
                <p class="text-muted"><em>Explanation: <?= htmlspecialchars((string)$ans['explanation']) ?></em></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
