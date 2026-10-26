<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/scoring.php';

header('Content-Type: application/json; charset=utf-8');
require_login(true);
require_valid_csrf();

$pdo = get_db();
$user = current_user();
$uid = current_user_id();

$stmt = $pdo->prepare('
    SELECT module, AVG(band_score) AS avg_band
    FROM attempts
    WHERE user_id = :uid AND status = "completed"
    GROUP BY module
');
$stmt->execute([':uid' => $uid]);
$avgs = [
    'reading'   => 6.0,
    'listening' => 6.0,
    'writing'   => 5.5,
    'speaking'  => 6.0,
];
foreach ($stmt->fetchAll() as $r) {
    $avgs[$r['module']] = round_ielts_band((float)$r['avg_band']);
}
asort($avgs);
$weakestModule = (string)array_key_first($avgs);
$currentBand = round_ielts_band(array_sum($avgs) / count($avgs));
$targetBand = (float)($user['target_band'] ?? 7.5);

$plan = [
    ['day' => 'Day 1', 'task' => "Diagnostic review & core strategies for " . ucfirst($weakestModule) . " (Target: Band {$targetBand})."],
    ['day' => 'Day 2', 'task' => 'Timed Academic Reading passage practice: focus on True/False/Not Given keyword scanning.'],
    ['day' => 'Day 3', 'task' => 'Writing Task 2 essay structure drill: write 250+ words with 4 clear paragraphs and cohesive markers.'],
    ['day' => 'Day 4', 'task' => 'Listening Section 3 & 4 academic dialogue note-taking and distractor identification.'],
    ['day' => 'Day 5', 'task' => 'Speaking Part 2 Cue Card recording (1-min prep, 2-min uninterrupted speech) + transcript self-audit.'],
    ['day' => 'Day 6', 'task' => 'Writing Task 1 data comparison overview practice (150+ words in 20 minutes).'],
    ['day' => 'Day 7', 'task' => 'Full timed simulation across all 4 modules and error log review.'],
];

$ins = $pdo->prepare('
    INSERT INTO study_plans (user_id, current_band, target_band, weakest_module, plan_json)
    VALUES (:uid, :cband, :tband, :weak, :plan)
');
$ins->execute([
    ':uid'   => $uid,
    ':cband' => $currentBand,
    ':tband' => $targetBand,
    ':weak'  => $weakestModule,
    ':plan'  => json_encode($plan),
]);

echo json_encode([
    'success'        => true,
    'weakest_module' => $weakestModule,
    'current_band'   => $currentBand,
    'target_band'    => $targetBand,
    'plan'           => $plan,
]);
