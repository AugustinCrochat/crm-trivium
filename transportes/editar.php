<?php
require_once '../config/db.php';

$id = (int)($_GET['id'] ?? 0);
$t  = $pdo->prepare('SELECT * FROM transportes WHERE id = ?');
$t->execute([$id]);
$t = $t->fetch();
if (!$t) { flash('Transporte no encontrado.','error'); redirect('/transportes/'); }

// Ciudades actuales
$ciudades_actuales = $pdo->prepare('SELECT ciudad FROM transporte_ciudades WHERE transporte_id = ? ORDER BY ciudad');
$ciudades_actuales->execute([$id]);
$ciudades_actuales = $ciudades_actuales->fetchAll(PDO::FETCH_COLUMN);

$title  = 'Editar transporte';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $nombre    = trim($_POST['nombre']    ?? '');
    $telefono  = trim($_POST['telefono']  ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $notas     = trim($_POST['notas']     ?? '');
    $activo    = isset($_POST['activo']) ? 1 : 0;
    $ciudades_raw = $_POST['ciudades'] ?? '';

    if ($nombre === '') $errors[] = 'El nombre es obligatorio.';

    if (!$errors) {
        $pdo->prepare("UPDATE transportes SET nombre=?,telefono=?,direccion=?,notas=?,activo=? WHERE id=?")
            ->execute([$nombre, $telefono, $direccion, $notas, $activo, $id]);

        // Reemplazar ciudades
        $pdo->prepare("DELETE FROM transporte_ciudades WHERE transporte_id=?")->execute([$id]);
        $ciudades = array_filter(
            array_map('trim', preg_split('/[\n,]+/', $ciudades_raw)),
            fn($c) => $c !== ''
        );
        $stmtC = $pdo->prepare("INSERT INTO transporte_ciudades (transporte_id, ciudad) VALUES (?,?)");
        foreach (array_unique($ciudades) as $ciudad) {
            $stmtC->execute([$id, $ciudad]);
        }

        flash('Transporte actualizado.');
        redirect('/transportes/');
    }
}

// Datos para el form: usar POST si hay error, sino datos de DB
$form = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : [
    'nombre'    => $t['nombre'],
    'telefono'  => $t['telefono'],
    'direccion' => $t['direccion'],
    'notas'     => $t['notas'],
    'ciudades'  => implode("\n", $ciudades_actuales),
    'activo'    => $t['activo'],
];

require_once '../includes/header.php';
?>

<div class="max-w-lg">
  <a href="<?= BASE_URL ?>/transportes/" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
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
      <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
      <input type="tel" name="telefono" value="<?= esc($form['telefono']) ?>"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Dirección del depósito</label>
      <input type="text" name="direccion" value="<?= esc($form['direccion']) ?>"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">
        Ciudades destino
        <span class="text-gray-400 font-normal">(separadas por coma o en líneas separadas)</span>
      </label>
      <textarea name="ciudades" rows="6"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"><?= esc($form['ciudades']) ?></textarea>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
      <textarea name="notas" rows="2"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"><?= esc($form['notas']) ?></textarea>
    </div>
    <div class="flex items-center gap-2">
      <input type="checkbox" name="activo" id="activo" value="1" <?= $form['activo'] ? 'checked' : '' ?>
        class="w-4 h-4 rounded border-gray-300 text-blue-600">
      <label for="activo" class="text-sm text-gray-700">Transporte activo</label>
    </div>
    <div class="flex justify-end gap-3 pt-2">
      <a href="<?= BASE_URL ?>/transportes/" class="text-sm text-gray-500 px-4 py-2">Cancelar</a>
      <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-5 py-2 rounded-lg hover:bg-blue-700">
        Guardar cambios
      </button>
    </div>
  </form>
</div>

<?php require_once '../includes/footer.php'; ?>
