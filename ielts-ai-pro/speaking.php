<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = get_db();
$testId = (int)($_GET['test_id'] ?? 4);
$stmt = $pdo->prepare('SELECT * FROM tests WHERE id = :id AND module = "speaking" AND is_active = 1 LIMIT 1');
$stmt->execute([':id' => $testId]);
$test = $stmt->fetch();
if (!$test) {
    $stmt = $pdo->query('SELECT * FROM tests WHERE module = "speaking" AND is_active = 1 ORDER BY id ASC LIMIT 1');
    $test = $stmt->fetch();
}

$qStmt = $pdo->prepare('SELECT * FROM questions WHERE test_id = :tid ORDER BY question_number ASC');
$qStmt->execute([':tid' => $test['id']]);
$questions = $qStmt->fetchAll();

$pageTitle = 'IELTS Speaking Exam & Voice Recorder';
require_once __DIR__ . '/includes/header.php';
?>
<div class="exam-topbar card">
    <div>
        <h2><?= htmlspecialchars($test['title']) ?></h2>
        <p class="text-muted"><?= htmlspecialchars($test['description'] ?? '') ?></p>
    </div>
    <div class="timer-box" id="exam-timer" data-duration="<?= (int)$test['duration_minutes'] * 60 ?>">
        <span class="timer-label">Session Timer:</span>
        <span class="timer-digits" id="timer-display"><?= sprintf('%02d:00', (int)$test['duration_minutes']) ?></span>
    </div>
</div>

<div class="card" style="margin-bottom:1.25rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
        <div>
            <h3>Part 2 Cue Card Preparation &amp; Speaking Stopwatch</h3>
            <p class="text-muted">Use the 1-minute preparation timer and 2-minute speaking timer for Part 2, or record directly into any part below using your microphone.</p>
        </div>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
            <button type="button" class="btn btn-sm btn-outline" id="btn-prep-timer">Start 1-Min Prep Timer</button>
            <button type="button" class="btn btn-sm btn-outline" id="btn-speak-timer">Start 2-Min Cue Card Timer</button>
            <span class="pill" id="cue-timer-status">Ready</span>
        </div>
    </div>
</div>

<form id="speaking-exam-form" data-endpoint="<?= htmlspecialchars(BASE_URL) ?>/api/submit_speaking.php">
    <?= csrf_field() ?>
    <input type="hidden" name="test_id" value="<?= (int)$test['id'] ?>">
    <input type="hidden" name="time_spent_seconds" id="time_spent_seconds" value="0">

    <?php foreach ($questions as $q):
        $qid = (int)$q['id'];
    ?>
        <div class="card" style="margin-bottom:1.25rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;">
                <h3><?= htmlspecialchars($q['question_text']) ?></h3>
                <div style="display:flex;gap:0.5rem;">
                    <button type="button" class="btn btn-sm btn-primary btn-mic-record" data-target="speak-input-<?= $qid ?>">
                        Record with Microphone (Speech-to-Text)
                    </button>
                </div>
            </div>
            <textarea
                id="speak-input-<?= $qid ?>"
                name="answers[<?= $qid ?>]"
                class="essay-textarea"
                rows="5"
                placeholder="Speak using the microphone button above or type your spoken transcript here..."
            ></textarea>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary btn-block" id="btn-submit-exam">Submit Speaking Test for AI Examiner Band Evaluation</button>
</form>
<script src="<?= htmlspecialchars(BASE_URL) ?>/assets/js/timer.js"></script>
<script src="<?= htmlspecialchars(BASE_URL) ?>/assets/js/recorder.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
