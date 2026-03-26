<?php
/**
 * importaciones/migrar.php — Crea las tablas del módulo Importaciones.
 * Ejecutar una vez desde el navegador y luego eliminar o proteger.
 */
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/db.php';

$tablas = [
    'forwarders' => "
        CREATE TABLE IF NOT EXISTS forwarders (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            nombre     VARCHAR(200) NOT NULL,
            contacto   VARCHAR(200),
            telefono   VARCHAR(50),
            email      VARCHAR(100),
            notas      TEXT,
            activo     TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'importaciones' => "
        CREATE TABLE IF NOT EXISTS importaciones (
            id                INT AUTO_INCREMENT PRIMARY KEY,
            proveedor         VARCHAR(200),
            origen            VARCHAR(100),
            familia_productos VARCHAR(200),
            numero_proforma   VARCHAR(100),
            monto_fob         DECIMAL(12,2),
            etd               DATE,
            eta               DATE,
            numero_bl         VARCHAR(100),
            nombre_barco      VARCHAR(200),
            forwarder_id      INT DEFAULT NULL,
            observaciones     TEXT,
            estado            ENUM('pendiente','embarcado','arribado','cerrado') DEFAULT 'pendiente',
            created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (forwarder_id) REFERENCES forwarders(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'importacion_documentos' => "
        CREATE TABLE IF NOT EXISTS importacion_documentos (
            id               INT AUTO_INCREMENT PRIMARY KEY,
            importacion_id   INT NOT NULL,
            tipo             ENUM('archivo','link') NOT NULL,
            nombre           VARCHAR(200) NOT NULL,
            url              VARCHAR(500),
            archivo_path     VARCHAR(300),
            created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (importacion_id) REFERENCES importaciones(id) ON DELETE CASCADE
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
<title>Migración Importaciones</title>
<script src="https://cdn.tailwindcss.com"></script>
</head><body class="bg-gray-100 p-8 font-sans">
<div class="max-w-lg mx-auto bg-white rounded-xl border border-gray-200 shadow p-6 space-y-3">
  <h1 class="text-lg font-bold text-gray-800">Migración — Módulo Importaciones</h1>
  <?php foreach ($ok as $t): ?>
  <p class="text-green-700 text-sm">✓ Tabla <strong><?= esc($t) ?></strong> creada (o ya existía)</p>
  <?php endforeach; ?>
  <?php foreach ($err as $e): ?>
  <p class="text-red-700 text-sm">✗ <?= esc($e) ?></p>
  <?php endforeach; ?>
  <?php if (!$err): ?>
  <p class="text-sm text-gray-500 pt-2 border-t border-gray-100">Todo OK — podés eliminar este archivo.</p>
  <a href="<?= BASE_URL ?>/importaciones/" class="inline-block mt-2 bg-blue-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-blue-700">
    Ir a Importaciones →
  </a>
  <?php endif; ?>
</div>
</body></html>
