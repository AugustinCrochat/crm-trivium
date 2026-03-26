<?php
require_once '../config/db.php';

$id = (int)($_GET['id'] ?? 0);
$pres = $pdo->prepare('SELECT * FROM presupuestos WHERE id = ?');
$pres->execute([$id]);
$pres = $pres->fetch();
if (!$pres) { flash('Presupuesto no encontrado.','error'); redirect('/presupuestos/'); }

$items_actuales = $pdo->prepare('SELECT * FROM presupuesto_items WHERE presupuesto_id = ? ORDER BY id');
$items_actuales->execute([$id]);
$items_actuales = $items_actuales->fetchAll();

$clientes = $pdo->query("SELECT id, nombre, empresa FROM clientes ORDER BY nombre")->fetchAll();
$title    = 'Editar presupuesto #' . $id;
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $cliente_id   = (int)($_POST['cliente_id'] ?? 0) ?: null;
    $fecha        = $_POST['fecha']        ?? date('Y-m-d');
    $validez_dias = (int)($_POST['validez_dias'] ?? 15);
    $estado       = $_POST['estado']       ?? 'enviado';
    $notas        = trim($_POST['notas']   ?? '');
    $items        = array_filter($_POST['items'] ?? [], fn($it) => trim($it['descripcion'] ?? '') !== '');

    if (empty($items)) $errors[] = 'Agregá al menos un ítem.';

    if (!$errors) {
        $total = 0;
        foreach ($items as $it) {
            $total += (float)($it['cantidad'] ?? 1) * (float)($it['precio_unitario'] ?? 0) * (1 + (float)($it['iva'] ?? 0) / 100);
        }
        $pdo->prepare("UPDATE presupuestos SET cliente_id=?,fecha=?,validez_dias=?,estado=?,notas=?,total=? WHERE id=?")
            ->execute([$cliente_id, $fecha, $validez_dias, $estado, $notas, $total, $id]);

        $pdo->prepare("DELETE FROM presupuesto_items WHERE presupuesto_id=?")->execute([$id]);
        $stmtItem = $pdo->prepare("INSERT INTO presupuesto_items (presupuesto_id,producto_id,descripcion,cantidad,precio_unitario,iva) VALUES (?,?,?,?,?,?)");
        foreach ($items as $it) {
            $stmtItem->execute([
                $id,
                ($it['producto_id'] ?? '') !== '' ? (int)$it['producto_id'] : null,
                trim($it['descripcion']),
                (float)($it['cantidad'] ?? 1),
                (float)($it['precio_unitario'] ?? 0),
                (float)($it['iva'] ?? 0),
            ]);
        }

        flash('Presupuesto actualizado.');
        redirect('/presupuestos/ver.php?id=' . $id);
    }
}

require_once '../includes/header.php';
?>

