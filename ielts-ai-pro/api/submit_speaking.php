<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/scoring.php';
require_once __DIR__ . '/../includes/ai_helper.php';

header('Content-Type: application/json; charset=utf-8');
require_login(true);
require_valid_csrf();

$pdo = get_db();
$uid = current_user_id();
$testId = (int)($_POST['test_id'] ?? 0);
$timeSpent = max(0, (int)($_POST['time_spent_seconds'] ?? 0));
$submittedAnswers = is_array($_POST['answers'] ?? null) ? $_POST['answers'] : [];

$stmtTest = $pdo->prepare('SELECT * FROM tests WHERE id = :id AND module = "speaking" LIMIT 1');
$stmtTest->execute([':id' => $testId]);
$test = $stmtTest->fetch();
if (!$test) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Speaking test not found.']);
    exit;
}

$stmtQ = $pdo->prepare('SELECT * FROM questions WHERE test_id = :tid ORDER BY question_number ASC');
$stmtQ->execute([':tid' => $testId]);
$questions = $stmtQ->fetchAll();

$pdo->beginTransaction();
try {
    $insAttempt = $pdo->prepare('
        INSERT INTO attempts (user_id, test_id, module, status, started_at, completed_at, time_spent_seconds, raw_score, max_score, band_score)
        VALUES (:uid, :tid, "speaking", "completed", NOW() - INTERVAL :spent SECOND, NOW(), :spent2, :raw, :maxq, 0.0)
    ');
    $insAttempt->execute([
        ':uid'    => $uid,
        ':tid'    => $testId,
        ':spent'  => $timeSpent,
        ':spent2' => $timeSpent,
        ':raw'    => count($questions),
        ':maxq'   => count($questions),
    ]);
    $attemptId = (int)$pdo->lastInsertId();

    $insAns = $pdo->prepare('
        INSERT INTO attempt_answers (attempt_id, question_id, user_answer, is_correct, score_awarded, ai_comment)
        VALUES (:aid, :qid, :uans, NULL, :score, :comm)
    ');

    $combinedTranscript = '';
    $combinedPrompts = '';
    foreach ($questions as $q) {
        $qid = (int)$q['id'];
        $ansText = isset($submittedAnswers[$qid]) ? trim((string)$submittedAnswers[$qid]) : '';
        $combinedTranscript .= "
" . $ansText;
        $combinedPrompts .= "
" . $q['question_text'];
        $partEval = evaluate_speaking_response((string)$q['question_text'], $ansText);

        $insAns->execute([
            ':aid'   => $attemptId,
            ':qid'   => $qid,
            ':uans'  => $ansText,
            ':score' => (float)$partEval['band'],
            ':comm'  => $partEval['summary'] ?? '',
        ]);
    }

    $fullEval = evaluate_speaking_response($combinedPrompts, trim($combinedTranscript));
    $bandScore = (float)$fullEval['band'];

    $upd = $pdo->prepare('
        UPDATE attempts
        SET band_score = :band, criteria_scores_json = :crit, ai_feedback_json = :fb
        WHERE id = :id AND user_id = :uid
    ');
    $upd->execute([
        ':band' => $bandScore,
        ':crit' => json_encode($fullEval['criteria']),
        ':fb'   => json_encode([
            'summary'      => $fullEval['summary'],
            'strengths'    => $fullEval['strengths'],
            'improvements' => $fullEval['improvements'],
        ]),
        ':id'   => $attemptId,
        ':uid'  => $uid,
    ]);

    $pdo->commit();
    echo json_encode([
        'success'      => true,
        'attempt_id'   => $attemptId,
        'band_score'   => $bandScore,
        'redirect_url' => BASE_URL . '/attempt_view.php?id=' . $attemptId,
    ]);
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to evaluate speaking: ' . $e->getMessage()]);
}
