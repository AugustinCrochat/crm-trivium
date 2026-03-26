<?php
session_start();

// ── Configuración ──────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'u982106244_crm_trivium');
define('DB_USER', 'u982106244_crm_trivium');
define('DB_PASS', '_Trivium1815');

// Raíz del proyecto (dirname de config/)
define('BASE_PATH', dirname(__DIR__));

// URL base: auto-detecta si está en subdirectorio o raíz del dominio
$_docRoot = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
$_projPath = rtrim(str_replace('\\', '/', BASE_PATH), '/');
define('BASE_URL', str_replace($_docRoot, '', $_projPath));
unset($_docRoot, $_projPath);

// ── Funciones de utilidad ───────────────────────────────────────
require_once BASE_PATH . '/includes/functions.php';

// ── Conexión PDO ───────────────────────────────────────────────
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Error de conexión a la base de datos: ' . $e->getMessage());
}

// ── Verificación de sesión ─────────────────────────────────────
$_publicPages = ['login.php', 'install.php'];
if (!in_array(basename($_SERVER['PHP_SELF']), $_publicPages) && empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}
unset($_publicPages);

// ── Token CSRF ─────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
