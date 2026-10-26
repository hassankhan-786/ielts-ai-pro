<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

$messages = [];
$errors   = [];
$installed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['auto'])) {
    try {
        $dsnNoDb = sprintf('mysql:host=%s;port=%s;charset=%s', DB_HOST, DB_PORT, DB_CHARSET);
        $pdo = new PDO($dsnNoDb, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $dbNameSafe = preg_replace('/[^a-zA-Z0-9_]/', '', DB_NAME);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbNameSafe}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbNameSafe}`");
        $messages[] = "Database `{$dbNameSafe}` verified/created on " . DB_HOST . ":" . DB_PORT . ".";

        $schemaSql = file_get_contents(__DIR__ . '/database/schema.sql');
        $pdo->exec($schemaSql);
        $messages[] = "Created all 7 relational tables (`users`, `tests`, `questions`, `attempts`, `attempt_answers`, `study_plans`, `system_settings`).";

        $seedSql = file_get_contents(__DIR__ . '/database/seed.sql');
        $pdo->exec($seedSql);
        $messages[] = "Imported official IELTS Reading, Listening, Writing, and Speaking practice tests.";

        $adminHash   = password_hash('Admin@12345', PASSWORD_BCRYPT);
        $studentHash = password_hash('Student@12345', PASSWORD_BCRYPT);

        $stmtUser = $pdo->prepare('
            INSERT INTO users (id, full_name, email, password_hash, role, exam_type, target_band)
            VALUES
                (1, :aname, :aemail, :ahash, "admin", "academic", 8.5),
                (2, :sname1, :semail1, :shash1, "student", "academic", 7.5),
                (3, :sname2, :semail2, :shash2, "student", "general", 7.0)
        ');
        $stmtUser->execute([
            ':aname'   => 'Dr. Tariq Mehmood (Admin)',
            ':aemail'  => 'admin@ieltspro.local',
            ':ahash'   => $adminHash,
            ':sname1'  => 'Ali Raza (Student)',
            ':semail1' => 'student@ieltspro.local',
            ':shash1'  => $studentHash,
            ':sname2'  => 'Sara Khan (Student)',
            ':semail2' => 'sara@ieltspro.local',
            ':shash2'  => $studentHash,
        ]);

        $stmtAttempt = $pdo->prepare('
            INSERT INTO attempts (user_id, test_id, module, status, started_at, completed_at, time_spent_seconds, raw_score, max_score, band_score, criteria_scores_json, ai_feedback_json)
            VALUES
            (2, 1, "reading", "completed", NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY, 2140, 7, 8, 8.0,
             '{"accuracy":"87.5%"}', '{"summary":"Excellent reading comprehension on Urban Vertical Farming."}'),
            (3, 1, "reading", "completed", NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 1 DAY, 2600, 6, 8, 7.0,
             '{"accuracy":"75.0%"}', '{"summary":"Good performance by Sara Khan."}')
        ');
        $stmtAttempt->execute();

        $messages[] = "Created demo accounts and sample student attempts.";
        $installed = true;
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>1-Click XAMPP Installer | <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL) ?>/assets/css/style.css">
</head>
<body>
<div class="container" style="max-width:740px;padding:3rem 1rem;">
    <div class="card">
        <h1><?= htmlspecialchars(APP_NAME) ?> &mdash; Local XAMPP Installer</h1>
        <p class="text-muted">Target MySQL Server: <code><?= htmlspecialchars(DB_HOST . ':' . DB_PORT) ?></code> | Database: <code><?= htmlspecialchars(DB_NAME) ?></code> | User: <code><?= htmlspecialchars(DB_USER) ?></code></p>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-error"><strong>Error:</strong> <?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>

        <?php foreach ($messages as $msg): ?>
            <div class="alert alert-success">&check; <?= htmlspecialchars($msg) ?></div>
        <?php endforeach; ?>

        <?php if ($installed): ?>
            <div class="card" style="background:#f0fdf4;border:1px solid #bbf7d0;margin-top:1.5rem;">
                <h3>Installation Complete! Ready-to-Use Demo Credentials:</h3>
                <ul>
                    <li><strong>Admin Account:</strong> <code>admin@ieltspro.local</code> / Password: <code>Admin@12345</code></li>
                    <li><strong>Student 1 Account:</strong> <code>student@ieltspro.local</code> / Password: <code>Student@12345</code></li>
                    <li><strong>Student 2 Account (Isolation Test):</strong> <code>sara@ieltspro.local</code> / Password: <code>Student@12345</code></li>
                </ul>
                <div style="margin-top:1rem;display:flex;gap:0.75rem;flex-wrap:wrap;">
                    <a href="<?= htmlspecialchars(BASE_URL) ?>/login.php" class="btn btn-primary">Go to Login Page &rarr;</a>
                    <a href="<?= htmlspecialchars(BASE_URL) ?>/tests/acceptance_audit.php" class="btn btn-outline">Run Automated Acceptance Tests</a>
                </div>
            </div>
        <?php else: ?>
            <p>Click the button below to initialize or reset the local XAMPP database with all tables, IELTS practice tests, and demo accounts.</p>
            <form method="post" action="install.php">
                <button type="submit" class="btn btn-primary">Initialize Database &amp; Demo Data Now</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
