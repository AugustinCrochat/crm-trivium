<?php
/**
 * tango/migrar.php — Agrega los campos necesarios para la integración Tango.
 * Ejecutar una sola vez y luego eliminar.
 */
require_once '../config/db.php';

$cambios = [];

$alteraciones = [
    "ALTER TABLE clientes ADD COLUMN IF NOT EXISTS cuit VARCHAR(20) DEFAULT NULL AFTER email",
    "ALTER TABLE ventas   ADD COLUMN IF NOT EXISTS tango_order_id VARCHAR(100) DEFAULT NULL AFTER sincronizado_tango",
    "ALTER TABLE ventas   ADD COLUMN IF NOT EXISTS factura_numero VARCHAR(50)  DEFAULT NULL AFTER tango_order_id",
    "ALTER TABLE ventas   ADD COLUMN IF NOT EXISTS factura_url    VARCHAR(255) DEFAULT NULL AFTER factura_numero",
    "ALTER TABLE ventas   ADD COLUMN IF NOT EXISTS factura_pdf    LONGBLOB     DEFAULT NULL AFTER factura_url",
];

foreach ($alteraciones as $sql) {
    try {
        $pdo->exec($sql);
        $cambios[] = ['ok' => true, 'sql' => $sql];
    } catch (PDOException $e) {
        // Si la columna ya existe MySQL lanza error, ignoramos
        $cambios[] = ['ok' => false, 'sql' => $sql, 'msg' => $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Migración Tango</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
<div class="bg-white rounded-xl shadow p-8 max-w-xl w-full">
  <h1 class="text-lg font-bold text-gray-800 mb-4">Migración para integración Tango</h1>
  <div class="space-y-2 mb-6">
    <?php foreach ($cambios as $c): ?>
    <div class="flex items-start gap-2 text-sm <?= $c['ok'] ? 'text-green-700' : 'text-yellow-700' ?>">
      <span><?= $c['ok'] ? '✓' : '~' ?></span>
      <div>
        <code class="text-xs"><?= esc(substr($c['sql'], 0, 80)) ?></code>
        <?php if (!$c['ok']): ?><p class="text-xs opacity-70"><?= esc($c['msg']) ?></p><?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <p class="text-red-600 text-xs font-medium mb-4">⚠️ Eliminá este archivo del servidor.</p>
  <a href="../" class="inline-block bg-blue-600 text-white text-sm font-medium px-6 py-2.5 rounded-lg hover:bg-blue-700">
    Ir al CRM →
  </a>
</div>
</body>
</html>
