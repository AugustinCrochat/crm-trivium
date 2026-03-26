<?php
require_once '../config/db.php';
$title  = 'Nueva venta';
$errors = [];

$cliente_id_default     = (int)($_GET['cliente_id']     ?? 0);
$presupuesto_id_default = (int)($_GET['presupuesto_id'] ?? 0);

$clientes = $pdo->query("SELECT id, nombre, empresa FROM clientes ORDER BY nombre")->fetchAll();

// Si viene desde un presupuesto aprobado, precargar datos
$pres_data = null;
if ($presupuesto_id_default) {
    $pres_data = $pdo->prepare("SELECT * FROM presupuestos WHERE id = ? AND estado = 'aprobado'");
    $pres_data->execute([$presupuesto_id_default]);
    $pres_data = $pres_data->fetch();
    if ($pres_data && !$cliente_id_default) {
        $cliente_id_default = (int)($pres_data['cliente_id'] ?? 0);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $cliente_id    = (int)($_POST['cliente_id']    ?? 0) ?: null;
    $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
    $presupuesto_id = (int)($_POST['presupuesto_id'] ?? 0) ?: null;
    $fecha          = $_POST['fecha']          ?? date('Y-m-d');
    $producto       = trim($_POST['producto']   ?? '');
    $cantidad       = (float)($_POST['cantidad'] ?? 1);
    $precio_unitario = (float)($_POST['precio_unitario'] ?? 0);
    $total          = $cantidad * $precio_unitario;
    $vendedor       = trim($_POST['vendedor']    ?? '');
    $metodo_pago    = trim($_POST['metodo_pago'] ?? '');
    $cobrado        = isset($_POST['cobrado']) ? 1 : 0;
    $notas          = trim($_POST['notas']       ?? '');

    if (!$cliente_id && $cliente_nombre === '') $errors[] = 'Ingresá o seleccioná un cliente.';
    if ($producto === '') $errors[] = 'El producto es obligatorio.';

    if (!$errors) {
        $pdo->prepare("
            INSERT INTO ventas
              (cliente_id, cliente_nombre, presupuesto_id, fecha, estado,
               producto, cantidad, precio_unitario, total,
               vendedor, metodo_pago, cobrado, notas)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
        ")->execute([
            $cliente_id, $cliente_nombre ?: null, $presupuesto_id, $fecha, 'pendiente',
            $producto, $cantidad, $precio_unitario, $total,
            $vendedor, $metodo_pago, $cobrado, $notas,
        ]);

        $vid = $pdo->lastInsertId();

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

<div class="max-w-lg">
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

  <form method="POST" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-4">
    <?= csrf_field() ?>
    <input type="hidden" name="presupuesto_id" value="<?= $presupuesto_id_default ?>">

    <!-- Cliente -->
    <div>
      <div class="flex items-center justify-between mb-1">
        <label class="text-sm font-medium text-gray-700">Cliente *</label>
        <div class="flex gap-3 text-xs">
          <button type="button" id="btn-lista"  onclick="usarLista()"  class="text-blue-600 hover:underline font-medium">Lista</button>
          <button type="button" id="btn-manual" onclick="usarManual()" class="text-gray-400 hover:underline">Escribir</button>
          <a href="<?= BASE_URL ?>/clientes/nuevo.php" target="_blank" class="text-green-600 hover:underline">+ Crear</a>
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
        <input type="text" name="cliente_nombre" id="input-cliente-nombre"
          placeholder="Nombre del cliente o empresa"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
    </div>

    <!-- Fecha -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
      <input type="date" name="fecha" value="<?= date('Y-m-d') ?>"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <!-- Producto -->
    <div class="relative">
      <label class="block text-sm font-medium text-gray-700 mb-1">Producto *</label>
      <input type="text" name="producto" id="input-producto" autocomplete="off"
        placeholder="Nombre del producto o descripción"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      <div id="resultados-producto"
        class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10 hidden max-h-48 overflow-y-auto"></div>
    </div>

    <!-- Cantidad / Precio / Total -->
    <div class="grid grid-cols-3 gap-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad</label>
        <input type="number" name="cantidad" id="inp-cant" value="1" min="0.01" step="0.01"
          oninput="recalc()"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Precio unit.</label>
        <input type="number" name="precio_unitario" id="inp-precio" value="0" min="0" step="0.01"
          oninput="recalc()"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Total</label>
        <div id="inp-total"
          class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm font-semibold text-gray-700">
          $ 0,00
        </div>
      </div>
    </div>

    <!-- Vendedor / Método de pago -->
    <div class="grid grid-cols-2 gap-3">
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
    </div>

    <!-- Cobrado -->
    <div class="flex items-center gap-2">
      <input type="checkbox" name="cobrado" id="cobrado" value="1"
        class="w-4 h-4 rounded border-gray-300 text-blue-600">
      <label for="cobrado" class="text-sm font-medium text-gray-700">Cobrado</label>
    </div>

    <!-- Notas -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
      <textarea name="notas" rows="3" placeholder="Detalle de productos, condiciones, observaciones…"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
    </div>

    <div class="flex justify-end gap-3 pt-2">
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
    document.getElementById('input-cliente-nombre').value = '';
    document.getElementById('btn-lista').className  = 'text-blue-600 hover:underline font-medium';
    document.getElementById('btn-manual').className = 'text-gray-400 hover:underline';
}
function usarManual() {
    document.getElementById('bloque-lista').classList.add('hidden');
    document.getElementById('bloque-manual').classList.remove('hidden');
    document.getElementById('select-cliente').value = '';
    document.getElementById('btn-lista').className  = 'text-gray-400 hover:underline';
    document.getElementById('btn-manual').className = 'text-blue-600 hover:underline font-medium';
}

function recalc() {
    const cant  = parseFloat(document.getElementById('inp-cant').value)   || 0;
    const price = parseFloat(document.getElementById('inp-precio').value) || 0;
    document.getElementById('inp-total').textContent =
        '$ ' + (cant * price).toLocaleString('es-AR', {minimumFractionDigits:2, maximumFractionDigits:2});
}

// Búsqueda de producto en catálogo
let _timer;
const inputProd = document.getElementById('input-producto');
const resProd   = document.getElementById('resultados-producto');
inputProd.addEventListener('input', function() {
    clearTimeout(_timer);
    const q = this.value.trim();
    if (q.length < 2) { resProd.classList.add('hidden'); return; }
    _timer = setTimeout(async () => {
        const res  = await fetch(`${BASE_URL}/catalogo/buscar.php?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        if (!data.length) { resProd.classList.add('hidden'); return; }
        resProd.innerHTML = data.map(p => `
            <div class="px-3 py-2.5 hover:bg-blue-50 cursor-pointer text-sm border-b border-gray-100 last:border-0"
                onclick="selProd('${p.nombre.replace(/'/g,"\\'")}', ${p.precio})">
              <p class="font-medium text-gray-800">${p.nombre}</p>
              <p class="text-xs text-gray-400">$ ${parseFloat(p.precio).toLocaleString('es-AR',{minimumFractionDigits:2})} · Stock: ${p.stock}</p>
            </div>`).join('');
        resProd.classList.remove('hidden');
    }, 250);
});
function selProd(nombre, precio) {
    inputProd.value = nombre;
    document.getElementById('inp-precio').value = precio;
    recalc();
    resProd.classList.add('hidden');
}
document.addEventListener('click', e => {
    if (!resProd.contains(e.target) && e.target !== inputProd) resProd.classList.add('hidden');
});
</script>

<?php require_once '../includes/footer.php'; ?>
