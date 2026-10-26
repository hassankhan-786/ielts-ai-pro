<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = get_db();
$testId = (int)($_GET['test_id'] ?? 3);
$stmt = $pdo->prepare('SELECT * FROM tests WHERE id = :id AND module = "writing" AND is_active = 1 LIMIT 1');
$stmt->execute([':id' => $testId]);
$test = $stmt->fetch();
if (!$test) {
    $stmt = $pdo->query('SELECT * FROM tests WHERE module = "writing" AND is_active = 1 ORDER BY id ASC LIMIT 1');
    $test = $stmt->fetch();
}

$qStmt = $pdo->prepare('SELECT * FROM questions WHERE test_id = :tid ORDER BY question_number ASC');
$qStmt->execute([':tid' => $test['id']]);
$questions = $qStmt->fetchAll();

$pageTitle = 'IELTS Writing Exam (Task 1 & Task 2)';
require_once __DIR__ . '/includes/header.php';
?>
<div class="exam-topbar card">
    <div>
        <h2><?= htmlspecialchars($test['title']) ?></h2>
        <p class="text-muted"><?= htmlspecialchars($test['description'] ?? '') ?></p>
    </div>
    <div class="timer-box" id="exam-timer" data-duration="<?= (int)$test['duration_minutes'] * 60 ?>">
        <span class="timer-label">Time Remaining:</span>
        <span class="timer-digits" id="timer-display"><?= sprintf('%02d:00', (int)$test['duration_minutes']) ?></span>
    </div>
</div>

<div class="card" style="margin-bottom:1.25rem;">
    <?= $test['passage_or_script'] ?>
</div>

<form id="writing-exam-form" data-endpoint="<?= htmlspecialchars(BASE_URL) ?>/api/submit_writing.php">
    <?= csrf_field() ?>
    <input type="hidden" name="test_id" value="<?= (int)$test['id'] ?>">
    <input type="hidden" name="time_spent_seconds" id="time_spent_seconds" value="0">

    <?php foreach ($questions as $q):
        $qid = (int)$q['id'];
        $minWords = ($q['question_type'] === 'writing_task1') ? 150 : 250;
    ?>
        <div class="card" style="margin-bottom:1.25rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;">
                <h3><?= htmlspecialchars($q['question_text']) ?></h3>
                <span class="pill word-counter" id="wc-<?= $qid ?>" data-min="<?= $minWords ?>">0 / <?= $minWords ?>+ words</span>
            </div>
            <textarea
                name="answers[<?= $qid ?>]"
                class="essay-textarea"
                data-counter="wc-<?= $qid ?>"
                rows="10"
                placeholder="Write your complete IELTS response here (minimum <?= $minWords ?> words recommended)..."
            ></textarea>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary btn-block" id="btn-submit-exam">Submit Both Writing Tasks for AI Examiner Band Evaluation</button>
</form>
<script src="<?= htmlspecialchars(BASE_URL) ?>/assets/js/timer.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
