<?php
require_once '../config/db.php';
$title = 'Envíos';

// Cargar notas/todo para la sección superior
$notas = [];
try {
    $notas = $pdo->query("SELECT * FROM envios_notas ORDER BY completado ASC, created_at DESC")->fetchAll();
} catch (PDOException $ex) {
    // La tabla puede no existir aún
}
$pendientes = count(array_filter($notas, fn($n) => !$n['completado']));

require_once '../includes/header.php';

$estado = $_GET['estado'] ?? 'todos';
$tipo   = $_GET['tipo']   ?? 'todos';

$where  = [];
$params = [];

if ($estado !== 'todos') {
    $where[]  = 'e.estado = ?';
    $params[] = $estado;
}
if ($tipo !== 'todos') {
    $where[]  = 'e.tipo = ?';
    $params[] = $tipo;
}

$sql = "
    SELECT e.*,
           c.nombre AS cliente_nombre, c.ciudad,
           t.nombre AS transporte_nombre,
           vj.fecha AS viaje_fecha
    FROM envios e
    LEFT JOIN clientes c    ON c.id  = e.cliente_id
    LEFT JOIN transportes t ON t.id  = e.transporte_id
    LEFT JOIN viajes vj     ON vj.id = e.viaje_id
";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY e.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$envios = $stmt->fetchAll();

$labels_estado = ['todos'=>'Todos','pendiente'=>'Pendiente','en_transito'=>'En tránsito','entregado'=>'Entregado'];
$labels_tipo   = ['todos'=>'Todos','expreso'=>'Expreso','camion_plancha_deposito'=>'Camión → Depósito','camion_plancha_directo'=>'Camión → Directo'];
?>

<!-- ══ Sección: Cosas a tener en cuenta (TODO list) ══ -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-5" id="todo-section">
  <button onclick="toggleTodo()" type="button"
    class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-gray-50 transition-colors rounded-xl">
    <div class="flex items-center gap-2">
      <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
      </svg>
      <span class="text-sm font-semibold text-gray-700">Cosas a tener en cuenta</span>
      <?php if ($pendientes > 0): ?>
      <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-amber-500 rounded-full" id="todo-badge">
        <?= $pendientes ?>
      </span>
      <?php else: ?>
      <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-amber-500 rounded-full hidden" id="todo-badge">0</span>
      <?php endif; ?>
    </div>
    <svg id="todo-chevron" class="w-4 h-4 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
    </svg>
  </button>

  <div id="todo-body" class="hidden border-t border-gray-100">
    <!-- Formulario para agregar -->
    <div class="px-4 py-3 border-b border-gray-100">
      <form onsubmit="addTodo(event)" class="flex gap-2">
        <input type="text" id="todo-input" placeholder="Agregar recordatorio…"
          class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
        <button type="submit"
          class="bg-amber-500 text-white text-sm font-medium px-3 py-2 rounded-lg hover:bg-amber-600 transition-colors flex-shrink-0">
          +
        </button>
      </form>
    </div>
    <!-- Lista de items -->
    <div id="todo-list" class="divide-y divide-gray-50 max-h-64 overflow-y-auto">
      <?php if (empty($notas)): ?>
      <p class="px-4 py-6 text-center text-sm text-gray-400" id="todo-empty">No hay recordatorios</p>
      <?php else: ?>
      <?php foreach ($notas as $nota): ?>
      <div class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 group todo-item" data-id="<?= $nota['id'] ?>">
        <button onclick="toggleItem(<?= $nota['id'] ?>)" class="flex-shrink-0">
          <?php if ($nota['completado']): ?>
          <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
          </svg>
          <?php else: ?>
          <svg class="w-5 h-5 text-gray-300 hover:text-amber-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke-width="2"/>
          </svg>
          <?php endif; ?>
        </button>
        <span class="flex-1 text-sm <?= $nota['completado'] ? 'text-gray-400 line-through' : 'text-gray-700' ?>">
          <?= esc($nota['texto']) ?>
        </span>
        <button onclick="deleteItem(<?= $nota['id'] ?>)"
          class="flex-shrink-0 opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500 transition-all p-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="flex items-center justify-between mb-3 gap-3">
  <h2 class="text-sm font-medium text-gray-500">Listado de envíos</h2>
  <a href="<?= BASE_URL ?>/envios/nuevo.php"
    class="flex-shrink-0 bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700">
    + Nuevo
  </a>
</div>

<!-- Filtros -->
<div class="flex flex-wrap gap-1 mb-2">
  <?php foreach ($labels_estado as $key => $lbl): ?>
  <a href="?estado=<?= $key ?>&tipo=<?= esc($tipo) ?>"
    class="flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium transition-colors
    <?= $estado === $key ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
    <?= $lbl ?>
  </a>
  <?php endforeach; ?>
</div>
<div class="flex flex-wrap gap-1 mb-4">
  <?php foreach ($labels_tipo as $key => $lbl): ?>
  <a href="?estado=<?= esc($estado) ?>&tipo=<?= $key ?>"
    class="flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium transition-colors
    <?= $tipo === $key ? 'bg-gray-700 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
    <?= $lbl ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if (!$envios): ?>
