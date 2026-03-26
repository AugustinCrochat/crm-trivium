<?php
require_once '../config/db.php';
$title  = 'Nuevo transporte';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $nombre    = trim($_POST['nombre']    ?? '');
    $telefono  = trim($_POST['telefono']  ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $notas     = trim($_POST['notas']     ?? '');
    // Ciudades: texto separado por comas o saltos de línea
    $ciudades_raw = $_POST['ciudades'] ?? '';

    if ($nombre === '') $errors[] = 'El nombre es obligatorio.';

    if (!$errors) {
        $pdo->prepare("INSERT INTO transportes (nombre,telefono,direccion,notas) VALUES (?,?,?,?)")
            ->execute([$nombre, $telefono, $direccion, $notas]);
        $tid = $pdo->lastInsertId();

        // Procesar ciudades
        $ciudades = array_filter(
            array_map('trim', preg_split('/[\n,]+/', $ciudades_raw)),
            fn($c) => $c !== ''
        );
        $stmtC = $pdo->prepare("INSERT INTO transporte_ciudades (transporte_id, ciudad) VALUES (?,?)");
        foreach (array_unique($ciudades) as $ciudad) {
            $stmtC->execute([$tid, $ciudad]);
        }

        flash('Transporte creado.');
        redirect('/transportes/');
    }
}

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
      <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del transporte *</label>
      <input type="text" name="nombre" value="<?= esc($_POST['nombre'] ?? '') ?>" required
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
      <input type="tel" name="telefono" value="<?= esc($_POST['telefono'] ?? '') ?>"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Dirección del depósito</label>
      <input type="text" name="direccion" value="<?= esc($_POST['direccion'] ?? '') ?>"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">
        Ciudades destino
        <span class="text-gray-400 font-normal">(separadas por coma o en líneas separadas)</span>
      </label>
      <textarea name="ciudades" rows="5" placeholder="Córdoba&#10;Santa Fe&#10;Rosario&#10;Tucumán"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"><?= esc($_POST['ciudades'] ?? '') ?></textarea>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
      <textarea name="notas" rows="2"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"><?= esc($_POST['notas'] ?? '') ?></textarea>
    </div>
    <div class="flex justify-end gap-3 pt-2">
      <a href="<?= BASE_URL ?>/transportes/" class="text-sm text-gray-500 px-4 py-2">Cancelar</a>
      <button type="submit" class="bg-blue-600 text-white text-sm font-medium px-5 py-2 rounded-lg hover:bg-blue-700">
        Guardar transporte
      </button>
    </div>
  </form>
</div>

<?php require_once '../includes/footer.php'; ?>
