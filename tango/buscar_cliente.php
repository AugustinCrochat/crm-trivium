<?php
/**
 * tango/buscar_cliente.php
 * AJAX: GET ?q=20123456789 → devuelve datos del cliente desde tango_clientes local
 */
require_once '../config/db.php';
header('Content-Type: application/json');

$q = preg_replace('/[^0-9]/', '', trim($_GET['q'] ?? ''));

if (strlen($q) < 7) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT codigo, razon_social, nombre_fantasia, tipo_documento,
           numero_documento, iva_categoria, direccion, ciudad,
           provincia_code, codigo_postal, condicion_venta
    FROM tango_clientes
    WHERE REPLACE(REPLACE(numero_documento, '-', ''), ' ', '') LIKE ?
    ORDER BY razon_social
    LIMIT 10
");
$stmt->execute(["%{$q}%"]);
$rows = $stmt->fetchAll();

echo json_encode($rows);
