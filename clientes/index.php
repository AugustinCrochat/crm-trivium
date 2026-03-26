<?php
require_once '../config/db.php';
$title = 'Clientes';
require_once '../includes/header.php';

$estado = $_GET['estado'] ?? 'todos';
$q      = trim($_GET['q'] ?? '');

$estados_validos = ['todos','prospecto','activo','en_envio','guardado'];
if (!in_array($estado, $estados_validos)) $estado = 'todos';

$where  = [];
$params = [];

if ($estado !== 'todos') {
    $where[]  = 'estado = ?';
    $params[] = $estado;
}
if ($q !== '') {
    $where[]  = '(nombre LIKE ? OR empresa LIKE ? OR ciudad LIKE ? OR telefono LIKE ?)';
    $like     = "%{$q}%";
    $params   = array_merge($params, [$like, $like, $like, $like]);
}

$sql = 'SELECT * FROM clientes';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY updated_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll();

// Conteos por estado
$conteos = $pdo->query("
    SELECT estado, COUNT(*) AS n FROM clientes GROUP BY estado
")->fetchAll(PDO::FETCH_KEY_PAIR);
$total = array_sum($conteos);

$tabs = [
    'todos'     => ['label' => 'Todos',       'n' => $total],
    'prospecto' => ['label' => 'Prospectos',  'n' => $conteos['prospecto'] ?? 0],
    'activo'    => ['label' => 'Activos',     'n' => $conteos['activo']    ?? 0],
    'en_envio'  => ['label' => 'En envío',    'n' => $conteos['en_envio']  ?? 0],
    'guardado'  => ['label' => 'Guardados',   'n' => $conteos['guardado']  ?? 0],
];
?>

<!-- Header de sección -->
<div class="flex items-center justify-between mb-4 gap-3">
  <form method="GET" class="flex-1 max-w-xs">
    <input type="hidden" name="estado" value="<?= esc($estado) ?>">
    <input type="search" name="q" value="<?= esc($q) ?>" placeholder="Buscar cliente…"
      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
  </form>
  <a href="<?= BASE_URL ?>/clientes/nuevo.php"
    class="flex-shrink-0 bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
    + Nuevo
  </a>
</div>

<!-- Tabs de estado -->
<div class="flex gap-1 mb-4 overflow-x-auto pb-1">
  <?php foreach ($tabs as $key => $tab): ?>
  <a href="?estado=<?= $key ?><?= $q ? '&q=' . urlencode($q) : '' ?>"
    class="flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium transition-colors
    <?= $estado === $key
        ? 'bg-blue-600 text-white'
        : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
    <?= $tab['label'] ?> <span class="ml-0.5 opacity-70">(<?= $tab['n'] ?>)</span>
  </a>
  <?php endforeach; ?>
</div>

<!-- Lista -->
<?php if (!$clientes): ?>
<div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
  <p class="text-gray-400 text-sm">No hay clientes<?= $q ? " que coincidan con \"{$q}\"" : '' ?></p>
</div>
<?php else: ?>
<div class="bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100">
  <?php foreach ($clientes as $c): ?>
  <a href="<?= BASE_URL ?>/clientes/ver.php?id=<?= $c['id'] ?>"
    class="flex items-center gap-4 px-4 py-3.5 hover:bg-gray-50 transition-colors">
    <!-- Avatar -->
    <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-sm font-bold flex-shrink-0">
      <?= strtoupper(substr($c['nombre'], 0, 1)) ?>
    </div>
    <!-- Info -->
    <div class="min-w-0 flex-1">
      <p class="text-sm font-medium text-gray-900 truncate"><?= esc($c['nombre']) ?></p>
      <p class="text-xs text-gray-400 truncate">
        <?= esc($c['empresa'] ?: '') ?>
        <?= $c['empresa'] && $c['ciudad'] ? ' · ' : '' ?>
        <?= esc($c['ciudad'] ?: '') ?>
      </p>
    </div>
    <!-- Estado -->
    <div class="flex-shrink-0"><?= badge($c['estado']) ?></div>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
