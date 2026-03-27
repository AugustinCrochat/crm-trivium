<?php
/**
 * api/envios_notas.php — CRUD de notas/todo para el módulo de envíos.
 *
 * Maneja items tipo "to-do list" que se muestran arriba del listado de envíos.
 * Acciones vía POST (AJAX, JSON body):
 *   action=add      → texto (string)
 *   action=toggle   → id (int)
 *   action=delete   → id (int)
 *   action=list     → (sin params) devuelve todas las notas
 */

require_once dirname(__DIR__) . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar sesión
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Leer input
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $rows = $pdo->query("SELECT * FROM envios_notas ORDER BY completado ASC, created_at DESC")->fetchAll();
        echo json_encode(['ok' => true, 'data' => $rows]);
        break;

    case 'add':
        $texto = trim($input['texto'] ?? '');
        if ($texto === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Texto requerido']);
            exit;
        }
        $pdo->prepare("INSERT INTO envios_notas (texto, completado) VALUES (?, 0)")->execute([$texto]);
        $id = $pdo->lastInsertId();
        echo json_encode(['ok' => true, 'id' => (int)$id, 'texto' => $texto, 'completado' => 0]);
        break;

    case 'toggle':
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID requerido']);
            exit;
        }
        $pdo->prepare("UPDATE envios_notas SET completado = NOT completado WHERE id = ?")->execute([$id]);
        $row = $pdo->prepare("SELECT * FROM envios_notas WHERE id = ?");
        $row->execute([$id]);
        $row = $row->fetch();
        echo json_encode(['ok' => true, 'data' => $row]);
        break;

    case 'delete':
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID requerido']);
            exit;
        }
        $pdo->prepare("DELETE FROM envios_notas WHERE id = ?")->execute([$id]);
        echo json_encode(['ok' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida. Acciones: list, add, toggle, delete']);
}
