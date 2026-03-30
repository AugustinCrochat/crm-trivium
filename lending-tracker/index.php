<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$user = current_user();
if (!$user) {
    redirect('/login.php');
}

if ($user['role'] === 'admin') {
    redirect('/admin/dashboard.php');
}

redirect('/lender/dashboard.php');

