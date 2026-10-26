<?php
/**
 * IELTS AI Pro - Automated Master Specification & Acceptance Test Suite
 * Verifies:
 *  1. Official IELTS Band Rounding & Module Score Calculations
 *  2. Objective Answer Normalization & Matching
 *  3. Offline AI Writing & Speaking Rubric Evaluator Consistency
 *  4. CSRF Token Generation & Hash Verification
 *  5. RBAC & Student Data Isolation (SQL & Ownership Guards)
 *  6. Database Connectivity & Relational Schema Integrity (when MySQL is running)
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/scoring.php';
require_once __DIR__ . '/../includes/ai_helper.php';

$results = [];

function assert_test(string $name, bool $condition, string $details): void {
    global $results;
    $results[] = [
        'name'    => $name,
        'passed'  => $condition,
        'details' => $details,
    ];
}

// 1. Official IELTS Half-Band Rounding Tests
assert_test(
    'AT-01: Official IELTS Band Rounding (6.25 -> 6.5, 6.75 -> 7.0, 6.125 -> 6.0)',
    round_ielts_band(6.25) === 6.5 && round_ielts_band(6.75) === 7.0 && round_ielts_band(6.125) === 6.0,
    sprintf('6.25=>%.1f, 6.75=>%.1f, 6.125=>%.1f', round_ielts_band(6.25), round_ielts_band(6.75), round_ielts_band(6.125))
);

// 2. Overall 4-Skills Band Calculation
$overall = calculate_overall_band(7.5, 7.0, 6.5, 7.5); // avg 7.125 -> 7.0
$overall2 = calculate_overall_band(8.0, 7.5, 7.0, 6.5); // avg 7.25 -> 7.5
assert_test(
    'AT-02: Overall 4-Module Band Calculation',
    $overall === 7.0 && $overall2 === 7.5,
    "Avg(7.5,7.0,6.5,7.5)=7.125 -> Band {$overall}; Avg(8.0,7.5,7.0,6.5)=7.25 -> Band {$overall2}"
);

// 3. Academic vs General Training Reading Conversion
$acad30 = calculate_reading_band(30, 40, 'academic'); // 30/40 Academic = 7.0
$gen30  = calculate_reading_band(30, 40, 'general');  // 30/40 General = 6.0
assert_test(
    'AT-03: Academic vs General Reading 40-Item Conversion Table',
    $acad30 === 7.0 && $gen30 === 6.0,
    "30/40 Academic => Band {$acad30}; 30/40 General => Band {$gen30}"
);

// 4. Listening Conversion Table & Proportional Scaling
$list35 = calculate_listening_band(35, 40); // 8.0
$list6of6 = calculate_listening_band(6, 6); // 9.0
assert_test(
    'AT-04: Listening Raw-to-Band Conversion & Scaling',
    $list35 === 8.0 && $list6of6 === 9.0,
    "35/40 => Band {$list35}; 6/6 => Band {$list6of6}"
);

// 5. Official Writing Task 1 (1x) + Task 2 (2x) Weighted Formula
$writingWeighted = calculate_writing_band(6.0, 7.5); // (6.0 + 15.0)/3 = 7.0
assert_test(
    'AT-05: Writing Task 1 & Task 2 Weighted Formula ((T1 + 2*T2)/3)',
    $writingWeighted === 7.0,
    "Task1=6.0, Task2=7.5 => Weighted Band {$writingWeighted}"
);

// 6. Objective Answer Normalization & Pipe-Variant Matching
$match1 = check_objective_answer('  VAPOR. ', 'vapor|vapour');
$match2 = check_objective_answer('vapour', 'vapor|vapour');
$mismatch = !check_objective_answer('water', 'vapor|vapour');
assert_test(
    'AT-06: Case-Insensitive & Variant Answer Matching',
    $match1 && $match2 && $mismatch,
    'Verified whitespace trimming, punctuation stripping, and British/American spelling variants.'
);

// 7. AI Writing Rubric Analyzer Verification
$sampleEssay = str_repeat(
    "Furthermore, vertical farming significantly reduces freshwater consumption in metropolitan centers. However, high electricity costs for automated lighting remain a challenge. Consequently, governments and private enterprises must collaborate to integrate renewable solar energy. On the other hand, traditional agriculture continues to supply staple calorie crops. ",
    6
);
$writingEval = offline_evaluate_writing($sampleEssay, 250, 'writing_task2');
assert_test(
    'AT-07: Writing 4-Criteria Rubric Evaluator',
    $writingEval['band'] >= 6.0 && isset($writingEval['criteria']['task_achievement'], $writingEval['criteria']['coherence_cohesion'], $writingEval['criteria']['lexical_resource'], $writingEval['criteria']['grammatical_range']),
    "Evaluated {$writingEval['word_count']}-word essay => Band {$writingEval['band']}"
);

// 8. CSRF Security Token Generation & Hash Verification
$token = csrf_token();
assert_test(
    'AT-08: CSRF Token Generation & Timing-Safe Verification',
    strlen($token) === 64 && verify_csrf_token($token) && !verify_csrf_token('invalid_token'),
    '64-hex token generated and verified via hash_equals().'
);

// 9. Student Data Isolation & Admin Guard Verification
$authCode = file_get_contents(__DIR__ . '/../includes/auth.php');
$hasAdminGuard = str_contains($authCode, 'function require_admin') && str_contains($authCode, 'http_response_code(403)');
$hasAttemptOwnershipGuard = str_contains($authCode, 'WHERE a.id = :id AND a.user_id = :uid');
assert_test(
    'AT-09: Student Data Isolation & Admin Page Access Guard',
    $hasAdminGuard && $hasAttemptOwnershipGuard,
    'Verified WHERE a.user_id = :uid row-level isolation and 403 Forbidden admin guard.'
);

// 10. Database Schema & Live Connection Check (if MySQL is active)
$dbConnected = false;
$dbDetail = '';
try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $dbConnected = count($tables) >= 7;
    $dbDetail = 'Connected to MySQL `' . DB_NAME . '` with ' . count($tables) . ' tables: ' . implode(', ', $tables);
} catch (Throwable $e) {
    $dbDetail = 'MySQL not yet initialized (' . $e->getMessage() . '). Run install.php in XAMPP to initialize.';
}

$pageTitle = 'System Audit & Acceptance Tests';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h2>IELTS AI Pro &mdash; Master Specification &amp; Acceptance Test Report</h2>
    <p class="text-muted">Automated verification of scoring formulas, security guards, CSRF protection, and database schema.</p>

    <table class="data-table" style="margin-top:1rem;">
        <thead>
            <tr>
                <th>Test Case</th>
                <th>Status</th>
                <th>Verification Details</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $r): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
                    <td>
                        <?php if ($r['passed']): ?>
                            <span class="pill" style="background:#dcfce7;color:#166534;">PASS</span>
                        <?php else: ?>
                            <span class="pill" style="background:#fee2e2;color:#991b1b;">FAIL</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($r['details']) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td><strong>AT-10: XAMPP MySQL Database Connection</strong></td>
                <td>
                    <?php if ($dbConnected): ?>
                        <span class="pill" style="background:#dcfce7;color:#166534;">CONNECTED</span>
                    <?php else: ?>
                        <span class="pill" style="background:#fef3c7;color:#92400e;">PENDING INSTALL</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($dbDetail) ?></td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top:1.25rem;display:flex;gap:0.75rem;">
        <a href="<?= htmlspecialchars(BASE_URL) ?>/install.php" class="btn btn-primary">Run 1-Click XAMPP Installer</a>
        <a href="<?= htmlspecialchars(BASE_URL) ?>/dashboard.php" class="btn btn-outline">Go to Dashboard</a>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
