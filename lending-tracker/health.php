<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';

$dbOk = false;
$dbError = null;
try {
    db()->query('SELECT 1');
    $dbOk = true;
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Health</title>
    <style>
        body{font-family:Arial, sans-serif; background:#f3f5fa; color:#1e293b; margin:0}
        .wrap{max-width:900px; margin:16px auto; padding:16px}
        .card{background:#fff; border-radius:12px; padding:16px; box-shadow:0 8px 20px rgba(15,23,42,.08)}
        .ok{color:#166534}
        .err{color:#991b1b}
        code{background:#f1f5f9; padding:2px 6px; border-radius:6px}
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h2>Diagnóstico</h2>
        <p><strong>BASE_URL derivado:</strong> <code><?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?></code></p>
        <p><strong>DB_NAME:</strong> <code><?php echo htmlspecialchars((string) DB_NAME, ENT_QUOTES, 'UTF-8'); ?></code></p>
        <p><strong>Conexión a BD:</strong>
            <?php if ($dbOk): ?>
                <span class="ok">OK</span>
            <?php else: ?>
                <span class="err">FALLÓ</span>
            <?php endif; ?>
        </p>
        <?php if ($dbError): ?>
            <p class="err"><strong>Error:</strong> <?php echo htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>

