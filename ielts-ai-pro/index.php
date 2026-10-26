<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Home - Official 9-Band IELTS Practice & AI Examiner';
require_once __DIR__ . '/includes/header.php';
?>
<section class="hero card">
    <div class="hero-content">
        <span class="pill">Local XAMPP &amp; Cloud HTTPS Ready</span>
        <h1>Master All 4 IELTS Modules with Instant Band Scoring</h1>
        <p class="lead">
            Practice Academic &amp; General Training <strong>Reading</strong>, <strong>Listening</strong>,
            <strong>Writing</strong> (Tasks 1 &amp; 2), and <strong>Speaking</strong> (Parts 1&ndash;3) with
            real-time countdown timers, official half-band rounding, and AI rubric evaluation.
        </p>
        <div class="hero-actions">
            <?php if (is_logged_in()): ?>
                <a href="<?= htmlspecialchars(BASE_URL) ?>/dashboard.php" class="btn btn-primary">Go to My Dashboard &rarr;</a>
                <a href="<?= htmlspecialchars(BASE_URL) ?>/reading.php" class="btn btn-outline">Start Reading Test</a>
            <?php else: ?>
                <a href="<?= htmlspecialchars(BASE_URL) ?>/login.php" class="btn btn-primary">Sign In with Demo Account</a>
                <a href="<?= htmlspecialchars(BASE_URL) ?>/register.php" class="btn btn-outline">Create Student Account</a>
                <a href="<?= htmlspecialchars(BASE_URL) ?>/install.php" class="btn btn-outline">1-Click XAMPP DB Setup</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="grid-4" style="margin-top:1.75rem;">
    <div class="card module-card">
        <h3>1. Reading Module</h3>
        <p>Split-screen passage reader with True/False/Not Given, Multiple Choice, and Fill-in-the-Blank questions plus a 60-minute auto-submitting timer.</p>
        <a href="<?= htmlspecialchars(BASE_URL) ?>/reading.php" class="btn btn-sm btn-primary">Practice Reading</a>
    </div>
    <div class="card module-card">
        <h3>2. Listening Module</h3>
        <p>Integrated multi-speaker audio synthesizer (works 100% offline on XAMPP) + HTML5 audio support, 30-minute timer, and instant band conversion.</p>
        <a href="<?= htmlspecialchars(BASE_URL) ?>/listening.php" class="btn btn-sm btn-primary">Practice Listening</a>
    </div>
    <div class="card module-card">
        <h3>3. Writing Module</h3>
        <p>Task 1 (150+ words) &amp; Task 2 (250+ words) with live word counters, weighted 1:2 scoring, and 4-criteria IELTS rubric evaluation.</p>
        <a href="<?= htmlspecialchars(BASE_URL) ?>/writing.php" class="btn btn-sm btn-primary">Practice Writing</a>
    </div>
    <div class="card module-card">
        <h3>4. Speaking Module</h3>
        <p>Parts 1, 2 (Cue Card with 1-min prep + 2-min speech timer), and 3 with browser microphone recording, live speech-to-text, and AI examiner feedback.</p>
        <a href="<?= htmlspecialchars(BASE_URL) ?>/speaking.php" class="btn btn-sm btn-primary">Practice Speaking</a>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
