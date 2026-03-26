<?php
require_once '../config/db.php';

$id  = (int)($_GET['id'] ?? 0);
$imp = $pdo->prepare('SELECT * FROM importaciones WHERE id = ?');
$imp->execute([$id]);
$imp = $imp->fetch();
if (!$imp) { flash('Importación no encontrada.','error'); redirect('/importaciones/'); }

$title      = 'Editar importación';
$errors     = [];
$forwarders = $pdo->query("SELECT id, nombre FROM forwarders WHERE activo=1 ORDER BY nombre")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data = [
        'proveedor'         => trim($_POST['proveedor']         ?? ''),
        'origen'            => trim($_POST['origen']            ?? ''),
        'familia_productos' => trim($_POST['familia_productos'] ?? ''),
        'numero_proforma'   => trim($_POST['numero_proforma']   ?? ''),
        'monto_fob'         => $_POST['monto_fob'] !== '' ? (float)$_POST['monto_fob'] : null,
        'etd'               => $_POST['etd']  ?: null,
        'eta'               => $_POST['eta']  ?: null,
        'numero_bl'         => trim($_POST['numero_bl']         ?? ''),
        'nombre_barco'      => trim($_POST['nombre_barco']      ?? ''),
        'forwarder_id'      => (int)($_POST['forwarder_id'] ?? 0) ?: null,
        'observaciones'     => trim($_POST['observaciones']     ?? ''),
        'estado'            => $_POST['estado'] ?? 'pendiente',
    ];

    $estados_validos = ['pendiente','embarcado','arribado','cerrado'];
    if (!in_array($data['estado'], $estados_validos)) $data['estado'] = 'pendiente';
    if ($data['proveedor'] === '') $errors[] = 'El proveedor es obligatorio.';

    if (!$errors) {
        $pdo->prepare("
            UPDATE importaciones SET
              proveedor=?, origen=?, familia_productos=?, numero_proforma=?, monto_fob=?,
              etd=?, eta=?, numero_bl=?, nombre_barco=?, forwarder_id=?, observaciones=?, estado=?
            WHERE id=?
        ")->execute([...array_values($data), $id]);
        flash('Importación actualizada.');
        redirect('/importaciones/ver.php?id=' . $id);
    }
}

$form = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $imp;

require_once '../includes/header.php';
?>

<div class="max-w-2xl">
  <a href="<?= BASE_URL ?>/importaciones/ver.php?id=<?= $id ?>" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Volver
  </a>

  <?php if ($errors): ?>
  <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
    <?= implode('<br>', array_map('esc', $errors)) ?>
  </div>
  <?php endif; ?>

  <form method="POST" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 space-y-4">
    <?= csrf_field() ?>

    <div class="grid sm:grid-cols-2 gap-4">

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor *</label>
        <input type="text" name="proveedor" value="<?= esc($form['proveedor']) ?>" required
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Origen (país)</label>
        <input type="text" name="origen" value="<?= esc($form['origen']) ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Familia de productos</label>
        <input type="text" name="familia_productos" value="<?= esc($form['familia_productos']) ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">N° de proforma</label>
        <input type="text" name="numero_proforma" value="<?= esc($form['numero_proforma']) ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Monto FOB (USD)</label>
        <input type="number" name="monto_fob" value="<?= esc($form['monto_fob']) ?>"
          min="0" step="0.01"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
        <select name="estado"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          <?php foreach (['pendiente'=>'Pendiente','embarcado'=>'Embarcado','arribado'=>'Arribado','cerrado'=>'Cerrado'] as $v => $l): ?>
          <option value="<?= $v ?>" <?= $form['estado'] === $v ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">ETD (salida)</label>
        <input type="date" name="etd" value="<?= esc($form['etd'] ?? '') ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">ETA (llegada)</label>
        <input type="date" name="eta" value="<?= esc($form['eta'] ?? '') ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">N° de B/L</label>
        <input type="text" name="numero_bl" value="<?= esc($form['numero_bl']) ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del barco</label>
        <input type="nombre_barco" name="nombre_barco" value="<?= esc($form['nombre_barco']) ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div class="sm:col-span-2">
        <div class="flex items-center justify-between mb-1">
          <label class="text-sm font-medium text-gray-700">Forwarder</label>
          <a href="<?= BASE_URL ?>/forwarders/nuevo.php" target="_blank"
            class="text-xs text-green-600 hover:underline">+ Nuevo forwarder</a>
        </div>
        <select name="forwarder_id"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">— Sin asignar —</option>
          <?php foreach ($forwarders as $fw): ?>
          <option value="<?= $fw['id'] ?>" <?= ($form['forwarder_id'] ?? '') == $fw['id'] ? 'selected' : '' ?>>
            <?= esc($fw['nombre']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
        <textarea name="observaciones" rows="3"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"><?= esc($form['observaciones']) ?></textarea>
      </div>

    </div>

    <div class="flex justify-end gap-3 pt-2">
      <a href="<?= BASE_URL ?>/importaciones/ver.php?id=<?= $id ?>" class="text-sm text-gray-500 px-4 py-2">Cancelar</a>
      <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-6 py-2.5 rounded-lg hover:bg-blue-700">
        Guardar cambios
      </button>
    </div>
  </form>
</div>

<?php require_once '../includes/footer.php'; ?>
