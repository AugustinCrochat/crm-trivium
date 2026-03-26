<?php
require_once '../config/db.php';
$title  = 'Nuevo viaje';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $fecha       = $_POST['fecha']       ?? date('Y-m-d');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $estado      = $_POST['estado']      ?? 'planificado';
    $notas       = trim($_POST['notas']  ?? '');

    if (!$fecha) $errors[] = 'La fecha es obligatoria.';

    if (!$errors) {
        $pdo->prepare("INSERT INTO viajes (fecha,descripcion,estado,notas) VALUES (?,?,?,?)")
            ->execute([$fecha, $descripcion, $estado, $notas]);
        $vid = $pdo->lastInsertId();
        flash('Viaje creado.');
        redirect('/viajes/ver.php?id=' . $vid);
    }
}

require_once '../includes/header.php';
?>

<div class="max-w-md">
  <a href="<?= BASE_URL ?>/viajes/" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-4">
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
      <label class="block text-sm font-medium text-gray-700 mb-1">Fecha del viaje *</label>
      <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
      <input type="text" name="descripcion" placeholder="ej: Córdoba y Rosario"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
      <select name="estado"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="planificado">Planificado</option>
        <option value="en_curso">En curso</option>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
      <textarea name="notas" rows="3"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
    </div>
    <div class="flex justify-end gap-3 pt-2">
      <a href="<?= BASE_URL ?>/viajes/" class="text-sm text-gray-500 px-4 py-2">Cancelar</a>
      <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-5 py-2 rounded-lg hover:bg-blue-700">
        Crear viaje
      </button>
    </div>
  </form>
</div>

<?php require_once '../includes/footer.php'; ?>
