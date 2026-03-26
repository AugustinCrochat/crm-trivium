<?php
require_once '../config/db.php';

$id = (int)($_GET['id'] ?? 0);
$cliente = $pdo->prepare('SELECT * FROM clientes WHERE id = ?');
$cliente->execute([$id]);
$cliente = $cliente->fetch();
if (!$cliente) { flash('Cliente no encontrado.','error'); redirect('/clientes/'); }

$title  = 'Editar cliente';
$errors = [];
$data   = $cliente;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data = array_map('trim', [
        'nombre'    => $_POST['nombre']    ?? '',
        'empresa'   => $_POST['empresa']   ?? '',
        'cuit'      => $_POST['cuit']      ?? '',
        'telefono'  => $_POST['telefono']  ?? '',
        'email'     => $_POST['email']     ?? '',
        'direccion' => $_POST['direccion'] ?? '',
        'ciudad'    => $_POST['ciudad']    ?? '',
        'provincia' => $_POST['provincia'] ?? '',
        'estado'    => $_POST['estado']    ?? 'prospecto',
        'notas'     => $_POST['notas']     ?? '',
    ]);

    if ($data['nombre'] === '') $errors[] = 'El nombre es obligatorio.';

    if (!$errors) {
        $pdo->prepare("
            UPDATE clientes SET nombre=?,empresa=?,cuit=?,telefono=?,email=?,
            direccion=?,ciudad=?,provincia=?,estado=?,notas=? WHERE id=?
        ")->execute([...array_values($data), $id]);
        flash('Cliente actualizado.');
        redirect('/clientes/ver.php?id=' . $id);
    }
}

require_once '../includes/header.php';
?>

<div class="max-w-lg">
  <a href="<?= BASE_URL ?>/clientes/ver.php?id=<?= $id ?>" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
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
      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
        <input type="text" name="nombre" value="<?= esc($data['nombre']) ?>" required
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Empresa</label>
        <input type="text" name="empresa" value="<?= esc($data['empresa']) ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">CUIT</label>
        <input type="text" name="cuit" value="<?= esc($data['cuit'] ?? '') ?>" placeholder="20-12345678-5"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
        <input type="tel" name="telefono" value="<?= esc($data['telefono']) ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <input type="email" name="email" value="<?= esc($data['email']) ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
        <input type="text" name="ciudad" value="<?= esc($data['ciudad']) ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
        <input type="text" name="provincia" value="<?= esc($data['provincia']) ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
        <input type="text" name="direccion" value="<?= esc($data['direccion']) ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
        <select name="estado"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          <?php foreach (['prospecto'=>'Prospecto','activo'=>'Activo','en_envio'=>'En envío','guardado'=>'Guardado'] as $val => $lbl): ?>
          <option value="<?= $val ?>" <?= $data['estado'] === $val ? 'selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
        <textarea name="notas" rows="3"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"><?= esc($data['notas']) ?></textarea>
      </div>
    </div>

    <div class="flex justify-between items-center pt-2">
      <form method="POST" action="<?= BASE_URL ?>/clientes/eliminar.php" onsubmit="return confirm('¿Eliminar este cliente?')">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <button type="submit" class="text-sm text-red-600 hover:text-red-700">Eliminar</button>
      </form>
      <div class="flex gap-3">
        <a href="<?= BASE_URL ?>/clientes/ver.php?id=<?= $id ?>" class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2">Cancelar</a>
        <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-5 py-2 rounded-lg hover:bg-blue-700">
          Guardar cambios
        </button>
      </div>
    </div>
  </form>
</div>

<?php require_once '../includes/footer.php'; ?>
