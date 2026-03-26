<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('/presupuestos/'); }
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
if (!$id) { flash('ID inválido.','error'); redirect('/presupuestos/'); }

$pdo->prepare("DELETE FROM presupuesto_items WHERE presupuesto_id = ?")->execute([$id]);
$pdo->prepare("DELETE FROM presupuestos WHERE id = ?")->execute([$id]);

flash('Presupuesto eliminado.');
redirect('/presupuestos/');
