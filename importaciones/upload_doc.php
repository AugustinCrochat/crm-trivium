<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('/importaciones/'); }
verify_csrf();

$importacion_id = (int)($_POST['importacion_id'] ?? 0);
if (!$importacion_id) { flash('ID de importación inválido.','error'); redirect('/importaciones/'); }

$redirect = '/importaciones/ver.php?id=' . $importacion_id;

if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    flash('Error al subir el archivo.','error');
    redirect($redirect);
}

$file    = $_FILES['archivo'];
$maxSize = 20 * 1024 * 1024; // 20 MB

if ($file['size'] > $maxSize) {
    flash('El archivo es demasiado grande (máx 20 MB).','error');
    redirect($redirect);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);

$allowedMimes = [
    'application/pdf'                                                      => 'pdf',
    'image/jpeg'                                                           => 'jpg',
    'image/png'                                                            => 'png',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'   => 'xlsx',
    'application/vnd.ms-excel'                                             => 'xls',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/msword'                                                   => 'doc',
];

if (!isset($allowedMimes[$mime])) {
    flash('Tipo de archivo no permitido. Solo PDF, JPG, PNG, XLSX, DOCX.','error');
    redirect($redirect);
}

$ext      = $allowedMimes[$mime];
$nombre   = trim($_POST['nombre_doc'] ?? '');
if ($nombre === '') {
    $nombre = pathinfo($file['name'], PATHINFO_FILENAME);
}

$filename = 'doc_' . $importacion_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$destDir  = BASE_PATH . '/uploads/importaciones/' . $importacion_id . '/';
$destPath = $destDir . $filename;

if (!is_dir($destDir)) {
    mkdir($destDir, 0755, true);
}

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    flash('No se pudo guardar el archivo.','error');
    redirect($redirect);
}

$archivo_path = 'uploads/importaciones/' . $importacion_id . '/' . $filename;

$pdo->prepare("INSERT INTO importacion_documentos (importacion_id, tipo, nombre, archivo_path) VALUES (?,?,?,?)")
    ->execute([$importacion_id, 'archivo', $nombre, $archivo_path]);

flash('Documento subido.');
redirect($redirect);
