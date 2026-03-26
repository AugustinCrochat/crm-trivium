<?php
require_once '../config/db.php';

$id = (int)($_GET['id'] ?? 0);
$vj = $pdo->prepare('SELECT * FROM viajes WHERE id = ?');
$vj->execute([$id]);
$vj = $vj->fetch();
if (!$vj) { flash('Viaje no encontrado.','error'); redirect('/viajes/'); }

$title = 'Viaje ' . fecha($vj['fecha']);

// Envíos asignados a este viaje
$envios = $pdo->prepare("
    SELECT e.*, c.nombre AS cliente_nombre, c.ciudad, t.nombre AS transporte_nombre
    FROM envios e
    LEFT JOIN clientes c    ON c.id = e.cliente_id
    LEFT JOIN transportes t ON t.id = e.transporte_id
    WHERE e.viaje_id = ?
    ORDER BY e.created_at
");
$envios->execute([$id]);
$envios = $envios->fetchAll();

// Envíos pendientes para agregar al viaje
$envios_disponibles = $pdo->query("
    SELECT e.id, c.nombre AS cliente_nombre, c.ciudad, e.tipo
    FROM envios e
    LEFT JOIN clientes c ON c.id = e.cliente_id
    WHERE e.viaje_id IS NULL
      AND e.estado = 'pendiente'
      AND e.tipo IN ('camion_plancha_deposito','camion_plancha_directo')
    ORDER BY c.nombre
")->fetchAll();

require_once '../includes/header.php';

// Acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (isset($_POST['nuevo_estado'])) {
        $validos = ['planificado','en_curso','completado'];
        if (in_array($_POST['nuevo_estado'], $validos)) {
            $pdo->prepare("UPDATE viajes SET estado=? WHERE id=?")->execute([$_POST['nuevo_estado'], $id]);
            flash('Estado actualizado.');
            redirect('/viajes/ver.php?id=' . $id);
        }
    }

    if (isset($_POST['agregar_envios'])) {
        $ids = $_POST['envio_ids'] ?? [];
        if ($ids) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge([$id], array_map('intval', $ids));
            $pdo->prepare("UPDATE envios SET viaje_id=? WHERE id IN ($placeholders) AND viaje_id IS NULL")
                ->execute($params);
            flash('Envíos agregados al viaje.');
            redirect('/viajes/ver.php?id=' . $id);
        }
    }

    if (isset($_POST['quitar_envio'])) {
        $pdo->prepare("UPDATE envios SET viaje_id=NULL WHERE id=? AND viaje_id=?")
            ->execute([(int)$_POST['quitar_envio'], $id]);
        flash('Envío quitado del viaje.');
        redirect('/viajes/ver.php?id=' . $id);
    }
}
?>

<div class="flex items-center justify-between mb-4 gap-3">
  <a href="<?= BASE_URL ?>/viajes/" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Volver
  </a>
</div>

