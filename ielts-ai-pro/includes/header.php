<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
$pageTitle = $pageTitle ?? APP_NAME;
$user = current_user();
$flashSuccess = get_flash('success');
$flashError   = get_flash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL) ?>/assets/css/style.css">
</head>
<body>
<header class="navbar">
    <div class="container nav-inner">
        <a href="<?= htmlspecialchars(BASE_URL) ?>/index.php" class="brand">
            <span class="brand-badge">IELTS</span> AI Pro
        </a>
        <nav class="nav-links">
            <?php if ($user): ?>
                <a href="<?= htmlspecialchars(BASE_URL) ?>/dashboard.php">Dashboard</a>
                <a href="<?= htmlspecialchars(BASE_URL) ?>/reading.php">Reading</a>
                <a href="<?= htmlspecialchars(BASE_URL) ?>/listening.php">Listening</a>
                <a href="<?= htmlspecialchars(BASE_URL) ?>/writing.php">Writing</a>
                <a href="<?= htmlspecialchars(BASE_URL) ?>/speaking.php">Speaking</a>
                <a href="<?= htmlspecialchars(BASE_URL) ?>/results.php">My Results</a>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="<?= htmlspecialchars(BASE_URL) ?>/admin/index.php" class="badge-admin">Admin Panel</a>
                <?php endif; ?>
                <a href="<?= htmlspecialchars(BASE_URL) ?>/profile.php" class="user-pill">
                    <?= htmlspecialchars($user['full_name']) ?> (Band <?= number_format((float)$user['target_band'], 1) ?>)
                </a>
                <a href="<?= htmlspecialchars(BASE_URL) ?>/logout.php" class="btn btn-sm btn-outline">Logout</a>
            <?php else: ?>
                <a href="<?= htmlspecialchars(BASE_URL) ?>/login.php">Sign In</a>
                <a href="<?= htmlspecialchars(BASE_URL) ?>/register.php" class="btn btn-sm btn-primary">Register Free</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container main-content">
    <?php if ($flashSuccess): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="alert alert-error"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>
