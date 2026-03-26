<?php
require_once '../config/db.php';
require_once '../tango/api.php';
$title = 'Sync Catálogo — Tango';
require_once '../includes/header.php';

$resultado = null;

// ── Test de conexión ───────────────────────────────────────────
if (isset($_GET['test'])) {
    $r = tango_post('dummy', []);
    $resultado = ['tipo' => 'test', 'ok' => ($r['isOk'] ?? false), 'msg' => $r['Message'] ?? json_encode($r)];
}

// ── Debug: ver estructura real de respuesta ─────────────────────
if (isset($_GET['debug'])) {
    $r = tango_get('Product', ['pageSize' => 2, 'pageNumber' => 1, 'onlyEnabled' => 'true']);
    unset($r['Data']); // no mostrar los productos, solo la estructura
    header('Content-Type: application/json');
    echo json_encode($r, JSON_PRETTY_PRINT);
    exit;
}

// ── Sync productos ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_productos'])) {
    verify_csrf();

    $nuevos = 0; $actualizados = 0; $errores = [];
    $page = 1;

    do {
        $res = tango_get('Product', ['pageSize' => 500, 'pageNumber' => $page, 'onlyEnabled' => 'true']);
        if (!isset($res['Data'])) { $errores[] = 'Error obteniendo productos: ' . json_encode($res); break; }

        foreach ($res['Data'] as $p) {
            $sku  = $p['SKUCode'] ?? '';
            $desc = $p['Description'] ?? $sku;
            if (!$sku) continue;

            $existe = $pdo->prepare('SELECT id FROM productos WHERE codigo_tango = ?');
            $existe->execute([$sku]);
            $existe = $existe->fetchColumn();

            if ($existe) {
                $pdo->prepare("UPDATE productos SET nombre=?, activo=? WHERE codigo_tango=?")
                    ->execute([$desc, isset($p['Disabled']) && $p['Disabled'] ? 0 : 1, $sku]);
                $actualizados++;
            } else {
                $pdo->prepare("INSERT INTO productos (codigo_tango, nombre, activo) VALUES (?,?,1)")
                    ->execute([$sku, $desc]);
                $nuevos++;
            }
        }

        $totalPag = $res['TotalPages'] ?? 1;
        $page++;
    } while ($page <= $totalPag);

    $resultado = ['tipo' => 'productos', 'ok' => empty($errores),
        'msg' => "Nuevos: {$nuevos} | Actualizados: {$actualizados}" . (count($errores) ? ' | Errores: ' . implode(', ', $errores) : '')];
}

// ── Sync precios ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_precios'])) {
    verify_csrf();

    $actualizados = 0; $errores = [];
    $lista = TANGO_LISTA_PRECIO;
    $page  = 1;

    do {
        $res = tango_get('Price', ['pageSize' => 500, 'pageNumber' => $page, 'priceListNumber' => $lista]);
        if (!isset($res['Data'])) { $errores[] = 'Error: ' . json_encode($res); break; }

        foreach ($res['Data'] as $p) {
            $sku    = $p['SKUCode'] ?? '';
            $precio = (float)($p['Price'] ?? 0);
            if (!$sku || !$precio) continue;

            $rows = $pdo->prepare("UPDATE productos SET precio=? WHERE codigo_tango=?");
            $rows->execute([$precio, $sku]);
            if ($rows->rowCount()) $actualizados++;
        }

        $totalPag = $res['TotalPages'] ?? 1;
        $page++;
    } while ($page <= $totalPag);

    $resultado = ['tipo' => 'precios', 'ok' => empty($errores),
        'msg' => "Precios actualizados: {$actualizados}" . (count($errores) ? ' | ' . implode(', ', $errores) : '')];
}

// ── Sync stock ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_stock'])) {
    verify_csrf();

    $actualizados = 0; $errores = [];
    $page = 1;

    do {
        $res = tango_get('Stock', ['pageSize' => 500, 'pageNumber' => $page]);
        if (!isset($res['Data'])) { $errores[] = 'Error: ' . json_encode($res); break; }

        // Agrupar stock por SKU (puede venir de múltiples depósitos)
        $stockPorSku = [];
        foreach ($res['Data'] as $s) {
            $sku = $s['SKUCode'] ?? '';
            if (!$sku) continue;
            $stockPorSku[$sku] = ($stockPorSku[$sku] ?? 0) + (float)($s['Balance'] ?? 0);
        }

        foreach ($stockPorSku as $sku => $stock) {
            $rows = $pdo->prepare("UPDATE productos SET stock=? WHERE codigo_tango=?");
            $rows->execute([(int)$stock, $sku]);
            if ($rows->rowCount()) $actualizados++;
        }

        $totalPag = $res['TotalPages'] ?? 1;
        $page++;
    } while ($page <= $totalPag);

    $resultado = ['tipo' => 'stock', 'ok' => empty($errores),
        'msg' => "Stock actualizado: {$actualizados} productos" . (count($errores) ? ' | ' . implode(', ', $errores) : '')];
}