<div class="max-w-2xl space-y-4">

  <!-- Cabecera del viaje -->
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
    <div class="flex items-start justify-between gap-3">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <?= badge($vj['estado']) ?>
          <h2 class="text-lg font-semibold text-gray-900"><?= fecha($vj['fecha']) ?></h2>
        </div>
        <?php if ($vj['descripcion']): ?>
        <p class="text-sm text-gray-600"><?= esc($vj['descripcion']) ?></p>
        <?php endif; ?>
        <?php if ($vj['notas']): ?>
        <p class="text-xs text-gray-400 mt-1 whitespace-pre-wrap"><?= esc($vj['notas']) ?></p>
        <?php endif; ?>
      </div>
      <div class="flex-shrink-0 flex flex-col items-end gap-2">
        <!-- Foto -->
        <?php if ($vj['foto_url']): ?>
        <img src="<?= BASE_URL . '/' . esc($vj['foto_url']) ?>" alt="Foto del camión"
          class="w-24 h-24 object-cover rounded-lg border border-gray-200">
        <?php endif; ?>
        <label class="cursor-pointer text-xs text-blue-600 hover:underline">
          <?= $vj['foto_url'] ? 'Cambiar foto' : '📷 Subir foto' ?>
          <form id="form-foto" method="POST" action="<?= BASE_URL ?>/viajes/upload_foto.php" enctype="multipart/form-data" class="hidden">
            <?= csrf_field() ?>
            <input type="hidden" name="viaje_id" value="<?= $id ?>">
            <input type="file" name="foto" accept="image/*" capture="environment"
              onchange="document.getElementById('form-foto').submit()">
          </form>
        </label>
      </div>
    </div>

    <!-- Cambiar estado -->
    <div class="mt-4 pt-4 border-t border-gray-100">
      <form method="POST" class="flex flex-wrap gap-2">
        <?= csrf_field() ?>
        <?php foreach (['planificado'=>'Planificado','en_curso'=>'En curso','completado'=>'Completado'] as $est => $lbl): ?>
        <button type="submit" name="nuevo_estado" value="<?= $est ?>"
          class="px-3 py-1.5 rounded-lg text-sm border transition-colors
          <?= $vj['estado'] === $est ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-50' ?>">
          <?= $lbl ?>
        </button>
        <?php endforeach; ?>
      </form>
    </div>
  </div>

  <!-- Envíos en este viaje -->
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="px-5 py-4 border-b border-gray-100">
      <h3 class="font-semibold text-gray-800 text-sm">Envíos en este viaje (<?= count($envios) ?>)</h3>
    </div>
    <?php if (!$envios): ?>
    <p class="text-sm text-gray-400 text-center py-8">Sin envíos asignados</p>
    <?php else: ?>
    <ul class="divide-y divide-gray-50">
      <?php foreach ($envios as $e): ?>
      <li class="px-5 py-3 flex items-center justify-between gap-3">
        <div class="min-w-0">
          <p class="text-sm font-medium text-gray-800 truncate"><?= esc($e['cliente_nombre'] ?: 'Sin cliente') ?></p>
          <p class="text-xs text-gray-400">
            <?= esc($e['ciudad'] ?: '') ?>
            <?= $e['transporte_nombre'] ? ' · ' . esc($e['transporte_nombre']) : '' ?>
            · <?= tipo_envio($e['tipo']) ?>
          </p>
        </div>
        <div class="flex items-center gap-3 flex-shrink-0">
          <?= badge($e['estado']) ?>
          <form method="POST" onsubmit="return confirm('¿Quitar este envío del viaje?')">
            <?= csrf_field() ?>
            <input type="hidden" name="quitar_envio" value="<?= $e['id'] ?>">
            <button type="submit" class="text-xs text-gray-400 hover:text-red-500">Quitar</button>
          </form>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>

  <!-- Agregar envíos -->
  <?php if ($envios_disponibles && $vj['estado'] !== 'completado'): ?>
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
    <h3 class="font-semibold text-gray-800 text-sm mb-3">Agregar envíos pendientes</h3>
    <form method="POST">
      <?= csrf_field() ?>
      <div class="space-y-2 mb-3 max-h-60 overflow-y-auto">
        <?php foreach ($envios_disponibles as $e): ?>
        <label class="flex items-start gap-3 p-2.5 rounded-lg hover:bg-gray-50 cursor-pointer">
          <input type="checkbox" name="envio_ids[]" value="<?= $e['id'] ?>"
            class="mt-0.5 w-4 h-4 rounded border-gray-300 text-blue-600 flex-shrink-0">
          <div class="min-w-0">
            <p class="text-sm font-medium text-gray-800"><?= esc($e['cliente_nombre'] ?: 'Sin cliente') ?></p>
            <p class="text-xs text-gray-400"><?= esc($e['ciudad'] ?: '') ?> · <?= tipo_envio($e['tipo']) ?></p>
          </div>
        </label>
        <?php endforeach; ?>
      </div>
      <button type="submit" name="agregar_envios" value="1"
        class="w-full bg-blue-600 text-white text-sm font-medium py-2.5 rounded-lg hover:bg-blue-700">
        Agregar seleccionados
      </button>
    </form>
  </div>
  <?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>
