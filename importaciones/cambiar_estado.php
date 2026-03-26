<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('/importaciones/'); }
verify_csrf();

$id     = (int)($_POST['importacion_id'] ?? 0);
$estado = $_POST['nuevo_estado'] ?? '';
$validos = ['pendiente','embarcado','arribado','cerrado'];

if ($id && in_array($estado, $validos)) {
    $pdo->prepare("UPDATE importaciones SET estado=? WHERE id=?")->execute([$estado, $id]);
}

$ref = $_SERVER['HTTP_REFERER'] ?? '';
$redirect = (strpos($ref, '/importaciones/') !== false) ? $ref : '/importaciones/';
header('Location: ' . $redirect);
exit;