// Stats
$total_productos  = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
$con_tango        = $pdo->query("SELECT COUNT(*) FROM productos WHERE codigo_tango IS NOT NULL AND codigo_tango != ''")->fetchColumn();
$sin_precio       = $pdo->query("SELECT COUNT(*) FROM productos WHERE precio = 0 OR precio IS NULL")->fetchColumn();
?>

<!-- Resultado -->
<?php if ($resultado): ?>
<div class="mb-4 px-4 py-3 rounded-lg text-sm <?= $resultado['ok'] ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800' ?>">
  <strong><?= $resultado['ok'] ? '✓' : '✗' ?></strong> <?= esc($resultado['msg']) ?>
</div>
<?php endif; ?>

<!-- Estado de conexión -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <?php foreach ([
    ['Total productos CRM', $total_productos,  'text-gray-700'],
    ['Con código Tango',    $con_tango,         'text-blue-600'],
    ['Sin precio',          $sin_precio,        $sin_precio > 0 ? 'text-yellow-600' : 'text-green-600'],
    ['Lista de precios',    '#' . TANGO_LISTA_PRECIO, 'text-gray-500'],
  ] as [$label, $val, $cls]): ?>
  <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
    <p class="text-xs text-gray-400 mb-1"><?= $label ?></p>
    <p class="text-xl font-bold <?= $cls ?>"><?= esc((string)$val) ?></p>
  </div>
  <?php endforeach; ?>
</div>

<div class="grid lg:grid-cols-2 gap-4">

  <!-- Acciones de sync -->
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
    <h2 class="font-semibold text-gray-800 mb-4">Sincronización manual</h2>
    <div class="space-y-3">

      <form method="POST">
        <?= csrf_field() ?>
        <button name="sync_productos" value="1"
          class="w-full flex items-center justify-between px-4 py-3 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors text-left">
          <div>
            <p class="text-sm font-medium text-blue-800">Sync Productos</p>
            <p class="text-xs text-blue-600">Importa el catálogo completo desde Tango</p>
          </div>
          <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
        </button>
      </form>

      <form method="POST">
        <?= csrf_field() ?>
        <button name="sync_precios" value="1"
          class="w-full flex items-center justify-between px-4 py-3 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-colors text-left">
          <div>
            <p class="text-sm font-medium text-green-800">Sync Precios</p>
            <p class="text-xs text-green-600">Actualiza precios desde lista <?= TANGO_LISTA_PRECIO ?></p>
          </div>
          <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </button>
      </form>

      <form method="POST">
        <?= csrf_field() ?>
        <button name="sync_stock" value="1"
          class="w-full flex items-center justify-between px-4 py-3 bg-yellow-50 border border-yellow-200 rounded-lg hover:bg-yellow-100 transition-colors text-left">
          <div>
            <p class="text-sm font-medium text-yellow-800">Sync Stock</p>
            <p class="text-xs text-yellow-600">Actualiza el stock disponible desde Tango</p>
          </div>
          <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
          </svg>
        </button>
      </form>

    </div>
  </div>

  <!-- Webhooks y config -->
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-4">
    <div>
      <h2 class="font-semibold text-gray-800 mb-3">Conexión con Tango API</h2>
      <a href="?test=1"
        class="inline-flex items-center gap-2 text-sm text-blue-600 border border-blue-300 bg-blue-50 px-4 py-2 rounded-lg hover:bg-blue-100">
        Probar conexión (dummy)
      </a>
    </div>

    <div class="border-t border-gray-100 pt-4">
      <h3 class="text-sm font-semibold text-gray-700 mb-2">Webhook URL</h3>
      <p class="text-xs text-gray-500 mb-2">Configurá esta URL en el portal de Tango Tiendas para recibir actualizaciones automáticas de stock, precios y facturas:</p>
      <code class="block text-xs bg-gray-50 border border-gray-200 rounded px-3 py-2 break-all">
        https://crm.trivium.ar/tango/webhook.php
      </code>
    </div>

    <div class="border-t border-gray-100 pt-4">
      <h3 class="text-sm font-semibold text-gray-700 mb-2">Configuración actual</h3>
      <div class="text-xs text-gray-500 space-y-1">
        <p>Depósito: <strong><?= TANGO_DEPOSITO ?></strong></p>
        <p>Vendedor: <strong><?= TANGO_VENDEDOR ?></strong></p>
        <p>Condición de venta: <strong><?= TANGO_CONDICION_VENTA ?></strong></p>
        <p>Lista de precios: <strong><?= TANGO_LISTA_PRECIO ?></strong></p>
      </div>
      <a href="<?= BASE_URL ?>/tango/config_form.php" class="text-xs text-blue-600 hover:underline mt-2 inline-block">Cambiar configuración →</a>
    </div>
  </div>

</div>

<?php require_once '../includes/footer.php'; ?>
