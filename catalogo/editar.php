<?php
require_once '../config/db.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM productos WHERE id = ?');
$stmt->execute([$id]);
$data = $stmt->fetch();
if (!$data) { flash('Producto no encontrado.','error'); redirect('/catalogo/'); }

$title  = 'Editar producto';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data = [
        'codigo_tango' => trim($_POST['codigo_tango'] ?? ''),
        'nombre'       => trim($_POST['nombre']       ?? ''),
        'descripcion'  => trim($_POST['descripcion']  ?? ''),
        'precio'       => trim($_POST['precio']       ?? '0'),
        'stock'        => (int)($_POST['stock']       ?? 0),
        'categoria'    => trim($_POST['categoria']    ?? ''),
        'activo'       => isset($_POST['activo']) ? 1 : 0,
    ];
    if ($data['nombre'] === '') $errors[] = 'El nombre es obligatorio.';
    if (!is_numeric($data['precio'])) $errors[] = 'El precio debe ser un número.';

    if (!$errors) {
        $pdo->prepare("
            UPDATE productos SET codigo_tango=?,nombre=?,descripcion=?,precio=?,stock=?,categoria=?,activo=?
            WHERE id=?
        ")->execute([
            $data['codigo_tango'], $data['nombre'], $data['descripcion'],
            (float)$data['precio'], $data['stock'], $data['categoria'], $data['activo'], $id
        ]);
        flash('Producto actualizado.');
        redirect('/catalogo/');
    }
}

require_once '../includes/header.php';
?>

<div class="max-w-lg">
  <a href="<?= BASE_URL ?>/catalogo/" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
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
        <label class="block text-sm font-medium text-gray-700 mb-1">Código Tango</label>
        <input type="text" name="codigo_tango" value="<?= esc($data['codigo_tango']) ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
        <input type="text" name="categoria" value="<?= esc($data['categoria']) ?>"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Precio</label>
        <input type="number" name="precio" value="<?= esc($data['precio']) ?>" step="0.01" min="0"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Stock</label>
        <input type="number" name="stock" value="<?= esc($data['stock']) ?>" min="0"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
        <textarea name="descripcion" rows="3"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"><?= esc($data['descripcion']) ?></textarea>
      </div>
      <div class="sm:col-span-2 flex items-center gap-2">
        <input type="checkbox" name="activo" id="activo" value="1" <?= $data['activo'] ? 'checked' : '' ?>
          class="w-4 h-4 rounded border-gray-300 text-blue-600">
        <label for="activo" class="text-sm text-gray-700">Producto activo (visible en catálogo)</label>
      </div>
    </div>
    <div class="flex justify-end gap-3 pt-2">
      <a href="<?= BASE_URL ?>/catalogo/" class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2">Cancelar</a>
      <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-5 py-2 rounded-lg hover:bg-blue-700">
        Guardar cambios
      </button>
    </div>
  </form>
</div>

<?php require_once '../includes/footer.php'; ?>
