<?php
/**
 * ventas/toggle.php — AJAX: toglea un campo booleano de ventas.
 * POST: csrf_token, id, campo
 */
require_once '../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['ok'=>false]); exit;
}

verify_csrf();

$id    = (int)($_POST['id']    ?? 0);
$campo = $_POST['campo'] ?? '';

if (!$id || !in_array($campo, ['cobrado','entregado','dado_de_baja'])) {
    echo json_encode(['ok'=>false]); exit;
}

$row = $pdo->prepare("SELECT `{$campo}` FROM ventas WHERE id = ?");
$row->execute([$id]);
$row = $row->fetch();
if (!$row) { echo json_encode(['ok'=>false]); exit; }

$nuevo = $row[$campo] ? 0 : 1;
$pdo->prepare("UPDATE ventas SET `{$campo}` = ? WHERE id = ?")->execute([$nuevo, $id]);

echo json_encode(['ok'=>true, 'valor'=>$nuevo]);
