<?php
/**
 * Endpoint JSON para búsqueda de productos.
 * Uso: /catalogo/buscar.php?q=texto
 * Retorna: [{ id, nombre, precio, stock, codigo_tango }]
 */
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, nombre, precio, stock, codigo_tango
    FROM productos
    WHERE activo = 1 AND (nombre LIKE ? OR codigo_tango LIKE ?)
    ORDER BY nombre ASC
    LIMIT 15
");
$like = "%{$q}%";
$stmt->execute([$like, $like]);
echo json_encode($stmt->fetchAll());
