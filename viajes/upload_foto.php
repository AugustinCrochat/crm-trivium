<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('/viajes/'); }
verify_csrf();

$viaje_id = (int)($_POST['viaje_id'] ?? 0);
if (!$viaje_id) { flash('ID de viaje inválido.','error'); redirect('/viajes/'); }

if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
    flash('Error al subir la foto.','error');
    redirect('/viajes/ver.php?id=' . $viaje_id);
}

$file     = $_FILES['foto'];
$maxSize  = 10 * 1024 * 1024; // 10 MB

if ($file['size'] > $maxSize) {
    flash('La foto es demasiado grande (máx 10 MB).','error');
    redirect('/viajes/ver.php?id=' . $viaje_id);
}

// Verificar que sea imagen real (no solo por extensión)
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);
$allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/heic'];

if (!in_array($mime, $allowedMimes)) {
    flash('El archivo debe ser una imagen (JPG, PNG, WebP).','error');
    redirect('/viajes/ver.php?id=' . $viaje_id);
}

$extensions = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/heic' => 'jpg',
];
$ext      = $extensions[$mime] ?? 'jpg';
$filename = 'viaje_' . $viaje_id . '_' . time() . '.' . $ext;
$destDir  = BASE_PATH . '/uploads/viajes/';
$destPath = $destDir . $filename;

if (!is_dir($destDir)) {
    mkdir($destDir, 0755, true);
}

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    flash('No se pudo guardar la foto.','error');
    redirect('/viajes/ver.php?id=' . $viaje_id);
}

// Eliminar foto anterior si existe
$old = $pdo->prepare('SELECT foto_url FROM viajes WHERE id = ?');
$old->execute([$viaje_id]);
$old = $old->fetchColumn();
if ($old && file_exists(BASE_PATH . '/' . $old)) {
    unlink(BASE_PATH . '/' . $old);
}

$pdo->prepare("UPDATE viajes SET foto_url=? WHERE id=?")
    ->execute(['uploads/viajes/' . $filename, $viaje_id]);

flash('Foto actualizada.');
redirect('/viajes/ver.php?id=' . $viaje_id);
