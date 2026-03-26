<?php
require_once '../config/db.php';
require_once '../tango/api.php';
$title = 'Configuración Tango';
require_once '../includes/header.php';

$cfgFile = BASE_PATH . '/config/tango.json';
$errors  = [];
$ok      = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $cfg = [
        'deposito'        => trim($_POST['deposito']        ?? ''),
        'vendedor'        => trim($_POST['vendedor']        ?? ''),
        'condicion_venta' => trim($_POST['condicion_venta'] ?? ''),
        'lista_precio'    => trim($_POST['lista_precio']    ?? ''),
    ];
    foreach ($cfg as $k => $v) {
        if ($v === '') $errors[] = "El campo {$k} no puede estar vacío.";
    }
    if (!$errors) {
        if (file_put_contents($cfgFile, json_encode($cfg, JSON_PRETTY_PRINT)) !== false) {
            flash('Configuración guardada.');
            redirect('/tango/config_form.php');
        } else {
            $errors[] = 'No se pudo escribir el archivo de configuración. Verificá permisos de la carpeta /config/.';
        }
    }
}

// Leer valores actuales (ya cargados como constantes en api.php)
$current = [
    'deposito'        => TANGO_DEPOSITO,
    'vendedor'        => TANGO_VENDEDOR,
    'condicion_venta' => TANGO_CONDICION_VENTA,
    'lista_precio'    => TANGO_LISTA_PRECIO,
];
// Si hubo POST con error, mostrar los valores del POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errors) {
    $current = [
        'deposito'        => $_POST['deposito']        ?? '',
        'vendedor'        => $_POST['vendedor']        ?? '',
        'condicion_venta' => $_POST['condicion_venta'] ?? '',
        'lista_precio'    => $_POST['lista_precio']    ?? '',
    ];
}
?>

<div class="max-w-lg">
  <a href="<?= BASE_URL ?>/tango/catalogo.php" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Volver al catálogo Tango
  </a>

  <?php if ($errors): ?>
  <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
    <?= implode('<br>', array_map('esc', $errors)) ?>
  </div>
  <?php endif; ?>

  <form method="POST" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-4">
    <?= csrf_field() ?>

    <div>
      <h2 class="font-semibold text-gray-800 mb-1">Parámetros de Tango</h2>
      <p class="text-xs text-gray-500">Estos códigos se usan al crear órdenes en Tango. Los valores deben coincidir con los códigos configurados en tu sistema Tango Gestión.</p>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Depósito</label>
        <input type="text" name="deposito" value="<?= esc($current['deposito']) ?>" required
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="01">
        <p class="text-xs text-gray-400 mt-1">Código de depósito origen</p>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Vendedor</label>
        <input type="text" name="vendedor" value="<?= esc($current['vendedor']) ?>" required
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="01">
        <p class="text-xs text-gray-400 mt-1">Código de vendedor</p>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Condición de venta</label>
        <input type="text" name="condicion_venta" value="<?= esc($current['condicion_venta']) ?>" required
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="01">
        <p class="text-xs text-gray-400 mt-1">Código de condición de pago</p>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Lista de precios</label>
        <input type="text" name="lista_precio" value="<?= esc($current['lista_precio']) ?>" required
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="1">
        <p class="text-xs text-gray-400 mt-1">Número de lista de precios</p>
      </div>
    </div>

    <div class="border-t border-gray-100 pt-4 flex justify-end gap-3">
      <a href="<?= BASE_URL ?>/tango/catalogo.php" class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2">Cancelar</a>
      <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-5 py-2 rounded-lg hover:bg-blue-700">
        Guardar configuración
      </button>
    </div>
  </form>

  <div class="mt-4 bg-gray-50 border border-gray-200 rounded-xl p-4 text-xs text-gray-500 space-y-1">
    <p class="font-medium text-gray-700">Token API</p>
    <p class="font-mono break-all"><?= esc(TANGO_TOKEN) ?></p>
    <p class="mt-2 font-medium text-gray-700">Base URL</p>
    <p class="font-mono"><?= esc(TANGO_BASE_URL) ?></p>
    <p class="text-gray-400 mt-1">Para cambiar el token o la URL, editá directamente <code>tango/api.php</code>.</p>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
