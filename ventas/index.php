<?php
require_once '../config/db.php';
$title = 'Ventas';
require_once '../includes/header.php';

$q          = trim($_GET['q']          ?? '');
$fecha_desde = $_GET['fecha_desde']    ?? '';
$fecha_hasta = $_GET['fecha_hasta']    ?? '';

$where  = [];
$params = [];

if ($q !== '') {
    $where[]  = '(c.nombre LIKE ? OR c.empresa LIKE ? OR v.cliente_nombre LIKE ? OR v.producto LIKE ?)';
    $like     = "%{$q}%";
    $params   = array_merge($params, [$like, $like, $like, $like]);
}
if ($fecha_desde !== '') {
    $where[]  = 'v.fecha >= ?';
    $params[] = $fecha_desde;
}
if ($fecha_hasta !== '') {
    $where[]  = 'v.fecha <= ?';
    $params[] = $fecha_hasta;
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

// Totales filtrados
$sqlT = "SELECT COUNT(*) AS cant, COALESCE(SUM(v.total),0) AS total
         FROM ventas v LEFT JOIN clientes c ON c.id = v.cliente_id";
if ($where) $sqlT .= ' WHERE ' . implode(' AND ', $where);
$totStmt = $pdo->prepare($sqlT);
$totStmt->execute($params);
$totRow = $totStmt->fetch();

function clienteDisplay(array $v): string {
    return esc($v['cliente_empresa'] ?: $v['cliente_nombre_fk'] ?: $v['cliente_nombre'] ?? '' ?: 'Sin cliente');
}
?>

<!-- Resumen ingresos -->
<div class="grid grid-cols-2 gap-3 mb-4">
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3">
    <p class="text-xs text-gray-500 mb-0.5">Ingresos totales (ARS)</p>
    <p class="text-lg font-bold text-gray-900"><?= money($totRow['total']) ?></p>
  </div>
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3">
    <p class="text-xs text-gray-500 mb-0.5">Ventas</p>
    <p class="text-lg font-bold text-gray-900"><?= (int)$totRow['cant'] ?></p>
  </div>
</div>

<!-- Filtros -->
<form method="GET" id="form-filtros" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-4 space-y-3">

  <!-- Búsqueda -->
  <input type="search" name="q" id="inp-q" value="<?= esc($q) ?>" placeholder="Buscar cliente o producto…"
    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">

  <!-- Accesos rápidos -->
  <div class="flex flex-wrap gap-1.5">
    <?php
    $hoy   = date('Y-m-d');
    $anio  = date('Y');
    $mes   = date('m');
    $iniMes     = "{$anio}-{$mes}-01";
    $finMes     = date('Y-m-t');
    $iniMesAnt  = date('Y-m-01', strtotime('first day of last month'));
    $finMesAnt  = date('Y-m-t',  strtotime('last day of last month'));
    $iniAnio    = "{$anio}-01-01";
    $iniAnioAnt = ($anio-1) . "-01-01";
    $finAnioAnt = ($anio-1) . "-12-31";

    $atajos = [
        'hoy'       => ['Hoy',           $hoy,       $hoy],
        'mes'       => ['Este mes',       $iniMes,    $finMes],
        'mes_ant'   => ['Mes anterior',   $iniMesAnt, $finMesAnt],
        'anio'      => ['Este año',       $iniAnio,   $hoy],
        'anio_ant'  => [($anio-1),        $iniAnioAnt,$finAnioAnt],
        'todo'      => ['Todo',           '',         ''],
    ];
    foreach ($atajos as $key => [$lbl, $desde, $hasta]):
        $activo = ($fecha_desde === $desde && $fecha_hasta === $hasta);
    ?>
    <button type="button"
      onclick="setAtajo('<?= $desde ?>','<?= $hasta ?>')"
      class="px-2.5 py-1 rounded-full text-xs font-medium border transition-colors
        <?= $activo ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
      <?= $lbl ?>
    </button>
    <?php endforeach; ?>
  </div>

  <!-- Rango manual -->
  <div class="flex items-center gap-2">
    <input type="date" name="fecha_desde" id="inp-desde" value="<?= esc($fecha_desde) ?>"
      class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    <span class="text-gray-400 text-sm flex-shrink-0">→</span>
    <input type="date" name="fecha_hasta" id="inp-hasta" value="<?= esc($fecha_hasta) ?>"
      class="flex-1 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    <button type="submit"
      class="flex-shrink-0 bg-blue-600 text-white text-sm font-medium px-3 py-1.5 rounded-lg hover:bg-blue-700">
      Filtrar
    </button>
    <a href="<?= BASE_URL ?>/ventas/"
      class="flex-shrink-0 text-xs text-gray-400 hover:text-gray-600 px-1">✕</a>
  </div>

</form>

<div class="flex justify-end mb-3">
  <a href="<?= BASE_URL ?>/ventas/nueva.php"
    class="bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700">
    + Nueva
  </a>
</div>

<div id="search-results">
<!-- Resumen ingresos -->
<div class="grid grid-cols-2 gap-3 mb-4">
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3">
    <p class="text-xs text-gray-500 mb-0.5">Ingresos totales (ARS)</p>
    <p class="text-lg font-bold text-gray-900"><?= money($totRow['total']) ?></p>
  </div>
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3">
    <p class="text-xs text-gray-500 mb-0.5">Ventas</p>
    <p class="text-lg font-bold text-gray-900"><?= (int)$totRow['cant'] ?></p>
  </div>
</div>

<?php if (!$ventas): ?>
<div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
  <p class="text-gray-400 text-sm">No hay ventas para este período</p>
</div>
<?php else: ?>
<div class="bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100">
  <?php foreach ($ventas as $v): ?>
  <div class="px-4 py-3 hover:bg-gray-50">
    <div class="flex items-start gap-3">
      <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2 flex-wrap">
          <a href="<?= BASE_URL ?>/ventas/ver.php?id=<?= $v['id'] ?>"
            class="text-sm font-semibold text-blue-600 hover:underline">#<?= $v['id'] ?></a>
          <?php if ($v['sincronizado_tango']): ?>
          <span class="text-xs text-green-600 font-medium">✓ Tango</span>
          <?php endif; ?>
        </div>
        <p class="text-sm text-gray-800 font-medium truncate"><?= clienteDisplay($v) ?></p>
        <p class="text-xs text-gray-500 truncate">
          <?= esc($v['producto'] ?? '') ?>
          <?php if (!empty($v['cantidad']) && (float)$v['cantidad'] != 1): ?>
            <span class="text-gray-400">× <?= number_format((float)$v['cantidad'], 0, ',', '.') ?></span>
          <?php endif; ?>
        </p>
        <p class="text-xs text-gray-400 mt-0.5">
          <?= fecha($v['fecha']) ?>
          <?= !empty($v['vendedor'])    ? ' · ' . esc($v['vendedor'])    : '' ?>
          <?= !empty($v['metodo_pago']) ? ' · ' . esc($v['metodo_pago']) : '' ?>
        </p>
        <?php if (!empty($v['notas'])): ?>
        <p class="text-xs text-gray-400 mt-0.5 truncate italic"><?= esc($v['notas']) ?></p>
        <?php endif; ?>
      </div>
      <div class="flex-shrink-0 text-right">
        <p class="text-sm font-bold text-gray-800"><?= money($v['total']) ?></p>
      </div>
    </div>

    <!-- Toggle pills -->
    <div class="flex gap-2 mt-2">
      <?php
      $pills = ['cobrado' => 'Cobrado', 'dado_de_baja' => 'Dado de Baja'];
      foreach ($pills as $campo => $label):
          $on  = !empty($v[$campo]);
          $cls = $on ? 'bg-green-100 border-green-200 text-green-700'
                     : 'bg-red-50 border-red-200 text-red-500';
      ?>
      <button type="button"
        onclick="togglePill(<?= $v['id'] ?>, '<?= $campo ?>', this)"
        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium border transition-colors <?= $cls ?>">
        <span><?= $label ?>:</span>
        <span class="pill-val font-semibold"><?= $on ? 'Sí' : 'No' ?></span>
      </button>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<script>
const BASE_URL   = '<?= BASE_URL ?>';
const CSRF_TOKEN = '<?= esc($_SESSION['csrf_token']) ?>';
const LS_KEY     = 'ventas_filtros';

// ── Persistencia de filtros en localStorage ──────────────────────
function guardarFiltros() {
    const f = {
        q:           document.getElementById('inp-q').value,
        fecha_desde: document.getElementById('inp-desde').value,
        fecha_hasta: document.getElementById('inp-hasta').value,
    };
    localStorage.setItem(LS_KEY, JSON.stringify(f));
}

// Al cargar: si no hay parámetros en la URL, restaurar desde localStorage
(function restaurarFiltros() {
    const params = new URLSearchParams(window.location.search);
    const tieneParams = params.has('q') || params.has('fecha_desde') || params.has('fecha_hasta');
    if (tieneParams) {
        // Hay params activos → guardarlos
        guardarFiltros();
        return;
    }
    const saved = localStorage.getItem(LS_KEY);
    if (!saved) return;
    try {
        const f = JSON.parse(saved);
        const tieneDatos = f.q || f.fecha_desde || f.fecha_hasta;
        if (!tieneDatos) return;
        const url = new URL(window.location.href);
        if (f.q)           url.searchParams.set('q',           f.q);
        if (f.fecha_desde) url.searchParams.set('fecha_desde', f.fecha_desde);
        if (f.fecha_hasta) url.searchParams.set('fecha_hasta', f.fecha_hasta);
        window.location.replace(url.toString());
    } catch(e) {}
})();

// Guardar al enviar el formulario
document.getElementById('form-filtros').addEventListener('submit', guardarFiltros);

// Borrar filtros al hacer click en ✕
document.querySelector('a[href$="/ventas/"]')?.addEventListener('click', () => {
    localStorage.removeItem(LS_KEY);
});

// ── Atajos de fecha ───────────────────────────────────────────────
function setAtajo(desde, hasta) {
    document.getElementById('inp-desde').value = desde;
    document.getElementById('inp-hasta').value = hasta;
    guardarFiltros();
    document.getElementById('form-filtros').submit();
}

// ── Toggle pills ──────────────────────────────────────────────────
async function togglePill(id, campo, btn) {
    btn.disabled = true;
    const fd = new FormData();
    fd.append('csrf_token', CSRF_TOKEN);
    fd.append('id', id);
    fd.append('campo', campo);

    try {
        const res  = await fetch(BASE_URL + '/ventas/toggle.php', {method:'POST', body:fd});
        const json = await res.json();
        if (!json.ok) return;

        const on = !!json.valor;
        btn.querySelector('.pill-val').textContent = on ? 'Sí' : 'No';
        btn.classList.remove(
            'bg-green-100','border-green-200','text-green-700',
            'bg-red-50','border-red-200','text-red-500'
        );
        btn.classList.add(...(on
            ? ['bg-green-100','border-green-200','text-green-700']
            : ['bg-red-50','border-red-200','text-red-500']
        ));
    } catch(e) {}

    btn.disabled = false;
}
</script>

<?php require_once '../includes/footer.php'; ?>
