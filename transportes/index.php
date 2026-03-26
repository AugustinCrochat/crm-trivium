<?php
require_once '../config/db.php';
$title = 'Transportes';
require_once '../includes/header.php';

$q = trim($_GET['q'] ?? ''); // búsqueda por ciudad

if ($q !== '') {
    // Buscar transportes que sirven esa ciudad
    $stmt = $pdo->prepare("
        SELECT DISTINCT t.*
        FROM transportes t
        JOIN transporte_ciudades tc ON tc.transporte_id = t.id
        WHERE t.activo = 1 AND tc.ciudad LIKE ?
        ORDER BY t.nombre
    ");
    $stmt->execute(['%' . $q . '%']);
} else {
    $stmt = $pdo->query("SELECT * FROM transportes WHERE activo = 1 ORDER BY nombre");
}
$transportes = $stmt->fetchAll();

// Para cada transporte, obtener sus ciudades
$ciudades_por_transporte = [];
if ($transportes) {
    $ids  = implode(',', array_column($transportes, 'id'));
    $rows = $pdo->query("SELECT * FROM transporte_ciudades WHERE transporte_id IN ($ids) ORDER BY ciudad")->fetchAll();
    foreach ($rows as $r) {
        $ciudades_por_transporte[$r['transporte_id']][] = $r['ciudad'];
    }
}
?>

<div class="flex items-center justify-between mb-4 gap-3">
  <form method="GET" class="flex gap-2 flex-1 max-w-sm">
    <input type="search" name="q" value="<?= esc($q) ?>" placeholder="Buscar por ciudad destino…"
      class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    <button type="submit" class="bg-gray-100 border border-gray-300 text-gray-700 text-sm px-3 py-2 rounded-lg hover:bg-gray-200">
      Buscar
    </button>
    <?php if ($q): ?>
    <a href="?" class="text-sm text-gray-500 hover:text-gray-700 px-2 py-2">✕</a>
    <?php endif; ?>
  </form>
  <a href="<?= BASE_URL ?>/transportes/nuevo.php"
    class="flex-shrink-0 bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700">
    + Nuevo
  </a>
</div>

<?php if ($q): ?>
<p class="text-sm text-gray-500 mb-3">Transportes que llegan a <strong><?= esc($q) ?></strong>: <?= count($transportes) ?></p>
<?php endif; ?>

<?php if (!$transportes): ?>
<div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
  <p class="text-gray-400 text-sm">
    <?= $q ? "Ningún transporte cubre \"" . esc($q) . "\"" : 'No hay transportes cargados' ?>
  </p>
</div>
<?php else: ?>
<div class="space-y-3">
  <?php foreach ($transportes as $t): ?>
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <h3 class="font-semibold text-gray-900"><?= esc($t['nombre']) ?></h3>
        <?php if ($t['telefono']): ?>
        <p class="text-sm text-gray-500 mt-0.5">
          <a href="tel:<?= esc($t['telefono']) ?>" class="hover:underline">☎ <?= esc($t['telefono']) ?></a>
        </p>
        <?php endif; ?>
        <?php if ($t['direccion']): ?>
        <p class="text-xs text-gray-400 mt-0.5"><?= esc($t['direccion']) ?></p>
        <?php endif; ?>
        <!-- Ciudades -->
        <?php if (!empty($ciudades_por_transporte[$t['id']])): ?>
        <div class="mt-2 flex flex-wrap gap-1">
          <?php foreach ($ciudades_por_transporte[$t['id']] as $ciudad): ?>
          <span class="inline-block bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded-full
            <?= $q && stripos($ciudad, $q) !== false ? 'bg-blue-100 text-blue-700' : '' ?>">
            <?= esc($ciudad) ?>
          </span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <a href="<?= BASE_URL ?>/transportes/editar.php?id=<?= $t['id'] ?>"
        class="flex-shrink-0 text-xs text-blue-600 hover:underline">Editar</a>
    </div>
    <?php if ($t['notas']): ?>
    <p class="text-xs text-gray-400 mt-2 border-t border-gray-100 pt-2"><?= esc($t['notas']) ?></p>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
