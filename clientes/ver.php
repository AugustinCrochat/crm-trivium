<?php
require_once '../config/db.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM clientes WHERE id = ?');
$stmt->execute([$id]);
$c = $stmt->fetch();
if (!$c) { flash('Cliente no encontrado.','error'); redirect('/clientes/'); }

$title = esc($c['nombre']);

// Presupuestos del cliente
$presupuestos = $pdo->prepare("SELECT * FROM presupuestos WHERE cliente_id = ? ORDER BY fecha DESC LIMIT 20");
$presupuestos->execute([$id]);
$presupuestos = $presupuestos->fetchAll();

// Ventas del cliente
$ventas = $pdo->prepare("SELECT * FROM ventas WHERE cliente_id = ? ORDER BY fecha DESC LIMIT 20");
$ventas->execute([$id]);
$ventas = $ventas->fetchAll();

// Envíos del cliente
$envios = $pdo->prepare("
    SELECT e.*, t.nombre AS transporte_nombre
    FROM envios e
    LEFT JOIN transportes t ON t.id = e.transporte_id
    WHERE e.cliente_id = ? ORDER BY e.created_at DESC LIMIT 10
");
$envios->execute([$id]);
$envios = $envios->fetchAll();

require_once '../includes/header.php';
?>

<!-- Cabecera del cliente -->
<div class="flex items-start justify-between gap-4 mb-5">
  <div class="flex items-center gap-4">
    <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-lg font-bold flex-shrink-0">
      <?= strtoupper(substr($c['nombre'], 0, 1)) ?>
    </div>
    <div>
      <h2 class="text-lg font-semibold text-gray-900"><?= esc($c['nombre']) ?></h2>
      <?php if ($c['empresa']): ?>
      <p class="text-sm text-gray-500"><?= esc($c['empresa']) ?></p>
      <?php endif; ?>
      <?= badge($c['estado']) ?>
    </div>
  </div>
  <a href="<?= BASE_URL ?>/clientes/editar.php?id=<?= $id ?>"
    class="flex-shrink-0 bg-white border border-gray-300 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-50">
    Editar
  </a>
</div>

<!-- Datos de contacto -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-5 grid sm:grid-cols-2 gap-3 text-sm">
  <?php
  $campos = [
    'Teléfono'  => $c['telefono'],
    'Email'     => $c['email'],
    'Ciudad'    => $c['ciudad'] . ($c['provincia'] ? ', ' . $c['provincia'] : ''),
    'Dirección' => $c['direccion'],
  ];
  foreach ($campos as $label => $val):
    if (!trim((string)$val)) continue;
  ?>
  <div>
    <p class="text-xs text-gray-400 mb-0.5"><?= $label ?></p>
    <p class="text-gray-800"><?= esc($val) ?></p>
  </div>
  <?php endforeach; ?>
  <?php if ($c['notas']): ?>
  <div class="sm:col-span-2">
    <p class="text-xs text-gray-400 mb-0.5">Notas</p>
    <p class="text-gray-700 whitespace-pre-wrap"><?= esc($c['notas']) ?></p>
  </div>
  <?php endif; ?>
</div>

<!-- Presupuestos -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-5">
  <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
    <h3 class="text-sm font-semibold text-gray-800">Presupuestos</h3>
    <a href="<?= BASE_URL ?>/presupuestos/nuevo.php?cliente_id=<?= $id ?>"
      class="text-xs text-blue-600 hover:underline">+ Nuevo</a>
  </div>
  <?php if (!$presupuestos): ?>
  <p class="text-sm text-gray-400 text-center py-6">Sin presupuestos</p>
  <?php else: ?>
  <ul class="divide-y divide-gray-50">
    <?php foreach ($presupuestos as $p): ?>
    <li class="px-4 py-3 flex items-center justify-between gap-3">
      <div>
        <a href="<?= BASE_URL ?>/presupuestos/ver.php?id=<?= $p['id'] ?>"
          class="text-sm font-medium text-blue-600 hover:underline">#<?= $p['id'] ?></a>
        <p class="text-xs text-gray-400"><?= fecha($p['fecha']) ?></p>
      </div>
      <div class="text-right">
        <p class="text-sm font-semibold"><?= money($p['total']) ?></p>
        <?= badge($p['estado']) ?>
      </div>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</div>

<!-- Ventas -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-5">
  <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
    <h3 class="text-sm font-semibold text-gray-800">Ventas</h3>
    <a href="<?= BASE_URL ?>/ventas/nueva.php?cliente_id=<?= $id ?>"
      class="text-xs text-blue-600 hover:underline">+ Nueva</a>
  </div>
  <?php if (!$ventas): ?>
  <p class="text-sm text-gray-400 text-center py-6">Sin ventas</p>
  <?php else: ?>
  <ul class="divide-y divide-gray-50">
    <?php foreach ($ventas as $v): ?>
    <li class="px-4 py-3 flex items-center justify-between gap-3">
      <div>
        <a href="<?= BASE_URL ?>/ventas/ver.php?id=<?= $v['id'] ?>"
          class="text-sm font-medium text-blue-600 hover:underline">#<?= $v['id'] ?></a>
        <p class="text-xs text-gray-400"><?= fecha($v['fecha']) ?></p>
      </div>
      <div class="text-right">
        <p class="text-sm font-semibold"><?= money($v['total']) ?></p>
        <?= badge($v['estado']) ?>
      </div>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</div>

<!-- Envíos -->
<?php if ($envios): ?>
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
  <div class="px-4 py-3 border-b border-gray-100">
    <h3 class="text-sm font-semibold text-gray-800">Envíos</h3>
  </div>
  <ul class="divide-y divide-gray-50">
    <?php foreach ($envios as $e): ?>
    <li class="px-4 py-3">
      <div class="flex items-center justify-between gap-3">
        <div>
          <p class="text-sm font-medium text-gray-800"><?= tipo_envio($e['tipo']) ?></p>
          <p class="text-xs text-gray-400">
            <?= fecha($e['fecha_envio']) ?>
            <?= $e['transporte_nombre'] ? ' · ' . esc($e['transporte_nombre']) : '' ?>
          </p>
        </div>
        <?= badge($e['estado']) ?>
      </div>
    </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
