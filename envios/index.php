<?php
require_once '../config/db.php';
$title = 'Envíos';
require_once '../includes/header.php';

$estado = $_GET['estado'] ?? 'todos';
$tipo   = $_GET['tipo']   ?? 'todos';

$where  = [];
$params = [];

if ($estado !== 'todos') {
    $where[]  = 'e.estado = ?';
    $params[] = $estado;
}
if ($tipo !== 'todos') {
    $where[]  = 'e.tipo = ?';
    $params[] = $tipo;
}

$sql = "
    SELECT e.*,
           c.nombre AS cliente_nombre, c.ciudad,
           t.nombre AS transporte_nombre,
           vj.fecha AS viaje_fecha
    FROM envios e
    LEFT JOIN clientes c    ON c.id  = e.cliente_id
    LEFT JOIN transportes t ON t.id  = e.transporte_id
    LEFT JOIN viajes vj     ON vj.id = e.viaje_id
";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY e.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$envios = $stmt->fetchAll();

$labels_estado = ['todos'=>'Todos','pendiente'=>'Pendiente','en_transito'=>'En tránsito','entregado'=>'Entregado'];
$labels_tipo   = ['todos'=>'Todos','expreso'=>'Expreso','camion_plancha_deposito'=>'Camión → Depósito','camion_plancha_directo'=>'Camión → Directo'];
?>

<div class="flex items-center justify-between mb-3 gap-3">
  <h2 class="text-sm font-medium text-gray-500">Listado de envíos</h2>
  <a href="<?= BASE_URL ?>/envios/nuevo.php"
    class="flex-shrink-0 bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700">
    + Nuevo
  </a>
</div>

<!-- Filtros -->
<div class="flex flex-wrap gap-1 mb-2">
  <?php foreach ($labels_estado as $key => $lbl): ?>
  <a href="?estado=<?= $key ?>&tipo=<?= esc($tipo) ?>"
    class="flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium transition-colors
    <?= $estado === $key ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
    <?= $lbl ?>
  </a>
  <?php endforeach; ?>
</div>
<div class="flex flex-wrap gap-1 mb-4">
  <?php foreach ($labels_tipo as $key => $lbl): ?>
  <a href="?estado=<?= esc($estado) ?>&tipo=<?= $key ?>"
    class="flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium transition-colors
    <?= $tipo === $key ? 'bg-gray-700 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
    <?= $lbl ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if (!$envios): ?>
<div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
  <p class="text-gray-400 text-sm">No hay envíos</p>
</div>
<?php else: ?>
<div class="bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100">
  <?php foreach ($envios as $e): ?>
  <a href="<?= BASE_URL ?>/envios/ver.php?id=<?= $e['id'] ?>"
    class="flex items-center gap-4 px-4 py-3.5 hover:bg-gray-50 transition-colors">
    <div class="min-w-0 flex-1">
      <p class="text-sm font-medium text-gray-900 truncate"><?= esc($e['cliente_nombre'] ?: 'Sin cliente') ?></p>
      <p class="text-xs text-gray-400">
        <?= esc($e['ciudad'] ?: '') ?>
        <?= $e['transporte_nombre'] ? ' · ' . esc($e['transporte_nombre']) : '' ?>
        <?= $e['viaje_fecha'] ? ' · Viaje ' . fecha($e['viaje_fecha']) : '' ?>
      </p>
      <p class="text-xs text-gray-400 mt-0.5"><?= tipo_envio($e['tipo']) ?></p>
    </div>
    <div class="flex-shrink-0"><?= badge($e['estado']) ?></div>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
