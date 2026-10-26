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

$stmtTest = $pdo->prepare('SELECT * FROM tests WHERE id = :id AND module = "writing" LIMIT 1');
$stmtTest->execute([':id' => $testId]);
$test = $stmtTest->fetch();
if (!$test) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Writing test not found.']);
    exit;
}

$stmtQ = $pdo->prepare('SELECT * FROM questions WHERE test_id = :tid ORDER BY question_number ASC');
$stmtQ->execute([':tid' => $testId]);
$questions = $stmtQ->fetchAll();

$pdo->beginTransaction();
try {
    $insAttempt = $pdo->prepare('
        INSERT INTO attempts (user_id, test_id, module, status, started_at, completed_at, time_spent_seconds, raw_score, max_score, band_score)
        VALUES (:uid, :tid, "writing", "completed", NOW() - INTERVAL :spent SECOND, NOW(), :spent2, :raw, :maxq, 0.0)
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

    $task1Band = 0.0;
    $task2Band = 0.0;
    $combinedCriteria = [
        'task_achievement'   => 0.0,
        'coherence_cohesion' => 0.0,
        'lexical_resource'   => 0.0,
        'grammatical_range'  => 0.0,
    ];
    $allStrengths = [];
    $allImprovements = [];
    $evalCount = 0;

    foreach ($questions as $q) {
        $qid = (int)$q['id'];
        $essay = isset($submittedAnswers[$qid]) ? trim((string)$submittedAnswers[$qid]) : '';
        $eval = evaluate_writing_response((string)$q['question_text'], $essay, (string)$q['question_type']);

        if ($q['question_type'] === 'writing_task1') {
            $task1Band = (float)$eval['band'];
        } else {
            $task2Band = (float)$eval['band'];
        }

        foreach ($combinedCriteria as $ck => $_) {
            $combinedCriteria[$ck] += (float)($eval['criteria'][$ck] ?? $eval['band']);
        }
        $evalCount++;
        $allStrengths = array_merge($allStrengths, $eval['strengths'] ?? []);
        $allImprovements = array_merge($allImprovements, $eval['improvements'] ?? []);

        $insAns->execute([
            ':aid'   => $attemptId,
            ':qid'   => $qid,
            ':uans'  => $essay,
            ':score' => (float)$eval['band'],
            ':comm'  => $eval['summary'] ?? '',
        ]);
    }

    if ($evalCount > 0) {
        foreach ($combinedCriteria as $ck => $sumVal) {
            $combinedCriteria[$ck] = round_ielts_band($sumVal / $evalCount);
        }
    }

    $overallWritingBand = calculate_writing_band($task1Band, $task2Band);
    $feedback = [
        'summary'      => "Overall Writing Band {$overallWritingBand} (Task 1: Band {$task1Band}, Task 2: Band {$task2Band} weighted 2x).",
        'strengths'    => array_values(array_unique($allStrengths)),
        'improvements' => array_values(array_unique($allImprovements)),
    ];

    $upd = $pdo->prepare('
        UPDATE attempts
        SET band_score = :band, criteria_scores_json = :crit, ai_feedback_json = :fb
        WHERE id = :id AND user_id = :uid
    ');
    $upd->execute([
        ':band' => $overallWritingBand,
        ':crit' => json_encode($combinedCriteria),
        ':fb'   => json_encode($feedback),
        ':id'   => $attemptId,
        ':uid'  => $uid,
    ]);

    $pdo->commit();
    echo json_encode([
        'success'      => true,
        'attempt_id'   => $attemptId,
        'band_score'   => $overallWritingBand,
        'redirect_url' => BASE_URL . '/attempt_view.php?id=' . $attemptId,
    ]);
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to evaluate writing: ' . $e->getMessage()]);
}
