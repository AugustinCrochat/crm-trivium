<?php
require_once '../config/db.php';
$title = 'Presupuestos';
require_once '../includes/header.php';

$estado = $_GET['estado'] ?? 'todos';
$q      = trim($_GET['q'] ?? '');

$where  = [];
$params = [];

if ($estado !== 'todos') {
    $where[]  = 'p.estado = ?';
    $params[] = $estado;
}
if ($q !== '') {
    $where[]  = '(c.nombre LIKE ? OR c.empresa LIKE ?)';
    $like     = "%{$q}%";
    $params   = array_merge($params, [$like, $like]);
}

$sql = "SELECT p.*, c.nombre AS cliente_nombre, c.empresa AS cliente_empresa
        FROM presupuestos p
        LEFT JOIN clientes c ON c.id = p.cliente_id";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY p.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$presupuestos = $stmt->fetchAll();

$tabs = ['todos','borrador','enviado','aprobado','rechazado'];
?>

<div class="flex items-center justify-between mb-4 gap-3">
  <form method="GET" class="flex gap-2 flex-1 max-w-xs">
    <input type="hidden" name="estado" value="<?= esc($estado) ?>">
    <input type="search" name="q" value="<?= esc($q) ?>" placeholder="Buscar cliente…"
      class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
  </form>
  <a href="<?= BASE_URL ?>/presupuestos/nuevo.php"
    class="flex-shrink-0 bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700">
    + Nuevo
  </a>
</div>

<!-- Tabs -->
<div class="flex gap-1 mb-4 overflow-x-auto pb-1">
  <?php
  $labels = ['todos'=>'Todos','borrador'=>'Borrador','enviado'=>'Enviado','aprobado'=>'Aprobado','rechazado'=>'Rechazado'];
  foreach ($labels as $key => $lbl):
  ?>
  <a href="?estado=<?= $key ?><?= $q ? '&q=' . urlencode($q) : '' ?>"
    class="flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium transition-colors
    <?= $estado === $key
        ? 'bg-blue-600 text-white'
        : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
    <?= $lbl ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if (!$presupuestos): ?>
<div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
  <p class="text-gray-400 text-sm">No hay presupuestos</p>
</div>
<?php else: ?>
<div class="bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100">
  <?php foreach ($presupuestos as $p): ?>
  <div class="flex items-center gap-4 px-4 py-3.5 hover:bg-gray-50">
    <div class="min-w-0 flex-1">
      <div class="flex items-center gap-2">
        <a href="<?= BASE_URL ?>/presupuestos/ver.php?id=<?= $p['id'] ?>"
          class="text-sm font-medium text-blue-600 hover:underline">#<?= $p['id'] ?></a>
        <?= badge($p['estado']) ?>
      </div>
      <p class="text-sm text-gray-700 truncate mt-0.5">
        <?= esc($p['cliente_empresa'] ?: $p['cliente_nombre'] ?: 'Sin cliente') ?>
      </p>
      <p class="text-xs text-gray-400"><?= fecha($p['fecha']) ?> · válido <?= (int)$p['validez_dias'] ?> días</p>
    </div>
    <div class="flex-shrink-0 text-right">
      <p class="text-sm font-bold text-gray-800"><?= money($p['total']) ?></p>
      <div class="flex gap-2 mt-1 justify-end">
        <a href="<?= BASE_URL ?>/presupuestos/ver.php?id=<?= $p['id'] ?>" class="text-xs text-gray-500 hover:underline">Ver</a>
        <a href="<?= BASE_URL ?>/presupuestos/pdf.php?id=<?= $p['id'] ?>" class="text-xs text-blue-600 hover:underline">PDF</a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
