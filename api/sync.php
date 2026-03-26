<?php
/**
 * api/sync.php — Placeholder para la integración con Tango Gestión.
 *
 * Estructura prevista:
 *  - Header requerido: Authorization: Bearer {SYNC_SECRET}
 *  - GET  /api/sync.php?action=productos  → devuelve catálogo
 *  - POST /api/sync.php?action=productos  → recibe/actualiza catálogo desde Tango
 *  - POST /api/sync.php?action=ventas     → marca ventas como descontadas en Tango
 *
 * TODO: Implementar cuando se configure el agente local de Tango.
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

// Verificación del token de sincronización
$sync_secret = 'CAMBIAR_ESTE_SECRET'; // Cambiar por un valor seguro
$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if ($auth_header !== 'Bearer ' . $sync_secret) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'productos':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Devolver catálogo para sincronización
            $productos = $pdo->query("SELECT id, codigo_tango, nombre, precio, stock FROM productos WHERE activo=1")->fetchAll();
            echo json_encode(['ok' => true, 'data' => $productos]);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // TODO: Recibir actualización de Tango (precios, stock)
            http_response_code(501);
            echo json_encode(['error' => 'No implementado aún', 'todo' => 'Actualizar productos desde Tango']);
        }
        break;

    case 'ventas':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // TODO: Recibir confirmación de venta descontada en Tango
            http_response_code(501);
            echo json_encode(['error' => 'No implementado aún', 'todo' => 'Marcar ventas sincronizadas con Tango']);
        }
        break;

    case 'status':
        // Health check
        echo json_encode(['ok' => true, 'version' => '1.0', 'status' => 'placeholder']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida. Acciones disponibles: productos, ventas, status']);
}
