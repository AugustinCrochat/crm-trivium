<?php
require_once '../config/db.php';
$title = 'Importaciones';
require_once '../includes/header.php';

$estado = $_GET['estado'] ?? 'todos';
$q      = trim($_GET['q'] ?? '');

$where  = [];
$params = [];

if ($estado !== 'todos') {
    $where[]  = 'i.estado = ?';
    $params[] = $estado;
}
if ($q !== '') {
    $like     = "%{$q}%";
    $where[]  = '(i.proveedor LIKE ? OR i.numero_proforma LIKE ? OR i.numero_bl LIKE ? OR i.familia_productos LIKE ?)';
    $params   = array_merge($params, [$like, $like, $like, $like]);
}

$sql = "SELECT i.*, f.nombre AS forwarder_nombre
        FROM importaciones i
        LEFT JOIN forwarders f ON f.id = i.forwarder_id";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY ISNULL(i.eta) ASC, i.eta ASC, i.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$importaciones = $stmt->fetchAll();

$labels_estado = ['todos'=>'Todos','pendiente'=>'Pendiente','embarcado'=>'Embarcado','arribado'=>'Arribado','cerrado'=>'Cerrado'];

function iUrl(array $override): string {
    $p = array_filter(array_merge([
        'estado' => $_GET['estado'] ?? 'todos',
        'q'      => $_GET['q']      ?? '',
    ], $override), fn($v) => $v !== '' && $v !== 'todos');
    return '?' . http_build_query($p);
}
?>

<div class="flex items-center justify-between mb-3 gap-3">
  <form method="GET" class="flex gap-2 flex-1 max-w-xs">
    <input type="hidden" name="estado" value="<?= esc($estado) ?>">
    <input type="search" name="q" value="<?= esc($q) ?>" placeholder="Proveedor, proforma, B/L…"
      class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
  </form>
  <a href="<?= BASE_URL ?>/importaciones/nuevo.php"
    class="flex-shrink-0 bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700">
    + Nueva
  </a>
</div>

<!-- Tabs de estado -->
<div class="flex gap-1 mb-4 overflow-x-auto pb-1">
  <?php foreach ($labels_estado as $key => $lbl): ?>
  <a href="<?= iUrl(['estado' => $key]) ?>"
    class="flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium transition-colors
    <?= $estado === $key ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
    <?= $lbl ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if (!$importaciones): ?>
<div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
  <p class="text-gray-400 text-sm">No hay importaciones</p>
</div>
<?php else: ?>
<div class="space-y-2">
  <?php foreach ($importaciones as $imp): ?>
  <a href="<?= BASE_URL ?>/importaciones/ver.php?id=<?= $imp['id'] ?>"
    class="flex items-start gap-3 bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3 hover:border-blue-300 transition-colors">
    <div class="min-w-0 flex-1">
      <div class="flex items-center gap-2 flex-wrap mb-0.5">
        <?= badge($imp['estado']) ?>
        <span class="text-sm font-semibold text-gray-800"><?= esc($imp['proveedor'] ?: '—') ?></span>
        <?php if ($imp['familia_productos']): ?>
        <span class="text-xs text-gray-400"><?= esc($imp['familia_productos']) ?></span>
        <?php endif; ?>
      </div>
      <p class="text-xs text-gray-500">
        <?= $imp['numero_proforma'] ? 'Proforma: ' . esc($imp['numero_proforma']) : '' ?>
        <?= $imp['numero_bl'] ? ($imp['numero_proforma'] ? ' · ' : '') . 'B/L: ' . esc($imp['numero_bl']) : '' ?>
      </p>
      <div class="flex flex-wrap gap-3 mt-1 text-xs text-gray-400">
        <?php if ($imp['etd']): ?>
        <span>ETD <?= fecha($imp['etd']) ?></span>
        <?php endif; ?>
        <?php if ($imp['eta']): ?>
        <span>ETA <?= fecha($imp['eta']) ?></span>
        <?php endif; ?>
        <?php if ($imp['monto_fob']): ?>
        <span>FOB USD <?= number_format((float)$imp['monto_fob'], 2, ',', '.') ?></span>
        <?php endif; ?>
        <?php if ($imp['forwarder_nombre']): ?>
        <span><?= esc($imp['forwarder_nombre']) ?></span>
        <?php endif; ?>
      </div>
    </div>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
