<?php
require_once '../config/db.php';
$title  = 'Nuevo envío';
$errors = [];

$venta_id_default = (int)($_GET['venta_id'] ?? 0);

$clientes    = $pdo->query("SELECT id, nombre, empresa, ciudad FROM clientes ORDER BY nombre")->fetchAll();
$transportes = $pdo->query("SELECT id, nombre FROM transportes WHERE activo=1 ORDER BY nombre")->fetchAll();
$viajes      = $pdo->query("SELECT id, fecha, descripcion FROM viajes WHERE estado != 'completado' ORDER BY fecha")->fetchAll();

// Si viene con venta_id, preseleccionar cliente
$venta_cliente_id = 0;
if ($venta_id_default) {
    $vc = $pdo->prepare("SELECT cliente_id FROM ventas WHERE id = ?");
    $vc->execute([$venta_id_default]);
    $vc = $vc->fetch();
    if ($vc) $venta_cliente_id = (int)$vc['cliente_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $cliente_id    = (int)($_POST['cliente_id']    ?? 0) ?: null;
    $venta_id      = (int)($_POST['venta_id']      ?? 0) ?: null;
    $transporte_id = (int)($_POST['transporte_id'] ?? 0) ?: null;
    $viaje_id      = (int)($_POST['viaje_id']      ?? 0) ?: null;
    $tipo          = $_POST['tipo']          ?? 'expreso';
    $estado        = $_POST['estado']        ?? 'pendiente';
    $fecha_envio   = $_POST['fecha_envio']   ?? null;
    $remito        = trim($_POST['remito']   ?? '');
    $notas         = trim($_POST['notas']    ?? '');

    $tipos_validos = ['expreso','camion_plancha_deposito','camion_plancha_directo'];
    if (!in_array($tipo, $tipos_validos)) $errors[] = 'Tipo de envío inválido.';

    if (!$errors) {
        $pdo->prepare("
            INSERT INTO envios (venta_id,cliente_id,transporte_id,viaje_id,tipo,estado,fecha_envio,remito,notas)
            VALUES (?,?,?,?,?,?,?,?,?)
        ")->execute([$venta_id, $cliente_id, $transporte_id, $viaje_id, $tipo, $estado,
                     $fecha_envio ?: null, $remito, $notas]);
        $eid = $pdo->lastInsertId();

        // Actualizar estado del cliente
        if ($cliente_id) {
            $pdo->prepare("UPDATE clientes SET estado='en_envio' WHERE id=? AND estado IN ('activo','prospecto')")
                ->execute([$cliente_id]);
        }

        flash('Envío creado.');
        redirect('/envios/ver.php?id=' . $eid);
    }
}

require_once '../includes/header.php';
?>

<div class="max-w-lg">
  <a href="<?= BASE_URL ?>/viajes/" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Volver
  </a>

  <?php if ($errors): ?>
  <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
    <?= implode('<br>', array_map('esc', $errors)) ?>
  </div>
  <?php endif; ?>

  <form method="POST" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-4">
    <?= csrf_field() ?>
    <input type="hidden" name="venta_id" value="<?= $venta_id_default ?>">

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de envío *</label>
      <select name="tipo" id="tipo-select" onchange="actualizarCampos()"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="expreso">Expreso (larga distancia)</option>
        <option value="camion_plancha_deposito">Camión plancha → Depósito de expreso</option>
        <option value="camion_plancha_directo">Camión plancha → Entrega directa</option>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
      <select name="cliente_id"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">— Sin asignar —</option>
        <?php foreach ($clientes as $cl): ?>
        <option value="<?= $cl['id'] ?>" <?= (int)$cl['id'] === $venta_cliente_id ? 'selected' : '' ?>>
          <?= esc($cl['nombre']) ?><?= $cl['empresa'] ? ' — ' . esc($cl['empresa']) : '' ?>
          <?= $cl['ciudad'] ? ' (' . esc($cl['ciudad']) . ')' : '' ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Campo: Transporte (solo para expreso y deposito) -->
    <div id="campo-transporte">
      <label class="block text-sm font-medium text-gray-700 mb-1">Transporte</label>
      <select name="transporte_id"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">— Sin asignar —</option>
        <?php foreach ($transportes as $t): ?>
        <option value="<?= $t['id'] ?>"><?= esc($t['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Campo: Viaje (solo para camión plancha) -->
    <div id="campo-viaje" class="hidden">
      <label class="block text-sm font-medium text-gray-700 mb-1">Viaje (camión plancha)</label>
      <select name="viaje_id"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">— Sin asignar —</option>
        <?php foreach ($viajes as $vj): ?>
        <option value="<?= $vj['id'] ?>"><?= fecha($vj['fecha']) ?><?= $vj['descripcion'] ? ' — ' . esc($vj['descripcion']) : '' ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de envío</label>
        <input type="date" name="fecha_envio"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
        <select name="estado"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="pendiente">Pendiente</option>
          <option value="en_transito">En tránsito</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nº Remito</label>
        <input type="text" name="remito"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
      <textarea name="notas" rows="2"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
    </div>

    <div class="flex justify-end gap-3 pt-2">
      <a href="<?= BASE_URL ?>/viajes/" class="text-sm text-gray-500 px-4 py-2">Cancelar</a>
      <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-5 py-2 rounded-lg hover:bg-blue-700">
        Crear envío
      </button>
    </div>
  </form>
</div>

<script>
function actualizarCampos() {
  const tipo = document.getElementById('tipo-select').value;
  const esCamion = tipo.startsWith('camion_plancha');
  document.getElementById('campo-transporte').classList.toggle('hidden', tipo === 'camion_plancha_directo');
  document.getElementById('campo-viaje').classList.toggle('hidden', !esCamion);
}
actualizarCampos();
</script>

<?php require_once '../includes/footer.php'; ?>
