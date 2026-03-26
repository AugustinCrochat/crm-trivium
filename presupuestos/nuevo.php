<?php
require_once '../config/db.php';
$title  = 'Nuevo presupuesto';
$errors = [];

// Cliente preseleccionado desde URL
$cliente_id_default = (int)($_GET['cliente_id'] ?? 0);

// Clientes para el select
$clientes = $pdo->query("SELECT id, nombre, empresa FROM clientes WHERE estado != 'guardado' ORDER BY nombre")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $cliente_id  = (int)($_POST['cliente_id'] ?? 0) ?: null;
    $fecha        = $_POST['fecha']        ?? date('Y-m-d');
    $validez_dias = (int)($_POST['validez_dias'] ?? 15);
    $estado       = $_POST['estado']       ?? 'borrador';
    $notas        = trim($_POST['notas']   ?? '');
    $items        = $_POST['items']        ?? [];

    // Filtrar items vacíos
    $items = array_filter($items, fn($it) => trim($it['descripcion'] ?? '') !== '');

    if (empty($items)) $errors[] = 'Agregá al menos un ítem.';

    if (!$errors) {
        $total = 0;
        foreach ($items as $it) {
            $total += (float)($it['cantidad'] ?? 1) * (float)($it['precio_unitario'] ?? 0);
        }

        $pdo->prepare("
            INSERT INTO presupuestos (cliente_id,fecha,validez_dias,estado,notas,total)
            VALUES (?,?,?,?,?,?)
        ")->execute([$cliente_id, $fecha, $validez_dias, $estado, $notas, $total]);

        $pid = $pdo->lastInsertId();

        $stmtItem = $pdo->prepare("
            INSERT INTO presupuesto_items (presupuesto_id,producto_id,descripcion,cantidad,precio_unitario)
            VALUES (?,?,?,?,?)
        ");
        foreach ($items as $it) {
            $stmtItem->execute([
                $pid,
                ($it['producto_id'] ?? '') !== '' ? (int)$it['producto_id'] : null,
                trim($it['descripcion']),
                (float)($it['cantidad'] ?? 1),
                (float)($it['precio_unitario'] ?? 0),
            ]);
        }

        flash('Presupuesto creado.');
        redirect('/presupuestos/ver.php?id=' . $pid);
    }
}

require_once '../includes/header.php';
?>

<div class="max-w-3xl">
  <a href="<?= BASE_URL ?>/presupuestos/" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Volver
  </a>

  <?php if ($errors): ?>
  <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
    <?= implode('<br>', array_map('esc', $errors)) ?>
  </div>
  <?php endif; ?>

  <form method="POST" id="form-presupuesto" class="space-y-4">
    <?= csrf_field() ?>

    <!-- Datos generales -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
      <h2 class="text-sm font-semibold text-gray-700 mb-4">Datos generales</h2>
      <div class="grid sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
          <select name="cliente_id"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">— Sin asignar —</option>
            <?php foreach ($clientes as $cl): ?>
            <option value="<?= $cl['id'] ?>" <?= (int)$cl['id'] === $cliente_id_default ? 'selected' : '' ?>>
              <?= esc($cl['nombre']) ?><?= $cl['empresa'] ? ' — ' . esc($cl['empresa']) : '' ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
          <input type="date" name="fecha" value="<?= date('Y-m-d') ?>"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Validez (días)</label>
          <input type="number" name="validez_dias" value="15" min="1"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
          <select name="estado"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="borrador">Borrador</option>
            <option value="enviado">Enviado</option>
          </select>
        </div>
        <div class="sm:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
          <textarea name="notas" rows="2"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
        </div>
      </div>
    </div>

    <!-- Items -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
      <h2 class="text-sm font-semibold text-gray-700 mb-3">Ítems</h2>

      <!-- Buscador de productos -->
      <div class="mb-4 relative">
        <input type="search" id="buscar-producto" placeholder="Buscar producto para agregar…"
          autocomplete="off"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <div id="resultados-busqueda"
          class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10 hidden max-h-52 overflow-y-auto">
        </div>
      </div>

      <!-- Lista de items -->
      <div id="items-container" class="space-y-2 mb-4">
        <!-- Los ítems se agregan dinámicamente -->
      </div>

      <p id="no-items" class="text-sm text-gray-400 text-center py-4">
        Buscá un producto o agregá un ítem manual
      </p>

      <button type="button" onclick="addItemManual()"
        class="text-sm text-blue-600 hover:underline">
        + Agregar ítem manual
      </button>

      <!-- Total -->
      <div class="mt-4 pt-4 border-t border-gray-100 flex justify-end">
        <div class="text-right">
          <p class="text-xs text-gray-500 mb-0.5">Total</p>
          <p id="total-display" class="text-xl font-bold text-gray-900">$ 0,00</p>
        </div>
      </div>
    </div>

    <div class="flex justify-end gap-3">
      <a href="<?= BASE_URL ?>/presupuestos/" class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2">Cancelar</a>
      <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-6 py-2.5 rounded-lg hover:bg-blue-700">
        Guardar presupuesto
      </button>
    </div>
  </form>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
let itemIdx = 0;

function formatMoney(n) {
  return '$ ' + parseFloat(n || 0).toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function recalcTotal() {
  let total = 0;
  document.querySelectorAll('.item-row').forEach(row => {
    const cant  = parseFloat(row.querySelector('.item-cant').value)  || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    total += cant * price;
    row.querySelector('.item-subtotal').textContent = formatMoney(cant * price);
  });
  document.getElementById('total-display').textContent = formatMoney(total);
  document.getElementById('no-items').style.display =
    document.querySelectorAll('.item-row').length ? 'none' : '';
}

function addItem(descripcion, precio, productoId) {
  const idx = itemIdx++;
  const div = document.createElement('div');
  div.className = 'item-row grid grid-cols-12 gap-2 items-start bg-gray-50 rounded-lg p-3';
  div.innerHTML = `
    <div class="col-span-12 sm:col-span-5">
      <input type="text" name="items[${idx}][descripcion]" value="${descripcion.replace(/"/g,'&quot;')}"
        placeholder="Descripción" required
        class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      <input type="hidden" name="items[${idx}][producto_id]" value="${productoId || ''}">
    </div>
    <div class="col-span-4 sm:col-span-2">
      <input type="number" name="items[${idx}][cantidad]" value="1" min="0.01" step="0.01"
        placeholder="Cant."
        class="item-cant w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        oninput="recalcTotal()">
    </div>
    <div class="col-span-5 sm:col-span-3">
      <input type="number" name="items[${idx}][precio_unitario]" value="${precio || 0}" min="0" step="0.01"
        placeholder="Precio unit."
        class="item-price w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        oninput="recalcTotal()">
    </div>
    <div class="col-span-9 sm:col-span-1 text-right text-sm font-semibold text-gray-700 py-1.5">
      <span class="item-subtotal">${formatMoney(precio)}</span>
    </div>
    <div class="col-span-3 sm:col-span-1 text-right py-1">
      <button type="button" onclick="this.closest('.item-row').remove(); recalcTotal();"
        class="text-gray-400 hover:text-red-500 p-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>
  `;
  document.getElementById('items-container').appendChild(div);
  recalcTotal();
}

function addItemManual() {
  addItem('', 0, '');
}

// Buscador de productos
let searchTimer;
const inputBuscar = document.getElementById('buscar-producto');
const resultados  = document.getElementById('resultados-busqueda');

inputBuscar.addEventListener('input', function() {
  clearTimeout(searchTimer);
  const q = this.value.trim();
  if (q.length < 2) { resultados.classList.add('hidden'); return; }
  searchTimer = setTimeout(async () => {
    const res  = await fetch(`${BASE_URL}/catalogo/buscar.php?q=${encodeURIComponent(q)}`);
    const data = await res.json();
    if (!data.length) { resultados.classList.add('hidden'); return; }
    resultados.innerHTML = data.map(p => `
      <div class="px-3 py-2.5 hover:bg-blue-50 cursor-pointer text-sm border-b border-gray-100 last:border-0"
        onclick="selectProducto(${p.id}, '${p.nombre.replace(/'/g,"\\'")}', ${p.precio})">
        <p class="font-medium text-gray-800">${p.nombre}</p>
        <p class="text-xs text-gray-400">$ ${parseFloat(p.precio).toLocaleString('es-AR',{minimumFractionDigits:2})} · Stock: ${p.stock}</p>
      </div>
    `).join('');
    resultados.classList.remove('hidden');
  }, 250);
});

function selectProducto(id, nombre, precio) {
  addItem(nombre, precio, id);
  inputBuscar.value = '';
  resultados.classList.add('hidden');
}

document.addEventListener('click', e => {
  if (!resultados.contains(e.target) && e.target !== inputBuscar) {
    resultados.classList.add('hidden');
  }
});
</script>

<?php require_once '../includes/footer.php'; ?>
