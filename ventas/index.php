<?php
require_once '../config/db.php';
$title = 'Ventas';
require_once '../includes/header.php';

$estado           = $_GET['estado']            ?? 'todos';
$tipo_facturacion = $_GET['tipo_facturacion']  ?? 'todos';
$q                = trim($_GET['q']            ?? '');

$where  = [];
$params = [];

if ($estado !== 'todos') {
    $where[]  = 'v.estado = ?';
    $params[] = $estado;
}
if ($tipo_facturacion !== 'todos') {
    $where[]  = 'v.tipo_facturacion = ?';
    $params[] = $tipo_facturacion;
}
if ($q !== '') {
    $where[]  = '(c.nombre LIKE ? OR c.empresa LIKE ?)';
    $like     = "%{$q}%";
    $params   = array_merge($params, [$like, $like]);
}

$sql = "SELECT v.*, c.nombre AS cliente_nombre, c.empresa AS cliente_empresa
        FROM ventas v
        LEFT JOIN clientes c ON c.id = v.cliente_id";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY v.fecha DESC, v.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ventas = $stmt->fetchAll();

$labels_estado = ['todos'=>'Todos','pendiente'=>'Pendiente','confirmada'=>'Confirmada','entregada'=>'Entregada','cancelada'=>'Cancelada'];

function buildUrl(array $override): string {
    $params = array_filter(array_merge([
        'estado'           => $_GET['estado']           ?? 'todos',
        'tipo_facturacion' => $_GET['tipo_facturacion'] ?? 'todos',
        'q'                => $_GET['q']                ?? '',
    ], $override));
    return '?' . http_build_query($params);
}
?>

<div class="flex items-center justify-between mb-3 gap-3">
  <form method="GET" class="flex gap-2 flex-1 max-w-xs">
    <input type="hidden" name="estado" value="<?= esc($estado) ?>">
    <input type="hidden" name="tipo_facturacion" value="<?= esc($tipo_facturacion) ?>">
    <input type="search" name="q" value="<?= esc($q) ?>" placeholder="Buscar cliente…"
      class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
  </form>
  <a href="<?= BASE_URL ?>/ventas/nueva.php"
    class="flex-shrink-0 bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700">
    + Nueva
  </a>
</div>

<!-- Filtro tipo facturación -->
<div class="flex gap-1 mb-2 overflow-x-auto pb-1">
  <?php foreach (['todos'=>'Todas','manual'=>'Manual','facturada'=>'Facturada'] as $key => $lbl): ?>
  <a href="<?= buildUrl(['tipo_facturacion' => $key]) ?>"
    class="flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium transition-colors
    <?= $tipo_facturacion === $key ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
    <?= $lbl ?>
  </a>
  <?php endforeach; ?>
</div>

<!-- Filtro estado -->
<div class="flex gap-1 mb-4 overflow-x-auto pb-1">
  <?php foreach ($labels_estado as $key => $lbl): ?>
  <a href="<?= buildUrl(['estado' => $key]) ?>"
    class="flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium transition-colors
    <?= $estado === $key ? 'bg-gray-800 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
    <?= $lbl ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if (!$ventas): ?>
<div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
  <p class="text-gray-400 text-sm">No hay ventas</p>
</div>
<?php else: ?>
<div class="bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100">
  <?php foreach ($ventas as $v): ?>
  <div class="flex items-center gap-4 px-4 py-3.5 hover:bg-gray-50">
    <div class="min-w-0 flex-1">
      <div class="flex items-center gap-2 flex-wrap">
        <a href="<?= BASE_URL ?>/ventas/ver.php?id=<?= $v['id'] ?>"
          class="text-sm font-medium text-blue-600 hover:underline">#<?= $v['id'] ?></a>
        <?= badge($v['estado']) ?>
        <?php if (($v['tipo_facturacion'] ?? 'manual') === 'facturada'): ?>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">Facturada</span>
        <?php else: ?>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Manual</span>
        <?php endif; ?>
        <?php if (!empty($v['cobrado'])): ?>
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Cobrado</span>
        <?php endif; ?>
        <?php if ($v['sincronizado_tango']): ?>
        <span class="text-xs text-green-600 font-medium">✓ Tango</span>
        <?php endif; ?>
      </div>
      <p class="text-sm text-gray-700 truncate mt-0.5">
        <?= esc($v['cliente_empresa'] ?: $v['cliente_nombre'] ?: 'Sin cliente') ?>
      </p>
      <p class="text-xs text-gray-400">
        <?= fecha($v['fecha']) ?>
        <?= !empty($v['vendedor']) ? ' · ' . esc($v['vendedor']) : '' ?>
        <?= !empty($v['metodo_pago']) ? ' · ' . esc($v['metodo_pago']) : '' ?>
      </p>
    </div>
    <div class="flex-shrink-0 text-right">
      <p class="text-sm font-bold text-gray-800"><?= money($v['total']) ?></p>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
