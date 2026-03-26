<?php
require_once 'config/db.php';
$title = 'Dashboard';
require_once 'includes/header.php';

// KPIs
$clientes_activos  = $pdo->query("SELECT COUNT(*) FROM clientes WHERE estado IN ('activo','en_envio')")->fetchColumn();
$presupuestos_pend = $pdo->query("SELECT COUNT(*) FROM presupuestos WHERE estado IN ('borrador','enviado')")->fetchColumn();
$ventas_mes        = $pdo->query("SELECT COALESCE(SUM(total),0) FROM ventas WHERE MONTH(fecha)=MONTH(NOW()) AND YEAR(fecha)=YEAR(NOW()) AND estado != 'cancelada'")->fetchColumn();
$envios_en_curso   = $pdo->query("SELECT COUNT(*) FROM envios WHERE estado = 'en_transito'")->fetchColumn();

// Últimas ventas
$ultimas_ventas = $pdo->query("
    SELECT v.id, v.fecha, v.total, v.estado, c.nombre AS cliente
    FROM ventas v
    LEFT JOIN clientes c ON c.id = v.cliente_id
    ORDER BY v.created_at DESC LIMIT 5
")->fetchAll();

// Próximos viajes
$proximos_viajes = $pdo->query("
    SELECT v.id, v.fecha, v.descripcion, v.estado,
           COUNT(e.id) AS cant_envios
    FROM viajes v
    LEFT JOIN envios e ON e.viaje_id = v.id
    WHERE v.estado != 'completado' AND v.fecha >= CURDATE()
    GROUP BY v.id
    ORDER BY v.fecha ASC LIMIT 5
")->fetchAll();
?>

<!-- KPIs -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <?php
  $kpis = [
    ['Clientes activos',       $clientes_activos,              'text-blue-600',  'bg-blue-50'],
    ['Presupuestos pendientes', $presupuestos_pend,             'text-yellow-600','bg-yellow-50'],
    ['Ventas del mes',          money($ventas_mes),             'text-green-600', 'bg-green-50'],
    ['Envíos en curso',         $envios_en_curso,               'text-purple-600','bg-purple-50'],
  ];
  foreach ($kpis as [$label, $value, $textCls, $bgCls]):
  ?>
  <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
    <p class="text-xs text-gray-500 mb-1"><?= $label ?></p>
    <p class="text-2xl font-bold <?= $textCls ?>"><?= esc((string)$value) ?></p>
  </div>
  <?php endforeach; ?>
</div>

<div class="grid lg:grid-cols-2 gap-6">

  <!-- Últimas ventas -->
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
      <h2 class="font-semibold text-gray-800 text-sm">Últimas ventas</h2>
      <a href="<?= BASE_URL ?>/ventas/" class="text-xs text-blue-600 hover:underline">Ver todas</a>
    </div>
    <?php if (!$ultimas_ventas): ?>
    <p class="text-sm text-gray-400 text-center py-8">Sin ventas registradas</p>
    <?php else: ?>
    <ul class="divide-y divide-gray-50">
      <?php foreach ($ultimas_ventas as $v): ?>
      <li class="px-5 py-3 flex items-center justify-between gap-3">
        <div class="min-w-0">
          <p class="text-sm font-medium text-gray-800 truncate"><?= esc($v['cliente'] ?? 'Sin cliente') ?></p>
          <p class="text-xs text-gray-400"><?= fecha($v['fecha']) ?></p>
        </div>
        <div class="text-right flex-shrink-0">
          <p class="text-sm font-semibold text-gray-800"><?= money($v['total']) ?></p>
          <?= badge($v['estado']) ?>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>

  <!-- Próximos viajes -->
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
      <h2 class="font-semibold text-gray-800 text-sm">Próximos viajes</h2>
      <a href="<?= BASE_URL ?>/viajes/" class="text-xs text-blue-600 hover:underline">Ver todos</a>
    </div>
    <?php if (!$proximos_viajes): ?>
    <p class="text-sm text-gray-400 text-center py-8">Sin viajes planificados</p>
    <?php else: ?>
    <ul class="divide-y divide-gray-50">
      <?php foreach ($proximos_viajes as $vj): ?>
      <li class="px-5 py-3 flex items-center justify-between gap-3">
        <div class="min-w-0">
          <p class="text-sm font-medium text-gray-800 truncate"><?= esc($vj['descripcion'] ?: 'Viaje') ?></p>
          <p class="text-xs text-gray-400"><?= fecha($vj['fecha']) ?> · <?= (int)$vj['cant_envios'] ?> envíos</p>
        </div>
        <div class="flex-shrink-0">
          <?= badge($vj['estado']) ?>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>

</div>

<?php require_once 'includes/footer.php'; ?>
