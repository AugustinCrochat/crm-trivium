<?php
require_once '../config/db.php';
$title = 'Ventas';
require_once '../includes/header.php';

$tipo_facturacion = $_GET['tipo_facturacion'] ?? 'todos';
$q                = trim($_GET['q'] ?? '');

$where  = [];
$params = [];

if ($tipo_facturacion !== 'todos') {
    $where[]  = 'v.tipo_facturacion = ?';
    $params[] = $tipo_facturacion;
}
if ($q !== '') {
    $where[]  = '(c.nombre LIKE ? OR c.empresa LIKE ? OR v.cliente_texto LIKE ?)';
    $like     = "%{$q}%";
    $params   = array_merge($params, [$like, $like, $like]);
}

$sql = "SELECT v.*,
               c.nombre  AS cliente_nombre_fk,
               c.empresa AS cliente_empresa
        FROM ventas v
        LEFT JOIN clientes c ON c.id = v.cliente_id";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY v.fecha DESC, v.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ventas = $stmt->fetchAll();

// Totales (sobre el mismo filtro)
$sqlT = "SELECT COUNT(*) AS cant, COALESCE(SUM(v.total),0) AS total
         FROM ventas v LEFT JOIN clientes c ON c.id = v.cliente_id";
if ($where) $sqlT .= ' WHERE ' . implode(' AND ', $where);
$totRow = $pdo->prepare($sqlT);
$totRow->execute($params);
$totRow = $totRow->fetch();

function buildUrl(array $override): string {
    $p = array_filter(array_merge([
        'tipo_facturacion' => $_GET['tipo_facturacion'] ?? 'todos',
        'q'                => $_GET['q']                ?? '',
    ], $override), fn($v) => $v !== '' && $v !== 'todos');
    return '?' . http_build_query($p);
}

function clienteDisplay(array $v): string {
    return esc($v['cliente_empresa'] ?: $v['cliente_nombre_fk'] ?: $v['cliente_texto'] ?? '' ?: 'Sin cliente');
}
?>

<!-- Resumen ingresos -->
<div class="grid grid-cols-2 gap-3 mb-5">
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3">
    <p class="text-xs text-gray-500 mb-0.5">Ingresos totales (ARS)</p>
    <p class="text-lg font-bold text-gray-900"><?= money($totRow['total']) ?></p>
  </div>
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3">
    <p class="text-xs text-gray-500 mb-0.5">Ventas</p>
    <p class="text-lg font-bold text-gray-900"><?= (int)$totRow['cant'] ?></p>
  </div>
</div>

<div class="flex items-center justify-between mb-3 gap-3">
  <form method="GET" class="flex gap-2 flex-1 max-w-xs">
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
<div class="flex gap-1 mb-4">
  <?php foreach (['todos'=>'Todas','manual'=>'Manual','facturada'=>'Facturada'] as $key => $lbl): ?>
  <a href="<?= buildUrl(['tipo_facturacion' => $key]) ?>"
    class="px-3 py-1.5 rounded-full text-xs font-medium transition-colors
    <?= $tipo_facturacion === $key ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
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
  <div class="px-4 py-3 hover:bg-gray-50">
    <div class="flex items-start gap-3">
      <!-- Info -->
      <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2 flex-wrap mb-0.5">
          <a href="<?= BASE_URL ?>/ventas/ver.php?id=<?= $v['id'] ?>"
            class="text-sm font-semibold text-blue-600 hover:underline">#<?= $v['id'] ?></a>
          <?php if (($v['tipo_facturacion'] ?? 'manual') === 'facturada'): ?>
          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">Facturada</span>
          <?php else: ?>
          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Manual</span>
          <?php endif; ?>
          <?php if ($v['sincronizado_tango']): ?>
          <span class="text-xs text-green-600 font-medium">✓ Tango</span>
          <?php endif; ?>
        </div>
        <p class="text-sm text-gray-800 font-medium truncate"><?= clienteDisplay($v) ?></p>
        <p class="text-xs text-gray-400 mt-0.5">
          <?= fecha($v['fecha']) ?>
          <?= !empty($v['vendedor'])    ? ' · ' . esc($v['vendedor'])    : '' ?>
          <?= !empty($v['metodo_pago']) ? ' · ' . esc($v['metodo_pago']) : '' ?>
        </p>
      </div>
      <!-- Total -->
      <div class="flex-shrink-0 text-right">
        <p class="text-sm font-bold text-gray-800"><?= money($v['total']) ?></p>
      </div>
    </div>

    <!-- Toggle pills -->
    <div class="flex gap-2 mt-2">
      <?php
      $pills = [
          'cobrado'      => 'Cobrado',
          'entregado'    => 'Entregado',
          'dado_de_baja' => 'Dado de Baja',
      ];
      foreach ($pills as $campo => $label):
          $on = !empty($v[$campo]);
      ?>
      <button
        onclick="togglePill(<?= $v['id'] ?>, '<?= $campo ?>', this)"
        data-on="<?= $on ? '1' : '0' ?>"
        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium border transition-colors
          <?= $on
            ? 'bg-green-100 border-green-200 text-green-700'
            : 'bg-white border-gray-200 text-gray-400' ?>">
        <span class="pill-label"><?= $label ?>:</span>
        <span class="pill-val font-semibold"><?= $on ? 'Sí' : 'No' ?></span>
      </button>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
const BASE_URL    = '<?= BASE_URL ?>';
const CSRF_TOKEN  = '<?= esc($_SESSION['csrf_token']) ?>';

async function togglePill(id, campo, btn) {
    btn.disabled = true;
    btn.style.opacity = '0.5';

    const fd = new FormData();
    fd.append('csrf_token', CSRF_TOKEN);
    fd.append('id', id);
    fd.append('campo', campo);

    try {
        const res  = await fetch(BASE_URL + '/ventas/toggle.php', {method:'POST', body:fd});
        const json = await res.json();
        if (json.ok) {
            btn.dataset.on = json.valor ? '1' : '0';
            btn.querySelector('.pill-val').textContent = json.valor ? 'Sí' : 'No';
            btn.className = btn.className
                .replace(/bg-\S+|border-\S+|text-\S+/g, '')
                .trim();
            if (json.valor) {
                btn.classList.add('bg-green-100','border-green-200','text-green-700');
            } else {
                btn.classList.add('bg-white','border-gray-200','text-gray-400');
            }
        }
    } catch(e) {}

    btn.disabled = false;
    btn.style.opacity = '';
}
</script>

<?php require_once '../includes/footer.php'; ?>
