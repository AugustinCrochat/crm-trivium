<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'Administrador de Préstamos');
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', '/lending-tracker');

