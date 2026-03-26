<?php
require_once '../config/db.php';
$title  = 'Nueva venta';
$errors = [];

$cliente_id_default    = (int)($_GET['cliente_id']    ?? 0);
$presupuesto_id_default = (int)($_GET['presupuesto_id'] ?? 0);

$clientes = $pdo->query("SELECT id, nombre, empresa FROM clientes ORDER BY nombre")->fetchAll();

// Si viene desde un presupuesto aprobado, precargar sus items
$pres_items = [];
$pres_data  = null;
if ($presupuesto_id_default) {
    $pres_data = $pdo->prepare("SELECT * FROM presupuestos WHERE id = ? AND estado = 'aprobado'");
    $pres_data->execute([$presupuesto_id_default]);
    $pres_data = $pres_data->fetch();
    if ($pres_data) {
        $pi = $pdo->prepare('SELECT * FROM presupuesto_items WHERE presupuesto_id = ?');
        $pi->execute([$presupuesto_id_default]);
        $pres_items = $pi->fetchAll();
        if (!$cliente_id_default) $cliente_id_default = (int)($pres_data['cliente_id'] ?? 0);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $cliente_id        = (int)($_POST['cliente_id']     ?? 0) ?: null;
    $cliente_texto     = trim($_POST['cliente_texto']   ?? '');
    $presupuesto_id    = (int)($_POST['presupuesto_id'] ?? 0) ?: null;
    $fecha             = $_POST['fecha']             ?? date('Y-m-d');
    $vendedor          = trim($_POST['vendedor']      ?? '');
    $metodo_pago       = trim($_POST['metodo_pago']   ?? '');
    $cobrado           = isset($_POST['cobrado']) ? 1 : 0;
    $tipo_facturacion  = ($_POST['tipo_facturacion'] ?? '') === 'facturada' ? 'facturada' : 'manual';
    $notas             = trim($_POST['notas']         ?? '');
    $items             = array_filter($_POST['items'] ?? [], fn($it) => trim($it['descripcion'] ?? '') !== '');

    if (!$cliente_id && $cliente_texto === '') $errors[] = 'Ingresá o seleccioná un cliente.';
    if (empty($items)) $errors[] = 'Agregá al menos un ítem.';

    if (!$errors) {
        $total = 0;
        foreach ($items as $it) {
            $total += (float)($it['cantidad'] ?? 1) * (float)($it['precio_unitario'] ?? 0);
        }
        $pdo->prepare("INSERT INTO ventas (cliente_id,cliente_texto,presupuesto_id,fecha,vendedor,metodo_pago,cobrado,tipo_facturacion,total,notas) VALUES (?,?,?,?,?,?,?,?,?,?)")
            ->execute([$cliente_id, $cliente_texto ?: null, $presupuesto_id, $fecha, $vendedor, $metodo_pago, $cobrado, $tipo_facturacion, $total, $notas]);

        $vid = $pdo->lastInsertId();
        $stmtItem = $pdo->prepare("INSERT INTO venta_items (venta_id,producto_id,descripcion,cantidad,precio_unitario) VALUES (?,?,?,?,?)");
        foreach ($items as $it) {
            $stmtItem->execute([
                $vid,
                ($it['producto_id'] ?? '') !== '' ? (int)$it['producto_id'] : null,
                trim($it['descripcion']),
                (float)($it['cantidad'] ?? 1),
                (float)($it['precio_unitario'] ?? 0),
            ]);
        }

        // Actualizar estado del cliente a 'activo' si era prospecto
        if ($cliente_id) {
            $pdo->prepare("UPDATE clientes SET estado='activo' WHERE id=? AND estado='prospecto'")
                ->execute([$cliente_id]);
        }

        flash('Venta registrada.');
        redirect('/ventas/ver.php?id=' . $vid);
    }
}

require_once '../includes/header.php';
?>

<div class="max-w-3xl">
  <a href="<?= BASE_URL ?>/ventas/" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Volver
  </a>

  <?php if ($pres_data): ?>
  <div class="mb-4 px-4 py-3 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg text-sm">
    Convertido desde presupuesto <strong>#<?= $presupuesto_id_default ?></strong>
  </div>
  <?php endif; ?>

  <?php if ($errors): ?>
  <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
    <?= implode('<br>', array_map('esc', $errors)) ?>
  </div>
  <?php endif; ?>

  <form method="POST" class="space-y-4">
    <?= csrf_field() ?>
    <input type="hidden" name="presupuesto_id" value="<?= $presupuesto_id_default ?>">

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
      <h2 class="text-sm font-semibold text-gray-700 mb-4">Datos de la venta</h2>
      <div class="grid sm:grid-cols-2 gap-4">
        <!-- Cliente -->
        <div class="sm:col-span-2">
          <div class="flex items-center justify-between mb-1">
            <label class="text-sm font-medium text-gray-700">Cliente *</label>
            <div class="flex gap-3 text-xs">
              <button type="button" id="btn-lista" onclick="usarLista()" class="text-blue-600 hover:underline">Seleccionar de lista</button>
              <button type="button" id="btn-manual" onclick="usarManual()" class="text-gray-400 hover:underline">Escribir nombre</button>
              <a href="<?= BASE_URL ?>/clientes/nuevo.php" target="_blank" class="text-green-600 hover:underline">+ Crear cliente</a>
            </div>
          </div>
          <div id="bloque-lista">
            <select name="cliente_id" id="select-cliente"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="">— Sin asignar —</option>
              <?php foreach ($clientes as $cl): ?>
              <option value="<?= $cl['id'] ?>" <?= (int)$cl['id'] === $cliente_id_default ? 'selected' : '' ?>>
                <?= esc($cl['nombre']) ?><?= $cl['empresa'] ? ' — ' . esc($cl['empresa']) : '' ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div id="bloque-manual" class="hidden">
            <input type="text" name="cliente_texto" id="input-cliente-texto"
              placeholder="Nombre del cliente o empresa"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
          <input type="date" name="fecha" value="<?= date('Y-m-d') ?>"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Vendedor</label>
          <input type="text" name="vendedor" list="vendedores-list" placeholder="Gerencia, Nicolás…"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          <datalist id="vendedores-list">
            <option value="Gerencia">
            <option value="Nicolás">
            <option value="Etienne">
            <option value="Jose Boschetti">
            <option value="Augustin">
          </datalist>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Método de pago</label>
          <select name="metodo_pago"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">— Sin especificar —</option>
            <option value="Efectivo">Efectivo</option>
            <option value="Transferencia">Transferencia</option>
            <option value="Cheques">Cheques</option>
            <option value="Dólares">Dólares</option>
            <option value="Otro">Otro</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
          <select name="tipo_facturacion"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="manual">Manual</option>
            <option value="facturada">Facturada</option>
          </select>
        </div>
        <div class="flex items-center gap-2 pt-5">
          <input type="checkbox" name="cobrado" id="cobrado" value="1"
            class="w-4 h-4 rounded border-gray-300 text-blue-600">
          <label for="cobrado" class="text-sm font-medium text-gray-700">Cobrado</label>
        </div>
        <div class="sm:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
          <textarea name="notas" rows="2"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
      <h2 class="text-sm font-semibold text-gray-700 mb-3">Ítems</h2>
      <div class="mb-4 relative">
        <input type="search" id="buscar-producto" placeholder="Buscar producto…" autocomplete="off"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <div id="resultados-busqueda"
          class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10 hidden max-h-52 overflow-y-auto"></div>
      </div>
      <div id="items-container" class="space-y-2 mb-4">
        <?php foreach ($pres_items as $i => $it): ?>
        <div class="item-row grid grid-cols-12 gap-2 items-start bg-gray-50 rounded-lg p-3">
          <div class="col-span-12 sm:col-span-5">
            <input type="text" name="items[<?= $i ?>][descripcion]" value="<?= esc($it['descripcion']) ?>" required
              class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <input type="hidden" name="items[<?= $i ?>][producto_id]" value="<?= esc($it['producto_id']) ?>">
          </div>
          <div class="col-span-4 sm:col-span-2">
            <input type="number" name="items[<?= $i ?>][cantidad]" value="<?= esc($it['cantidad']) ?>"
              min="0.01" step="0.01"
              class="item-cant w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" oninput="recalcTotal()">
          </div>
          <div class="col-span-5 sm:col-span-3">
            <input type="number" name="items[<?= $i ?>][precio_unitario]" value="<?= esc($it['precio_unitario']) ?>"
              min="0" step="0.01"
              class="item-price w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" oninput="recalcTotal()">
          </div>
          <div class="col-span-9 sm:col-span-1 text-right text-sm font-semibold text-gray-700 py-1.5">
            <span class="item-subtotal"></span>
          </div>
          <div class="col-span-3 sm:col-span-1 text-right py-1">
            <button type="button" onclick="this.closest('.item-row').remove();recalcTotal();"
              class="text-gray-400 hover:text-red-500 p-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" onclick="addItemManual()" class="text-sm text-blue-600 hover:underline">+ Agregar ítem manual</button>
      <div class="mt-4 pt-4 border-t border-gray-100 flex justify-end">
        <div class="text-right">
          <p class="text-xs text-gray-500 mb-0.5">Total</p>
          <p id="total-display" class="text-xl font-bold text-gray-900">$ 0,00</p>
        </div>
      </div>
    </div>

    <div class="flex justify-end gap-3">
      <a href="<?= BASE_URL ?>/ventas/" class="text-sm text-gray-500 px-4 py-2">Cancelar</a>
      <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-6 py-2.5 rounded-lg hover:bg-blue-700">
        Registrar venta
      </button>
    </div>
  </form>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';

function usarLista() {
    document.getElementById('bloque-lista').classList.remove('hidden');
    document.getElementById('bloque-manual').classList.add('hidden');
    document.getElementById('input-cliente-texto').value = '';
    document.getElementById('btn-lista').className = 'text-blue-600 hover:underline';
    document.getElementById('btn-manual').className = 'text-gray-400 hover:underline';
}
function usarManual() {
    document.getElementById('bloque-lista').classList.add('hidden');
    document.getElementById('bloque-manual').classList.remove('hidden');
    document.getElementById('select-cliente').value = '';
    document.getElementById('btn-lista').className = 'text-gray-400 hover:underline';
    document.getElementById('btn-manual').className = 'text-blue-600 hover:underline';
}
let itemIdx = <?= count($pres_items) ?>;
function formatMoney(n) {
  return '$ ' + parseFloat(n||0).toLocaleString('es-AR',{minimumFractionDigits:2,maximumFractionDigits:2});
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
}
function addItem(descripcion, precio, productoId) {
  const idx = itemIdx++;
  const div = document.createElement('div');
  div.className = 'item-row grid grid-cols-12 gap-2 items-start bg-gray-50 rounded-lg p-3';
  div.innerHTML = `
    <div class="col-span-12 sm:col-span-5">
      <input type="text" name="items[${idx}][descripcion]" value="${descripcion.replace(/"/g,'&quot;')}" required
        class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      <input type="hidden" name="items[${idx}][producto_id]" value="${productoId||''}">
    </div>
    <div class="col-span-4 sm:col-span-2">
      <input type="number" name="items[${idx}][cantidad]" value="1" min="0.01" step="0.01"
        class="item-cant w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" oninput="recalcTotal()">
    </div>
    <div class="col-span-5 sm:col-span-3">
      <input type="number" name="items[${idx}][precio_unitario]" value="${precio||0}" min="0" step="0.01"
        class="item-price w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" oninput="recalcTotal()">
    </div>
    <div class="col-span-9 sm:col-span-1 text-right text-sm font-semibold text-gray-700 py-1.5">
      <span class="item-subtotal">${formatMoney(precio)}</span>
    </div>
    <div class="col-span-3 sm:col-span-1 text-right py-1">
      <button type="button" onclick="this.closest('.item-row').remove();recalcTotal();" class="text-gray-400 hover:text-red-500 p-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>`;
  document.getElementById('items-container').appendChild(div);
  recalcTotal();
}
function addItemManual() { addItem('', 0, ''); }
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
        onclick="selectProducto(${p.id},'${p.nombre.replace(/'/g,"\\'")}',${p.precio})">
        <p class="font-medium text-gray-800">${p.nombre}</p>
        <p class="text-xs text-gray-400">$ ${parseFloat(p.precio).toLocaleString('es-AR',{minimumFractionDigits:2})} · Stock: ${p.stock}</p>
      </div>`).join('');
    resultados.classList.remove('hidden');
  }, 250);
});
function selectProducto(id, nombre, precio) {
  addItem(nombre, precio, id);
  inputBuscar.value = '';
  resultados.classList.add('hidden');
}
document.addEventListener('click', e => {
  if (!resultados.contains(e.target) && e.target !== inputBuscar) resultados.classList.add('hidden');
});
recalcTotal();
</script>

<?php require_once '../includes/footer.php'; ?>
