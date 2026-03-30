<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'Administrador de Préstamos');
define('BASE_PATH', dirname(__DIR__));

// Derivamos la base desde la URL actual para que funcione aunque lo subas a otra carpeta.
// Ej: si entrás a /lending-tracker/login.php => BASE_URL = /lending-tracker
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$base = rtrim(str_replace('\\', '/', (string) dirname($scriptName)), '/');
define('BASE_URL', $base === '/' ? '' : $base);

