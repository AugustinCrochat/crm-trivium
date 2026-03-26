<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('/importaciones/'); }
verify_csrf();

$doc_id         = (int)($_POST['doc_id']         ?? 0);
$importacion_id = (int)($_POST['importacion_id'] ?? 0);

if (!$doc_id || !$importacion_id) {
    flash('Parámetros inválidos.','error');
    redirect('/importaciones/');
}

$doc = $pdo->prepare('SELECT * FROM importacion_documentos WHERE id = ? AND importacion_id = ?');
$doc->execute([$doc_id, $importacion_id]);
$doc = $doc->fetch();

if (!$doc) {
    flash('Documento no encontrado.','error');
    redirect('/importaciones/ver.php?id=' . $importacion_id);
}

// Eliminar archivo físico si corresponde
if ($doc['tipo'] === 'archivo' && $doc['archivo_path']) {
    $path = BASE_PATH . '/' . $doc['archivo_path'];
    if (file_exists($path)) {
        unlink($path);
    }
}

$pdo->prepare('DELETE FROM importacion_documentos WHERE id = ?')->execute([$doc_id]);

flash('Documento eliminado.');
redirect('/importaciones/ver.php?id=' . $importacion_id);