<div class="max-w-3xl">
  <a href="<?= BASE_URL ?>/presupuestos/ver.php?id=<?= $id ?>" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Volver
  </a>

  <?php if ($errors): ?>
  <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
    <?= implode('<br>', array_map('esc', $errors)) ?>
  </div>
  <?php endif; ?>

  <form method="POST" class="space-y-4">
    <?= csrf_field() ?>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
      <h2 class="text-sm font-semibold text-gray-700 mb-4">Datos generales</h2>
      <div class="grid sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
          <div class="flex items-center justify-between mb-1">
            <label class="text-sm font-medium text-gray-700">Cliente</label>
            <button type="button" onclick="document.getElementById('modal-cliente').showModal()"
              class="text-xs text-blue-600 hover:underline">+ Nuevo cliente</button>
          </div>
          <select name="cliente_id" id="sel-cliente"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">— Sin asignar —</option>
            <?php foreach ($clientes as $cl): ?>
            <option value="<?= $cl['id'] ?>" <?= (int)$cl['id'] === (int)$pres['cliente_id'] ? 'selected' : '' ?>>
              <?= esc($cl['nombre']) ?><?= $cl['empresa'] ? ' — ' . esc($cl['empresa']) : '' ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
          <input type="date" name="fecha" value="<?= esc($pres['fecha']) ?>"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Validez (días)</label>
          <input type="number" name="validez_dias" value="<?= (int)$pres['validez_dias'] ?>"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
          <select name="estado"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <?php foreach (['enviado'=>'Enviado','borrador'=>'Borrador','aprobado'=>'Aprobado','rechazado'=>'Rechazado'] as $est => $lbl): ?>
            <option value="<?= $est ?>" <?= $pres['estado'] === $est ? 'selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="sm:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
          <textarea name="notas" rows="2"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"><?= esc($pres['notas']) ?></textarea>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
      <h2 class="text-sm font-semibold text-gray-700 mb-3">Ítems</h2>
      <div class="mb-4 relative">
        <input type="search" id="buscar-producto" placeholder="Buscar producto para agregar…" autocomplete="off"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <div id="resultados-busqueda"
          class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10 hidden max-h-52 overflow-y-auto"></div>
      </div>
      <div id="items-container" class="space-y-2 mb-4">
        <?php foreach ($items_actuales as $i => $it): ?>
        <div class="item-row bg-gray-50 rounded-lg p-3 space-y-2">
          <div class="flex gap-2 items-start">
            <input type="text" name="items[<?= $i ?>][descripcion]" value="<?= esc($it['descripcion']) ?>" required
              class="flex-1 border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <input type="hidden" name="items[<?= $i ?>][producto_id]" value="<?= esc($it['producto_id']) ?>">
            <button type="button" onclick="this.closest('.item-row').remove(); recalcTotal();"
              class="flex-shrink-0 text-gray-400 hover:text-red-500 p-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          <div class="flex gap-2 items-center flex-wrap">
            <input type="number" name="items[<?= $i ?>][cantidad]" value="<?= esc($it['cantidad']) ?>"
              min="0.01" step="0.01" placeholder="Cant."
              class="item-cant w-20 border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              oninput="recalcTotal()">
            <span class="text-gray-400 text-xs">×</span>
            <input type="number" name="items[<?= $i ?>][precio_unitario]" value="<?= esc($it['precio_unitario']) ?>"
              min="0" step="0.01" placeholder="Precio s/IVA"
              class="item-price flex-1 min-w-0 border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              oninput="recalcTotal()">
            <?php $iva_val = (float)($it['iva'] ?? 0); ?>
            <select name="items[<?= $i ?>][iva]"
              class="item-iva border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
              onchange="recalcTotal()">
              <option value="0"    <?= $iva_val == 0    ? 'selected' : '' ?>>0% IVA</option>
              <option value="10.5" <?= $iva_val == 10.5 ? 'selected' : '' ?>>10.5%</option>
              <option value="21"   <?= $iva_val == 21   ? 'selected' : '' ?>>21%</option>
              <option value="27"   <?= $iva_val == 27   ? 'selected' : '' ?>>27%</option>
            </select>
            <span class="item-subtotal text-sm font-semibold text-gray-700 text-right w-24"></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" onclick="addItemManual()" class="text-sm text-blue-600 hover:underline">+ Agregar ítem manual</button>
      <div class="mt-4 pt-4 border-t border-gray-100 space-y-1 text-right">
        <p class="text-xs text-gray-400">Subtotal s/IVA: <span id="subtotal-display" class="font-medium text-gray-600">$ 0,00</span></p>
        <p class="text-xs text-gray-400">IVA: <span id="iva-display" class="font-medium text-gray-600">$ 0,00</span></p>
        <p class="text-sm font-semibold text-gray-700">Total c/IVA: <span id="total-display" class="text-xl font-bold text-gray-900">$ 0,00</span></p>
      </div>
    </div>

    <div class="flex justify-end gap-3">
      <a href="<?= BASE_URL ?>/presupuestos/ver.php?id=<?= $id ?>" class="text-sm text-gray-500 px-4 py-2">Cancelar</a>
      <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-6 py-2.5 rounded-lg hover:bg-blue-700">Guardar cambios</button>
    </div>
  </form>
</div>

<!-- Modal nuevo cliente -->
<dialog id="modal-cliente" class="rounded-xl shadow-2xl p-6 w-full max-w-sm backdrop:bg-black/40">
  <h3 class="text-base font-semibold text-gray-800 mb-4">Nuevo cliente</h3>
  <div class="space-y-3">
    <input type="text" id="nc-nombre" placeholder="Nombre *" required
      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    <input type="text" id="nc-empresa" placeholder="Empresa"
      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    <input type="tel" id="nc-telefono" placeholder="Teléfono / WhatsApp"
      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    <p id="nc-error" class="text-xs text-red-600 hidden"></p>
  </div>
  <div class="flex justify-end gap-2 mt-5">
    <button type="button" onclick="document.getElementById('modal-cliente').close()"
      class="text-sm text-gray-500 px-4 py-2 hover:text-gray-700">Cancelar</button>
    <button type="button" onclick="crearCliente()"
      class="bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700">Crear</button>
  </div>
</dialog>

