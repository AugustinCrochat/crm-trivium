<?php
require_once '../config/db.php';

$id = (int)($_GET['id'] ?? 0);
$f  = $pdo->prepare('SELECT * FROM forwarders WHERE id = ?');
$f->execute([$id]);
$f = $f->fetch();
if (!$f) { flash('Forwarder no encontrado.','error'); redirect('/forwarders/'); }

$title  = 'Editar forwarder';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $nombre   = trim($_POST['nombre']   ?? '');
    $contacto = trim($_POST['contacto'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $notas    = trim($_POST['notas']    ?? '');
    $activo   = isset($_POST['activo']) ? 1 : 0;

    if ($nombre === '') $errors[] = 'El nombre es obligatorio.';

    if (!$errors) {
        $pdo->prepare("UPDATE forwarders SET nombre=?,contacto=?,telefono=?,email=?,notas=?,activo=? WHERE id=?")
            ->execute([$nombre, $contacto, $telefono, $email, $notas, $activo, $id]);
        flash('Forwarder actualizado.');
        redirect('/forwarders/');
    }
}

$form = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : [
    'nombre'   => $f['nombre'],
    'contacto' => $f['contacto'],
    'telefono' => $f['telefono'],
    'email'    => $f['email'],
    'notas'    => $f['notas'],
    'activo'   => $f['activo'],
];

require_once '../includes/header.php';
?>

<div class="max-w-md">
  <a href="<?= BASE_URL ?>/forwarders/" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
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
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
      <input type="text" name="nombre" value="<?= esc($form['nombre']) ?>" required
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Contacto</label>
      <input type="text" name="contacto" value="<?= esc($form['contacto']) ?>"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
        <input type="tel" name="telefono" value="<?= esc($form['telefono']) ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <input type="email" name="email" value="<?= esc($form['email']) ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
      <textarea name="notas" rows="2"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"><?= esc($form['notas']) ?></textarea>
    </div>
    <div class="flex items-center gap-2">
      <input type="checkbox" name="activo" id="activo" value="1" <?= $form['activo'] ? 'checked' : '' ?>
        class="w-4 h-4 rounded border-gray-300 text-blue-600">
      <label for="activo" class="text-sm text-gray-700">Activo</label>
    </div>
    <div class="flex justify-end gap-3 pt-2">
      <a href="<?= BASE_URL ?>/forwarders/" class="text-sm text-gray-500 px-4 py-2">Cancelar</a>
      <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-5 py-2 rounded-lg hover:bg-blue-700">
        Guardar cambios
      </button>
    </div>
  </form>
</div>

<?php require_once '../includes/footer.php'; ?>
