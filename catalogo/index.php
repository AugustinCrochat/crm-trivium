<?php
require_once '../config/db.php';
$title = 'Catálogo';
require_once '../includes/header.php';

$q        = trim($_GET['q'] ?? '');
$categoria = trim($_GET['categoria'] ?? '');

$where  = ['activo = 1'];
$params = [];

if ($q !== '') {
    $where[]  = '(nombre LIKE ? OR codigo_tango LIKE ? OR descripcion LIKE ?)';
    $like     = "%{$q}%";
    $params   = array_merge($params, [$like, $like, $like]);
}
if ($categoria !== '') {
    $where[]  = 'categoria = ?';
    $params[] = $categoria;
}

$sql = 'SELECT * FROM productos WHERE ' . implode(' AND ', $where) . ' ORDER BY nombre ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();

// Categorías disponibles
$categorias = $pdo->query("SELECT DISTINCT categoria FROM productos WHERE categoria IS NOT NULL AND categoria != '' AND activo=1 ORDER BY categoria")
    ->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="flex items-center justify-between mb-4 gap-3">
  <form method="GET" class="flex gap-2 flex-1 max-w-lg">
    <input type="search" name="q" value="<?= esc($q) ?>" placeholder="Buscar producto…"
      class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    <?php if ($categorias): ?>
    <select name="categoria"
      class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      <option value="">Todas las categorías</option>
      <?php foreach ($categorias as $cat): ?>
      <option value="<?= esc($cat) ?>" <?= $categoria === $cat ? 'selected' : '' ?>><?= esc($cat) ?></option>
      <?php endforeach; ?>
    </select>
    <?php else: ?>
    <input type="hidden" name="categoria" value="">
    <?php endif; ?>
    <button type="submit" class="bg-gray-100 border border-gray-300 text-gray-700 text-sm px-3 py-2 rounded-lg hover:bg-gray-200">
      Buscar
    </button>
  </form>
  <a href="<?= BASE_URL ?>/catalogo/nuevo.php"
    class="flex-shrink-0 bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700">
    + Nuevo
  </a>
</div>

<div id="search-results">
<?php if (!$productos): ?>
<div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
  <p class="text-gray-400 text-sm">No hay productos<?= $q ? " para \"{$q}\"" : '' ?></p>
</div>
<?php else: ?>
<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 border-b border-gray-200">
        <tr>
          <th class="text-left px-4 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Producto</th>
          <th class="text-left px-4 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide hidden sm:table-cell">Código</th>
          <th class="text-left px-4 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide hidden md:table-cell">Categoría</th>
          <th class="text-right px-4 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide">Precio</th>
          <th class="text-right px-4 py-3 font-medium text-gray-500 text-xs uppercase tracking-wide hidden sm:table-cell">Stock</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($productos as $p): ?>
        <tr class="hover:bg-gray-50">
          <td class="px-4 py-3 font-medium text-gray-900"><?= esc($p['nombre']) ?></td>
          <td class="px-4 py-3 text-gray-500 hidden sm:table-cell font-mono text-xs"><?= esc($p['codigo_tango'] ?: '—') ?></td>
          <td class="px-4 py-3 text-gray-500 hidden md:table-cell"><?= esc($p['categoria'] ?: '—') ?></td>
          <td class="px-4 py-3 text-right font-semibold text-gray-800"><?= money($p['precio']) ?></td>
          <td class="px-4 py-3 text-right hidden sm:table-cell">
            <span class="<?= (int)$p['stock'] <= 0 ? 'text-red-600' : 'text-gray-700' ?> font-medium"><?= (int)$p['stock'] ?></span>
          </td>
          <td class="px-4 py-3 text-right">
            <a href="<?= BASE_URL ?>/catalogo/editar.php?id=<?= $p['id'] ?>"
              class="text-xs text-blue-600 hover:underline">Editar</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
