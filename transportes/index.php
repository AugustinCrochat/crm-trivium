<?php
require_once '../config/db.php';

// Cargar notas/todo para la sección superior
$viajes_notas = [];
try {
    $viajes_notas = $pdo->query("SELECT * FROM viajes_notas ORDER BY completado ASC, created_at DESC")->fetchAll();
} catch (PDOException $ex) {}
$pendientes_notas = count(array_filter($viajes_notas, fn($n) => !$n['completado']));

$title = 'Transportes';
require_once '../includes/header.php';

$q = trim($_GET['q'] ?? ''); // búsqueda por ciudad

if ($q !== '') {
    // Buscar transportes que sirven esa ciudad directamente en la tabla
    $stmt = $pdo->prepare("
        SELECT *
        FROM transportes
        WHERE activo = 1 AND ciudad LIKE ?
        ORDER BY nombre
    ");
    $stmt->execute(['%' . $q . '%']);
} else {
    $stmt = $pdo->query("SELECT * FROM transportes WHERE activo = 1 ORDER BY nombre");
}
$transportes = $stmt->fetchAll();

// Agrupar filas por nombre de transporte, sumando sus ciudades
$transportes_agrupados = [];
foreach ($transportes as $r) {
    if (!isset($transportes_agrupados[$r['nombre']])) {
        $transportes_agrupados[$r['nombre']] = [
            'id' => $r['id'],
            'nombre' => $r['nombre'],
            'direccion' => $r['direccion'],
            'telefono' => $r['contacto'] ?? ($r['telefono'] ?? ''),
            'notas' => $r['notas'],
            'ciudades' => []
        ];
    }
    if (!empty($r['ciudad'])) {
        $transportes_agrupados[$r['nombre']]['ciudades'][] = trim($r['ciudad']);
    }
}
$transportes_agrupados = array_values($transportes_agrupados);
?>

<!-- ══ Sección: Cosas a tener en cuenta (Viajes) ══ -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-5" id="todo-viajes-section">
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
      <input type="text" id="new-viajes-todo-text" placeholder="Ej: Llamar a Expreso Jet por tarifas nuevas..."
        class="flex-1 text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
      <button onclick="addViajesTodo()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
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
        <button onclick="deleteViajeNota(<?= $n['id'] ?>)" class="text-gray-300 hover:text-red-500 p-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="flex items-center justify-between mb-4 gap-3">
  <form method="GET" class="flex gap-2 flex-1 max-w-sm">
    <input type="search" name="q" value="<?= esc($q) ?>" placeholder="Buscar por ciudad destino…"
      class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    <?php if ($q): ?>
    <a href="?" class="text-sm text-gray-500 hover:text-gray-700 px-2 py-2">✕</a>
    <?php endif; ?>
  </form>
  <a href="<?= BASE_URL ?>/transportes/nuevo.php"
    class="flex-shrink-0 bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700">
    + Nuevo
  </a>
</div>

<div id="search-results">
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
  <?php foreach ($transportes_agrupados as $t): ?>
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <h3 class="font-semibold text-gray-900"><?= esc($t['nombre']) ?></h3>
        <?php if ($t['telefono']): ?>
        <p class="text-sm text-gray-500 mt-0.5">
          <?php if (str_starts_with($t['telefono'], 'http')): ?>
          <a href="<?= esc($t['telefono']) ?>" target="_blank" class="text-blue-600 hover:underline">🔗 Contacto / Cotizador</a>
          <?php else: ?>
          <a href="tel:<?= esc($t['telefono']) ?>" class="hover:underline">☎ <?= esc($t['telefono']) ?></a>
          <?php endif; ?>
        </p>
        <?php endif; ?>
        <?php if ($t['direccion']): ?>
        <p class="text-xs text-gray-400 mt-0.5"><?= esc($t['direccion']) ?></p>
        <?php endif; ?>
        <!-- Ciudades -->
        <?php if (!empty($t['ciudades'])): ?>
        <div class="mt-2 flex flex-wrap gap-1">
          <?php foreach (array_unique($t['ciudades']) as $ciudad): ?>
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
        const json = await res.json();
        renderViajesNotas(json.data);
    } catch(e) {}
}

function renderViajesNotas(notas) {
    const list = document.getElementById('viajes-todo-list');
    const badge = document.getElementById('viajes-todo-badge');
    
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
        <button onclick="deleteViajeNota(${n.id})" class="text-gray-300 hover:text-red-500 p-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
      </div>
    `).join('');
}

async function addViajesTodo() {
    const inp = document.getElementById('new-viajes-todo-text');
    const texto = inp.value.trim();
    if (!texto) return;
    
    try {
        const res = await fetch('<?= BASE_URL ?>/api/viajes_notas.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'add', texto })
        });
        if (res.ok) {
            inp.value = '';
            fetchViajesNotas();
        }
    } catch(e) {}
}

async function toggleViajeNota(id) {
    try {
        await fetch('<?= BASE_URL ?>/api/viajes_notas.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'toggle', id })
        });
        fetchViajesNotas();
    } catch(e) {}
}

async function deleteViajeNota(id) {
    if (!confirm('¿Eliminar este recordatorio?')) return;
    try {
        await fetch('<?= BASE_URL ?>/api/viajes_notas.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'delete', id })
        });
        fetchViajesNotas();
    } catch(e) {}
}
</script>

<?php require_once '../includes/footer.php'; ?>
