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

// Enviados hace +7 días sin resolver (solo si no estamos filtrando)
$pendientes_alerta = [];
if ($estado === 'todos' || $estado === 'enviado') {
    $pa = $pdo->query("
        SELECT p.id, p.fecha, p.total,
               c.nombre AS cliente_nombre, c.empresa AS cliente_empresa,
               DATEDIFF(NOW(), p.fecha) AS dias
        FROM presupuestos p
        LEFT JOIN clientes c ON c.id = p.cliente_id
        WHERE p.estado = 'enviado' AND p.fecha <= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY p.fecha ASC
    ")->fetchAll();
    $pendientes_alerta = $pa;
}

$tabs   = ['todos','borrador','enviado','aprobado','rechazado'];
$labels = ['todos'=>'Todos','borrador'=>'Borrador','enviado'=>'Enviado','aprobado'=>'Aprobado','rechazado'=>'Rechazado'];
?>

<!-- Alerta enviados sin respuesta -->
<?php if ($pendientes_alerta): ?>
<div class="mb-4 bg-amber-50 border border-amber-200 rounded-xl p-4">
  <div class="flex items-center gap-2 mb-2">
    <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    <p class="text-sm font-semibold text-amber-800">Enviados sin respuesta hace más de 7 días</p>
  </div>
  <div class="space-y-1">
    <?php foreach ($pendientes_alerta as $pa): ?>
    <div class="flex items-center justify-between text-sm">
      <a href="<?= BASE_URL ?>/presupuestos/ver.php?id=<?= $pa['id'] ?>"
        class="text-amber-700 hover:underline font-medium">
        #<?= $pa['id'] ?> — <?= esc($pa['cliente_empresa'] ?: $pa['cliente_nombre'] ?: 'Sin cliente') ?>
      </a>
      <span class="text-amber-600 text-xs"><?= $pa['dias'] ?> días · <?= money($pa['total']) ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

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
  <?php foreach ($labels as $key => $lbl): ?>
  <a href="?estado=<?= $key ?><?= $q ? '&q=' . urlencode($q) : '' ?>"
    class="flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium transition-colors
    <?= $estado === $key ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
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
          class="text-sm font-medium text-blue-600 hover:underline">#<?= str_pad($p['id'],5,'0',STR_PAD_LEFT) ?></a>
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
        <a href="<?= BASE_URL ?>/presupuestos/pdf.php?id=<?= $p['id'] ?>" target="_blank" class="text-xs text-gray-500 hover:underline">PDF</a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
