<?php
require_once '../config/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('/clientes/'); }
verify_csrf();
$id = (int)($_POST['id'] ?? 0);
$pdo->prepare('DELETE FROM clientes WHERE id = ?')->execute([$id]);
flash('Cliente eliminado.');
redirect('/clientes/');
