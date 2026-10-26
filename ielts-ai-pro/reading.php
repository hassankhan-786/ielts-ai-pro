<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = get_db();
$testId = (int)($_GET['test_id'] ?? 1);
$stmt = $pdo->prepare('SELECT * FROM tests WHERE id = :id AND module = "reading" AND is_active = 1 LIMIT 1');
$stmt->execute([':id' => $testId]);
$test = $stmt->fetch();
if (!$test) {
    $stmt = $pdo->query('SELECT * FROM tests WHERE module = "reading" AND is_active = 1 ORDER BY id ASC LIMIT 1');
    $test = $stmt->fetch();
}

$qStmt = $pdo->prepare('SELECT * FROM questions WHERE test_id = :tid ORDER BY question_number ASC');
$qStmt->execute([':tid' => $test['id']]);
$questions = $qStmt->fetchAll();

$pageTitle = 'IELTS Reading Exam';
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

<form id="reading-exam-form" data-endpoint="<?= htmlspecialchars(BASE_URL) ?>/api/submit_reading.php">
    <?= csrf_field() ?>
    <input type="hidden" name="test_id" value="<?= (int)$test['id'] ?>">
    <input type="hidden" name="time_spent_seconds" id="time_spent_seconds" value="0">

    <div class="split-exam-layout">
        <div class="card passage-pane">
            <div class="pane-toolbar">
                <strong>Reading Passage</strong>
                <button type="button" class="btn btn-sm btn-outline" id="btn-highlight-selection">Highlight Selected Text</button>
            </div>
            <div class="passage-content" id="reading-passage">
                <?= $test['passage_or_script'] ?>
            </div>
        </div>

        <div class="card questions-pane">
            <h3>Questions (1&ndash;<?= count($questions) ?>)</h3>
            <?php foreach ($questions as $q):
                $qid = (int)$q['id'];
                $options = $q['options_json'] ? json_decode((string)$q['options_json'], true) : [];
            ?>
                <div class="question-block" id="qblock-<?= $qid ?>">
                    <p class="question-title">
                        <span class="q-num">Q<?= (int)$q['question_number'] ?></span>
                        <?= htmlspecialchars($q['question_text']) ?>
                    </p>
                    <?php if (in_array($q['question_type'], ['mcq', 'tfng'], true) && is_array($options)): ?>
                        <div class="options-list">
                            <?php foreach ($options as $opt): ?>
                                <label class="radio-option">
                                    <input type="radio" name="answers[<?= $qid ?>]" value="<?= htmlspecialchars((string)$opt) ?>">
                                    <span><?= htmlspecialchars((string)$opt) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <input type="text" class="fill-blank-input" name="answers[<?= $qid ?>]" placeholder="Type your exact answer...">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="exam-submit-bar">
                <button type="submit" class="btn btn-primary btn-block" id="btn-submit-exam">Submit Reading Exam &amp; Calculate Band Score</button>
            </div>
        </div>
    </div>
</form>
<script src="<?= htmlspecialchars(BASE_URL) ?>/assets/js/timer.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
