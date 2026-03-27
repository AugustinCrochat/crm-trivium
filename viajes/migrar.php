<?php
/**
 * viajes/migrar.php — Crea las tablas del módulo Viajes.
 * Ejecutar una vez desde el navegador y luego eliminar o proteger.
 */
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/db.php';

$tablas = [
    'viajes_notas' => "
        CREATE TABLE IF NOT EXISTS viajes_notas (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            texto       VARCHAR(500) NOT NULL,
            completado  TINYINT(1) DEFAULT 0,
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
];

$ok = [];
$err = [];
foreach ($tablas as $nombre => $sql) {
    try {
        $pdo->exec($sql);
        $ok[] = $nombre;
    } catch (PDOException $e) {
        $err[] = $nombre . ': ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">
<title>Migración Viajes</title>
<script src="https://cdn.tailwindcss.com"></script>
</head><body class="bg-gray-100 p-8 font-sans">
<div class="max-w-lg mx-auto bg-white rounded-xl border border-gray-200 shadow p-6 space-y-3">
  <h1 class="text-lg font-bold text-gray-800">Migración — Módulo Viajes</h1>
  <?php foreach ($ok as $t): ?>
  <p class="text-green-700 text-sm">✓ Tabla <strong><?= esc($t) ?></strong> creada (o ya existía)</p>
  <?php endforeach; ?>
  <?php foreach ($err as $e): ?>
  <p class="text-red-700 text-sm">✗ <?= esc($e) ?></p>
  <?php endforeach; ?>
  <?php if (!$err): ?>
  <p class="text-sm text-gray-500 pt-2 border-t border-gray-100">Todo OK — podés eliminar este archivo.</p>
  <a href="<?= BASE_URL ?>/viajes/" class="inline-block mt-2 bg-blue-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-blue-700">
    Ir a Viajes →
  </a>
  <?php endif; ?>
</div>
</body></html>
