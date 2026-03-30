<?php
declare(strict_types=1);

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $cachedUser = null;
    if ($cachedUser !== null) {
        return $cachedUser;
    }

    $stmt = db()->prepare('SELECT id, name, email, role, is_active FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || (int) $user['is_active'] !== 1) {
        unset($_SESSION['user_id']);
        return null;
    }

    $cachedUser = $user;
    return $cachedUser;
}

function require_auth(): array
{
    $user = current_user();
    if (!$user) {
        flash('error', 'Please login first.');
        redirect('/login.php');
    }

    return $user;
}

function require_role(string $role): array
{
    $user = require_auth();
    if ($user['role'] !== $role) {
        flash('error', 'You do not have access to this page.');
        redirect('/index.php');
    }

    return $user;
}