<div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
  <p class="text-gray-400 text-sm">No hay envíos</p>
</div>
<?php else: ?>
<div class="bg-white rounded-xl border border-gray-200 shadow-sm divide-y divide-gray-100">
  <?php foreach ($envios as $e): ?>
  <a href="<?= BASE_URL ?>/envios/ver.php?id=<?= $e['id'] ?>"
    class="flex items-center gap-4 px-4 py-3.5 hover:bg-gray-50 transition-colors">
    <div class="min-w-0 flex-1">
      <p class="text-sm font-medium text-gray-900 truncate"><?= esc($e['cliente_nombre'] ?: 'Sin cliente') ?></p>
      <p class="text-xs text-gray-400">
        <?= esc($e['ciudad'] ?: '') ?>
        <?= $e['transporte_nombre'] ? ' · ' . esc($e['transporte_nombre']) : '' ?>
        <?= $e['viaje_fecha'] ? ' · Viaje ' . fecha($e['viaje_fecha']) : '' ?>
      </p>
      <p class="text-xs text-gray-400 mt-0.5"><?= tipo_envio($e['tipo']) ?></p>
    </div>
    <div class="flex-shrink-0"><?= badge($e['estado']) ?></div>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<style>
/* En móvil, el botón de borrar siempre visible */
@media (max-width: 1023px) {
  .todo-item button:last-child { opacity: 1 !important; }
}
</style>

<script>
const BASE = '<?= BASE_URL ?>';
const API  = BASE + '/api/envios_notas.php';

function toggleTodo() {
  const body = document.getElementById('todo-body');
  const chevron = document.getElementById('todo-chevron');
  body.classList.toggle('hidden');
  chevron.style.transform = body.classList.contains('hidden') ? '' : 'rotate(180deg)';
}

function updateBadge() {
  const items = document.querySelectorAll('.todo-item');
  let pending = 0;
  items.forEach(el => {
    const icon = el.querySelector('button:first-child svg');
    if (icon && !icon.classList.contains('text-green-500')) pending++;
  });
  const badge = document.getElementById('todo-badge');
  if (pending > 0) {
    badge.textContent = pending;
    badge.classList.remove('hidden');
  } else {
    badge.classList.add('hidden');
  }
  // Show/hide empty message
  const emptyMsg = document.getElementById('todo-empty');
  if (emptyMsg) emptyMsg.style.display = items.length === 0 ? '' : 'none';
}

async function addTodo(e) {
  e.preventDefault();
  const input = document.getElementById('todo-input');
  const texto = input.value.trim();
  if (!texto) return;

  const res = await fetch(API, {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ action: 'add', texto })
  });
  const data = await res.json();
  if (!data.ok) return;

  input.value = '';

  // Remove empty message if exists
  const empty = document.getElementById('todo-empty');
  if (empty) empty.remove();

  const list = document.getElementById('todo-list');
  const div = document.createElement('div');
  div.className = 'flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 group todo-item';
  div.dataset.id = data.id;
  div.innerHTML = `
    <button onclick="toggleItem(${data.id})" class="flex-shrink-0">
      <svg class="w-5 h-5 text-gray-300 hover:text-amber-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="10" stroke-width="2"/>
      </svg>
    </button>
    <span class="flex-1 text-sm text-gray-700">${texto.replace(/</g,'&lt;')}</span>
    <button onclick="deleteItem(${data.id})"
      class="flex-shrink-0 opacity-0 group-hover:opacity-100 text-gray-400 hover:text-red-500 transition-all p-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>`;
  list.prepend(div);
  updateBadge();
}

async function toggleItem(id) {
  const res = await fetch(API, {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ action: 'toggle', id })
  });
  const data = await res.json();
  if (!data.ok) return;

  const item = document.querySelector(`.todo-item[data-id="${id}"]`);
  if (!item) return;

  const completed = parseInt(data.data.completado);
  const btn = item.querySelector('button:first-child');
  const span = item.querySelector('span');

  if (completed) {
    btn.innerHTML = `<svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
    </svg>`;
    span.classList.add('text-gray-400', 'line-through');
    span.classList.remove('text-gray-700');
  } else {
    btn.innerHTML = `<svg class="w-5 h-5 text-gray-300 hover:text-amber-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="10" stroke-width="2"/>
    </svg>`;
    span.classList.remove('text-gray-400', 'line-through');
    span.classList.add('text-gray-700');
  }
  updateBadge();
}

async function deleteItem(id) {
  const res = await fetch(API, {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ action: 'delete', id })
  });
  const data = await res.json();
  if (!data.ok) return;

  const item = document.querySelector(`.todo-item[data-id="${id}"]`);
  if (item) item.remove();

  // Show empty msg if no items left
  const list = document.getElementById('todo-list');
  if (!list.querySelector('.todo-item')) {
    list.innerHTML = '<p class="px-4 py-6 text-center text-sm text-gray-400" id="todo-empty">No hay recordatorios</p>';
  }
  updateBadge();
}
</script>

<?php require_once '../includes/footer.php'; ?>
