<?php
require_once '../config/db.php';

// Cargar notas/todo para la sección superior
$viajes_notas = [];
try {
    $viajes_notas = $pdo->query("SELECT * FROM viajes_notas ORDER BY completado ASC, created_at DESC")->fetchAll();
} catch (PDOException $ex) {}
$pendientes_notas = count(array_filter($viajes_notas, fn($n) => !$n['completado']));

$title = 'Viajes';
require_once '../includes/header.php';

// POST: asignar/desasignar envío a viaje
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_viaje'])) {
    verify_csrf();
    $envio_id = (int)$_POST['envio_id'];
    $viaje_id = $_POST['viaje_id'] !== '' ? (int)$_POST['viaje_id'] : null;
    $pdo->prepare("UPDATE envios SET viaje_id=? WHERE id=?")->execute([$viaje_id, $envio_id]);
    redirect('/viajes/');
}

$mostrar = $_GET['ver'] ?? 'activos';

// ── Viajes ───────────────────────────────────────────────────────
$where_viajes = $mostrar === 'activos'
    ? "WHERE v.estado != 'completado'"
    : '';

$viajes = $pdo->query("
    SELECT v.*, COUNT(e.id) AS cant_envios, tr.nombre AS transporte_nombre
    FROM viajes v
    LEFT JOIN envios e    ON e.viaje_id = v.id
    LEFT JOIN transportes tr ON tr.id = v.transporte_id
    {$where_viajes}
    GROUP BY v.id
    ORDER BY v.fecha ASC, v.created_at DESC
")->fetchAll();

// ── Envíos ───────────────────────────────────────────────────────
$estado_envio = $_GET['estado'] ?? 'pendientes';
$where_envios = $estado_envio === 'pendientes'
    ? "WHERE e.estado != 'entregado'"
    : '';

$envios = $pdo->query("
    SELECT e.*, c.nombre AS cliente_nombre, c.ciudad,
           tr.nombre AS transporte_nombre,
           vj.fecha AS viaje_fecha, vj.descripcion AS viaje_desc
    FROM envios e
    LEFT JOIN clientes c      ON c.id  = e.cliente_id
    LEFT JOIN transportes tr  ON tr.id = e.transporte_id
    LEFT JOIN viajes vj       ON vj.id = e.viaje_id
    {$where_envios}
    ORDER BY e.created_at DESC
")->fetchAll();

// Viajes disponibles para el selector de asignación
$viajes_opts = $pdo->query("
    SELECT id, fecha, descripcion FROM viajes
    WHERE estado != 'completado' ORDER BY fecha ASC
")->fetchAll();
?>

<div class="space-y-6">

  <!-- ══ Sección: Cosas a tener en cuenta (Viajes) ══ -->
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-2" id="todo-viajes-section">
    <button onclick="toggleViajesTodo()" type="button"
      class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-gray-50 transition-colors rounded-xl">
      <div class="flex items-center gap-2">
        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>
        <span class="font-bold text-gray-700">Puntos importantes / Recordatorios</span>
        <span id="viajes-todo-badge" class="<?= $pendientes_notas ? '' : 'hidden' ?> bg-blue-100 text-blue-700 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">
          <?= $pendientes_notas ?> Pendientes
        </span>
      </div>
      <svg id="todo-viajes-arrow" class="w-4 h-4 text-gray-400 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>
    
    <div id="todo-viajes-content" class="hidden px-4 pb-4">
      <div class="flex gap-2 mb-4 pt-2 border-t border-gray-50">
        <input type="text" id="new-viajes-todo-text" placeholder="Ej: Confirmar llegada de camión a depósito..."
          class="flex-1 text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
        <button type="button" onclick="addViajesTodo()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
          Agregar
        </button>
      </div>
      <div id="viajes-todo-list" class="space-y-2">
        <?php foreach ($viajes_notas as $n): ?>
        <div id="viaje-nota-<?= $n['id'] ?>" class="flex items-center justify-between gap-3 p-2 rounded-lg hover:bg-gray-50 group">
          <div class="flex items-center gap-3 flex-1 min-w-0">
            <input type="checkbox" <?= $n['completado'] ? 'checked' : '' ?>
              onchange="toggleViajeNota(<?= $n['id'] ?>)"
              class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
            <span class="text-sm <?= $n['completado'] ? 'line-through text-gray-400' : 'text-gray-700' ?> truncate">
              <?= esc($n['texto']) ?>
            </span>
          </div>
          <button type="button" onclick="deleteViajeNota(<?= $n['id'] ?>)" class="text-gray-300 hover:text-red-500 p-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
          </button>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- ── VIAJES ─────────────────────────────────────────────────── -->
  <div>
    <div class="flex items-center justify-between mb-3 gap-3">
      <div class="flex items-center gap-2">
        <!-- Truck icon -->
        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1"/>
        </svg>
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Viajes — Camión Plancha</h2>
      </div>
      <div class="flex items-center gap-2">
        <div class="flex gap-1">
          <a href="?ver=activos&estado=<?= esc($estado_envio) ?>"
            class="px-3 py-1.5 rounded-full text-xs font-medium
            <?= $mostrar === 'activos' ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
            Activos
          </a>
          <a href="?ver=todos&estado=<?= esc($estado_envio) ?>"
            class="px-3 py-1.5 rounded-full text-xs font-medium
            <?= $mostrar === 'todos' ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
            Todos
          </a>
        </div>
        <a href="<?= BASE_URL ?>/viajes/nuevo.php"
          class="bg-blue-600 text-white text-sm font-medium px-3 py-1.5 rounded-lg hover:bg-blue-700">
          + Viaje
        </a>
      </div>
    </div>

    <?php if (!$viajes): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
      <p class="text-gray-400 text-sm">No hay viajes planificados</p>
    </div>
    <?php else: ?>
    <div class="space-y-2">
      <?php foreach ($viajes as $vj): ?>
      <a href="<?= BASE_URL ?>/viajes/ver.php?id=<?= $vj['id'] ?>"
        class="flex items-center gap-4 bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3 hover:border-blue-300 transition-colors">
        <?php if ($vj['foto_url']): ?>
        <img src="<?= BASE_URL . '/' . esc($vj['foto_url']) ?>" alt=""
          class="w-12 h-12 object-cover rounded-lg flex-shrink-0">
        <?php else: ?>
        <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
          <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1"/>
          </svg>
        </div>
        <?php endif; ?>
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-2 mb-0.5">
            <?= badge($vj['estado']) ?>
            <span class="text-sm font-semibold text-gray-800"><?= fecha($vj['fecha']) ?></span>
          </div>
          <p class="text-xs text-gray-500 truncate">
            <?= $vj['descripcion'] ? esc($vj['descripcion']) . ' · ' : '' ?>
            <?= (int)$vj['cant_envios'] ?> envío(s)
            <?= $vj['transporte_nombre'] ? ' · ' . esc($vj['transporte_nombre']) : '' ?>
          </p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── ENVÍOS ─────────────────────────────────────────────────── -->
  <div>
    <div class="flex items-center justify-between mb-3 gap-3">
      <div class="flex items-center gap-2">
        <!-- Box icon -->
        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Envíos</h2>
      </div>
      <div class="flex items-center gap-2">
        <div class="flex gap-1">
          <a href="?ver=<?= esc($mostrar) ?>&estado=pendientes"
            class="px-3 py-1.5 rounded-full text-xs font-medium
            <?= $estado_envio === 'pendientes' ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
            Pendientes
          </a>
          <a href="?ver=<?= esc($mostrar) ?>&estado=todos"
            class="px-3 py-1.5 rounded-full text-xs font-medium
            <?= $estado_envio === 'todos' ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
            Todos
          </a>
        </div>
        <a href="<?= BASE_URL ?>/envios/nuevo.php"
          class="bg-blue-600 text-white text-sm font-medium px-3 py-1.5 rounded-lg hover:bg-blue-700">
          + Envío
        </a>
      </div>
    </div>

    <?php if (!$envios): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
      <p class="text-gray-400 text-sm">No hay envíos</p>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100">
      <?php foreach ($envios as $e): ?>
      <div class="flex items-center gap-3 px-4 py-3">
        <a href="<?= BASE_URL ?>/envios/ver.php?id=<?= $e['id'] ?>" class="min-w-0 flex-1">
          <p class="text-sm font-medium text-gray-900 truncate"><?= esc($e['cliente_nombre'] ?: 'Sin cliente') ?></p>
          <p class="text-xs text-gray-400 truncate">
            <?= esc($e['ciudad'] ?: '') ?>
            <?= $e['ciudad'] ? ' · ' : '' ?>
            <?= tipo_envio($e['tipo']) ?>
            <?= $e['transporte_nombre'] ? ' · ' . esc($e['transporte_nombre']) : '' ?>
          </p>
        </a>

        <!-- Asignar a viaje (solo camión plancha) -->
        <?php if (str_starts_with($e['tipo'], 'camion_plancha')): ?>
        <form method="POST" class="flex-shrink-0">
          <?= csrf_field() ?>
          <input type="hidden" name="envio_id" value="<?= $e['id'] ?>">
          <select name="viaje_id" onchange="this.form.submit()"
            class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white text-gray-600 max-w-[140px]">
            <option value="">Sin viaje</option>
            <?php foreach ($viajes_opts as $vo): ?>
            <option value="<?= $vo['id'] ?>"
              <?= $e['viaje_id'] == $vo['id'] ? 'selected' : '' ?>>
              <?= fecha($vo['fecha']) ?><?= $vo['descripcion'] ? ' — ' . esc(substr($vo['descripcion'], 0, 15)) : '' ?>
            </option>
            <?php endforeach; ?>
          </select>
          <input type="hidden" name="asignar_viaje" value="1">
        </form>
        <?php endif; ?>

        <div class="flex-shrink-0"><?= badge($e['estado']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div>

<script>
function toggleViajesTodo() {
    const content = document.getElementById('todo-viajes-content');
    const arrow   = document.getElementById('todo-viajes-arrow');
    content.classList.toggle('hidden');
    arrow.classList.toggle('rotate-180');
}

async function fetchViajesNotas() {
    try {
        const res = await fetch('<?= BASE_URL ?>/api/viajes_notas.php?action=list');
        if (!res.ok) throw new Error('Network response was not ok');
        const json = await res.json();
        if (json.ok) renderViajesNotas(json.data);
    } catch(e) { console.error('Error listing notes:', e); }
}

function renderViajesNotas(notas) {
    const list = document.getElementById('viajes-todo-list');
    const badge = document.getElementById('viajes-todo-badge');
    if (!list || !badge) return;
    
    const pendientes = notas.filter(n => !parseInt(n.completado)).length;
    if (pendientes > 0) {
        badge.textContent = `${pendientes} Pendientes`;
        badge.classList.remove('hidden');
    } else {
        badge.classList.add('hidden');
    }

    list.innerHTML = notas.map(n => `
      <div id="viaje-nota-${n.id}" class="flex items-center justify-between gap-3 p-2 rounded-lg hover:bg-gray-50 group">
        <div class="flex items-center gap-3 flex-1 min-w-0">
          <input type="checkbox" ${parseInt(n.completado) ? 'checked' : ''}
            onchange="toggleViajeNota(${n.id})"
            class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
          <span class="text-sm ${parseInt(n.completado) ? 'line-through text-gray-400' : 'text-gray-700'} truncate">
            ${n.texto}
          </span>
        </div>
        <button type="button" onclick="deleteViajeNota(${n.id})" class="text-gray-300 hover:text-red-500 p-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
      </div>
    `).join('');
}

async function addViajesTodo() {
    const inp = document.getElementById('new-viajes-todo-text');
    if (!inp) return;
    const texto = inp.value.trim();
    if (!texto) return;
    
    try {
        const res = await fetch('<?= BASE_URL ?>/api/viajes_notas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', texto })
        });
        const json = await res.json();
        if (json.ok) {
            inp.value = '';
            fetchViajesNotas();
        } else {
            alert('Error: ' + (json.error || 'No se pudo agregar la nota'));
        }
    } catch(e) {
        console.error('Fetch error:', e);
        alert('Error de conexión con el servidor');
    }
}

async function toggleViajeNota(id) {
    try {
        await fetch('<?= BASE_URL ?>/api/viajes_notas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'toggle', id })
        });
        fetchViajesNotas();
    } catch(e) { console.error('Error toggling note:', e); }
}

async function deleteViajeNota(id) {
    if (!confirm('¿Eliminar este recordatorio?')) return;
    try {
        await fetch('<?= BASE_URL ?>/api/viajes_notas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        });
        fetchViajesNotas();
    } catch(e) { console.error('Error deleting note:', e); }
}
</script>

<?php require_once '../includes/footer.php'; ?>
