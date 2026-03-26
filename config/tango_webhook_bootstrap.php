<?php
/**
 * Bootstrap mínimo para webhook endpoints.
 * No inicia sesión, no redirige — solo conexión PDO y constantes Tango.
 */
$_wh_host = 'localhost';
$_wh_db   = 'u982106244_crm_trivium';
$_wh_user = 'u982106244_crm_trivium';
$_wh_pass = '_Trivium1815';

try {
    $pdo = new PDO(
        "mysql:host={$_wh_host};dbname={$_wh_db};charset=utf8mb4",
        $_wh_user,
        $_wh_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'DB connection failed']);
    exit;
}
unset($_wh_host, $_wh_db, $_wh_user, $_wh_pass);

require_once BASE_PATH . '/tango/api.php';
