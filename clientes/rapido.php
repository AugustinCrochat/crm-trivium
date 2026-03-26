<?php
require_once '../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false]);
    exit;
}
verify_csrf();

$nombre   = trim($_POST['nombre']   ?? '');
$empresa  = trim($_POST['empresa']  ?? '');
$telefono = trim($_POST['telefono'] ?? '');

if (!$nombre) {
    echo json_encode(['ok' => false, 'error' => 'Nombre requerido']);
    exit;
}

$pdo->prepare("INSERT INTO clientes (nombre, empresa, telefono, estado) VALUES (?,?,?,'activo')")
    ->execute([$nombre, $empresa, $telefono]);

$id = $pdo->lastInsertId();
echo json_encode(['ok' => true, 'id' => $id, 'nombre' => $nombre, 'empresa' => $empresa]);
