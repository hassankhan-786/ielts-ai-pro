<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
logout_user();
set_flash('success', 'You have been signed out safely.');
header('Location: ' . BASE_URL . '/login.php');
exit;