<script>
const BASE_URL   = '<?= BASE_URL ?>';
const CSRF_TOKEN = '<?= $_SESSION['csrf_token'] ?? '' ?>';
let itemIdx = <?= count($items_actuales) ?>;

function formatMoney(n) {
  return '$ ' + parseFloat(n || 0).toLocaleString('es-AR', {minimumFractionDigits:2, maximumFractionDigits:2});
}
function recalcTotal() {
  let subtotal = 0, ivaTotal = 0;
  document.querySelectorAll('.item-row').forEach(row => {
    const cant  = parseFloat(row.querySelector('.item-cant').value)  || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    const iva   = parseFloat(row.querySelector('.item-iva').value)   || 0;
    const base  = cant * price;
    const ivaMonto = base * iva / 100;
    subtotal += base;
    ivaTotal += ivaMonto;
    row.querySelector('.item-subtotal').textContent = formatMoney(base + ivaMonto);
  });
  document.getElementById('subtotal-display').textContent = formatMoney(subtotal);
  document.getElementById('iva-display').textContent      = formatMoney(ivaTotal);
  document.getElementById('total-display').textContent    = formatMoney(subtotal + ivaTotal);
}
function addItem(descripcion, precio, productoId, iva) {
  iva = iva !== undefined ? iva : 21;
  const idx = itemIdx++;
  const div = document.createElement('div');
  div.className = 'item-row bg-gray-50 rounded-lg p-3 space-y-2';
  div.innerHTML = `
    <div class="flex gap-2 items-start">
      <input type="text" name="items[${idx}][descripcion]" value="${descripcion.replace(/"/g,'&quot;')}" required
        class="flex-1 border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      <input type="hidden" name="items[${idx}][producto_id]" value="${productoId||''}">
      <button type="button" onclick="this.closest('.item-row').remove();recalcTotal();"
        class="flex-shrink-0 text-gray-400 hover:text-red-500 p-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="flex gap-2 items-center flex-wrap">
      <input type="number" name="items[${idx}][cantidad]" value="1" min="0.01" step="0.01" placeholder="Cant."
        class="item-cant w-20 border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" oninput="recalcTotal()">
      <span class="text-gray-400 text-xs">×</span>
      <input type="number" name="items[${idx}][precio_unitario]" value="${precio||0}" min="0" step="0.01" placeholder="Precio s/IVA"
        class="item-price flex-1 min-w-0 border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" oninput="recalcTotal()">
      <select name="items[${idx}][iva]"
        class="item-iva border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white" onchange="recalcTotal()">
        <option value="0"   ${iva==0?'selected':''}>0% IVA</option>
        <option value="10.5" ${iva==10.5?'selected':''}>10.5%</option>
        <option value="21"  ${iva==21?'selected':''}>21%</option>
        <option value="27"  ${iva==27?'selected':''}>27%</option>
      </select>
      <span class="item-subtotal text-sm font-semibold text-gray-700 text-right w-24">${formatMoney(precio)}</span>
    </div>`;
  document.getElementById('items-container').appendChild(div);
  recalcTotal();
}
function addItemManual() { addItem('', 0, '', 21); }

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
  addItem(nombre, precio, id, 21);
  inputBuscar.value = '';
  resultados.classList.add('hidden');
}
document.addEventListener('click', e => {
  if (!resultados.contains(e.target) && e.target !== inputBuscar) resultados.classList.add('hidden');
});

async function crearCliente() {
  const nombre = document.getElementById('nc-nombre').value.trim();
  const errEl  = document.getElementById('nc-error');
  errEl.classList.add('hidden');
  if (!nombre) { errEl.textContent = 'El nombre es obligatorio.'; errEl.classList.remove('hidden'); return; }
  const empresa  = document.getElementById('nc-empresa').value.trim();
  const telefono = document.getElementById('nc-telefono').value.trim();
  const res = await fetch(`${BASE_URL}/clientes/rapido.php`, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({nombre, empresa, telefono, csrf_token: CSRF_TOKEN})
  });
  const d = await res.json();
  if (d.ok) {
    const sel = document.getElementById('sel-cliente');
    sel.add(new Option(nombre + (empresa ? ' — ' + empresa : ''), d.id, true, true));
    sel.value = d.id;
    document.getElementById('modal-cliente').close();
    ['nc-nombre','nc-empresa','nc-telefono'].forEach(id => document.getElementById(id).value = '');
  } else {
    errEl.textContent = d.error || 'Error al crear el cliente.';
    errEl.classList.remove('hidden');
  }
}

recalcTotal();
</script>

<?php require_once '../includes/footer.php'; ?>
