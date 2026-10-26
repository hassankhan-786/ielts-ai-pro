<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/scoring.php';

header('Content-Type: application/json; charset=utf-8');
require_login(true);
require_valid_csrf();

$pdo = get_db();
$uid = current_user_id();
$testId = (int)($_POST['test_id'] ?? 0);
$timeSpent = max(0, (int)($_POST['time_spent_seconds'] ?? 0));
$submittedAnswers = is_array($_POST['answers'] ?? null) ? $_POST['answers'] : [];

$stmtTest = $pdo->prepare('SELECT * FROM tests WHERE id = :id AND module = "reading" LIMIT 1');
$stmtTest->execute([':id' => $testId]);
$test = $stmtTest->fetch();
if (!$test) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Selected test not found.']);
    exit;
}

$stmtQ = $pdo->prepare('SELECT * FROM questions WHERE test_id = :tid ORDER BY question_number ASC');
$stmtQ->execute([':tid' => $testId]);
$questions = $stmtQ->fetchAll();

$totalQuestions = count($questions);
$rawCorrect = 0;

$pdo->beginTransaction();
try {
    $insAttempt = $pdo->prepare('
        INSERT INTO attempts (user_id, test_id, module, status, started_at, completed_at, time_spent_seconds, raw_score, max_score, band_score)
        VALUES (:uid, :tid, "reading", "completed", NOW() - INTERVAL :spent SECOND, NOW(), :spent2, 0, :maxq, 0.0)
    ');
    $insAttempt->execute([
        ':uid'    => $uid,
        ':tid'    => $testId,
        ':spent'  => $timeSpent,
        ':spent2' => $timeSpent,
        ':maxq'   => $totalQuestions,
    ]);
    $attemptId = (int)$pdo->lastInsertId();

    $insAns = $pdo->prepare('
        INSERT INTO attempt_answers (attempt_id, question_id, user_answer, is_correct, score_awarded, ai_comment)
        VALUES (:aid, :qid, :uans, :corr, :score, :comm)
    ');

    foreach ($questions as $q) {
        $qid = (int)$q['id'];
        $userVal = isset($submittedAnswers[$qid]) ? trim((string)$submittedAnswers[$qid]) : '';
        $isCorrect = check_objective_answer($userVal, (string)$q['correct_answer']);
        if ($isCorrect) {
            $rawCorrect++;
        }
        $insAns->execute([
            ':aid'   => $attemptId,
            ':qid'   => $qid,
            ':uans'  => $userVal,
            ':corr'  => $isCorrect ? 1 : 0,
            ':score' => $isCorrect ? 1.0 : 0.0,
            ':comm'  => $q['explanation'] ?? null,
        ]);
    }

    $bandScore = calculate_reading_band($rawCorrect, $totalQuestions, (string)$test['exam_type']);
    $accuracy = $totalQuestions > 0 ? round(($rawCorrect / $totalQuestions) * 100, 1) . '%' : '0%';
    $criteriaJson = json_encode([
        'raw_score' => "{$rawCorrect} / {$totalQuestions}",
        'accuracy'  => $accuracy,
        'band_score' => $bandScore,
    ]);
    $feedbackJson = json_encode([
        'summary' => "You answered {$rawCorrect} out of {$totalQuestions} questions correctly ({$accuracy}), achieving an official IELTS Band {$bandScore}.",
        'strengths' => $rawCorrect >= ($totalQuestions * 0.7)
            ? ['Strong comprehension and accuracy under timed conditions.']
            : ['Completed the timed module and recorded baseline score.'],
        'improvements' => $rawCorrect < $totalQuestions
            ? ['Review the explanations for missed items in the question-by-question breakdown below.']
            : ['Maintain this high precision across full-length mock tests.'],
    ]);

    $upd = $pdo->prepare('
        UPDATE attempts
        SET raw_score = :raw, band_score = :band, criteria_scores_json = :crit, ai_feedback_json = :fb
        WHERE id = :id AND user_id = :uid
    ');
    $upd->execute([
        ':raw'  => $rawCorrect,
        ':band' => $bandScore,
        ':crit' => $criteriaJson,
        ':fb'   => $feedbackJson,
        ':id'   => $attemptId,
        ':uid'  => $uid,
    ]);

    $pdo->commit();
    echo json_encode([
        'success'      => true,
        'attempt_id'   => $attemptId,
        'band_score'   => $bandScore,
        'raw_score'    => $rawCorrect,
        'max_score'    => $totalQuestions,
        'redirect_url' => BASE_URL . '/attempt_view.php?id=' . $attemptId,
    ]);
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save attempt: ' . $e->getMessage()]);
}
