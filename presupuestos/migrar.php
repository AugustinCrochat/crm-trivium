<?php
require_once '../config/db.php';

$sqls = [
    "ALTER TABLE presupuesto_items ADD COLUMN IF NOT EXISTS iva DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER precio_unitario",
];

foreach ($sqls as $sql) {
    try {
        $pdo->exec($sql);
        echo "<p style='color:green'>OK: " . htmlspecialchars($sql) . "</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red'>ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
echo "<p><strong>Listo. <a href='/presupuestos/'>Volver a presupuestos</a></strong></p>";
