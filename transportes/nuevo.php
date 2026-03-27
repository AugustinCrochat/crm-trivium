<?php
require_once '../config/db.php';
$title  = 'Nuevo transporte';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $nombre    = trim($_POST['nombre']    ?? '');
    $contacto  = trim($_POST['contacto']  ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $notas     = trim($_POST['notas']     ?? '');
    // Ciudades: cada línea = "ciudad, provincia"
    $ciudades_raw = $_POST['ciudades'] ?? '';

    if ($nombre === '') $errors[] = 'El nombre es obligatorio.';

    if (!$errors) {
        // Parsear líneas de ciudades
        $lineas = array_filter(
            array_map('trim', preg_split('/[\n]+/', $ciudades_raw)),
            fn($c) => $c !== ''
        );

        if (empty($lineas)) {
            // Si no hay ciudades, insertar una sola fila sin ciudad
            $pdo->prepare("INSERT INTO transportes (nombre,direccion,contacto,ciudad,provincia,notas,activo) VALUES (?,?,?,?,?,?,1)")
                ->execute([$nombre, $direccion, $contacto, '', '', $notas]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO transportes (nombre,direccion,contacto,ciudad,provincia,notas,activo) VALUES (?,?,?,?,?,?,1)");
            foreach ($lineas as $linea) {
                $parts = array_map('trim', explode(',', $linea, 2));
                $ciudad    = $parts[0] ?? '';
                $provincia = $parts[1] ?? '';
                $stmt->execute([$nombre, $direccion, $contacto, $ciudad, $provincia, $notas]);
            }
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
      <label class="block text-sm font-medium text-gray-700 mb-1">Contacto / URL</label>
      <input type="text" name="contacto" value="<?= esc($_POST['contacto'] ?? '') ?>" placeholder="https://..."
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
        <span class="text-gray-400 font-normal">(una por línea, formato: Ciudad, Provincia)</span>
      </label>
      <textarea name="ciudades" rows="6" placeholder="Córdoba, Córdoba&#10;Rosario, Santa Fe&#10;Mendoza, Mendoza"
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
