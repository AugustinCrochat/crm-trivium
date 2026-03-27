<?php
require_once '../config/db.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT e.*,
           c.nombre AS cliente_nombre, c.empresa, c.ciudad, c.provincia, c.telefono,
           t.nombre AS transporte_nombre, t.contacto AS transporte_contacto, t.direccion AS transporte_dir,
           vj.fecha AS viaje_fecha, vj.descripcion AS viaje_desc,
           v.id AS venta_id_ref
    FROM envios e
    LEFT JOIN clientes c    ON c.id  = e.cliente_id
    LEFT JOIN transportes t ON t.id  = e.transporte_id
    LEFT JOIN viajes vj     ON vj.id = e.viaje_id
    LEFT JOIN ventas v      ON v.id  = e.venta_id
    WHERE e.id = ?
");
$stmt->execute([$id]);
$e = $stmt->fetch();
if (!$e) { flash('Envío no encontrado.','error'); redirect('/viajes/'); }

// Acciones POST — antes del header para que redirect() funcione
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (isset($_POST['nuevo_estado'])) {
        $validos = ['pendiente','en_transito','entregado'];
        $nuevo   = $_POST['nuevo_estado'];
        if (in_array($nuevo, $validos)) {
            $pdo->prepare("UPDATE envios SET estado=? WHERE id=?")->execute([$nuevo, $id]);

            if ($nuevo === 'entregado') {
                if ($e['venta_id_ref']) {
                    $pdo->prepare("UPDATE ventas SET entregado=1 WHERE id=?")
                        ->execute([$e['venta_id_ref']]);
                }
                if ($e['cliente_id']) {
                    $pdo->prepare("UPDATE clientes SET estado='guardado' WHERE id=? AND estado='en_envio'")
                        ->execute([$e['cliente_id']]);
                }
            }
            flash('Estado actualizado.');
            redirect('/envios/ver.php?id=' . $id);
        }
    }

    if (isset($_POST['eliminar_envio'])) {
        $pdo->prepare("DELETE FROM envios WHERE id=?")->execute([$id]);
        flash('Envío eliminado.');
        redirect('/viajes/');
    }
}

$title = 'Envío #' . $id;
require_once '../includes/header.php';
?>

<div class="flex items-center justify-between mb-4 gap-3">
  <a href="<?= BASE_URL ?>/viajes/" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Volver
  </a>
</div>

<div class="max-w-2xl space-y-4">

  <!-- Cabecera -->
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
    <div class="flex items-start justify-between gap-3">
      <div>
        <p class="text-xs text-gray-400 mb-1">Envío #<?= $id ?> · <?= tipo_envio($e['tipo']) ?></p>
        <h2 class="text-lg font-semibold text-gray-900"><?= esc($e['empresa'] ?: $e['cliente_nombre'] ?: 'Sin cliente') ?></h2>
        <?php if ($e['ciudad']): ?>
        <p class="text-sm text-gray-500"><?= esc($e['ciudad']) ?><?= $e['provincia'] ? ', ' . esc($e['provincia']) : '' ?></p>
        <?php endif; ?>
        <?php if ($e['remito']): ?>
        <p class="text-xs text-gray-400 mt-1">Remito: <strong><?= esc($e['remito']) ?></strong></p>
        <?php endif; ?>
        <?php if ($e['fecha_envio']): ?>
        <p class="text-xs text-gray-400">Fecha: <?= fecha($e['fecha_envio']) ?></p>
        <?php endif; ?>
      </div>
      <div class="flex-shrink-0 text-right">
        <?= badge($e['estado']) ?>
      </div>
    </div>
  </div>

  <!-- Transporte -->
  <?php if ($e['transporte_nombre']): ?>
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
    <p class="text-xs text-gray-400 mb-2 font-medium uppercase tracking-wide">Transporte</p>
    <p class="font-semibold text-gray-800"><?= esc($e['transporte_nombre']) ?></p>
    <?php if ($e['transporte_contacto']): ?>
    <p class="text-sm text-gray-500 mt-0.5">
      <?php if (str_starts_with($e['transporte_contacto'], 'http')): ?>
      <a href="<?= esc($e['transporte_contacto']) ?>" target="_blank" class="text-blue-600 hover:underline">🔗 Contacto / Cotizador</a>
      <?php else: ?>
      <a href="tel:<?= esc($e['transporte_contacto']) ?>" class="hover:underline">☎ <?= esc($e['transporte_contacto']) ?></a>
      <?php endif; ?>
    </p>
    <?php endif; ?>
    <?php if ($e['transporte_dir']): ?>
    <p class="text-xs text-gray-400 mt-0.5"><?= esc($e['transporte_dir']) ?></p>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Viaje -->
  <?php if ($e['viaje_fecha']): ?>
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
    <p class="text-xs text-gray-400 mb-2 font-medium uppercase tracking-wide">Viaje camión plancha</p>
    <a href="<?= BASE_URL ?>/viajes/ver.php?id=<?= $e['viaje_id'] ?>" class="font-semibold text-blue-600 hover:underline">
      <?= fecha($e['viaje_fecha']) ?><?= $e['viaje_desc'] ? ' — ' . esc($e['viaje_desc']) : '' ?>
    </a>
  </div>
  <?php endif; ?>

  <!-- Venta relacionada -->
  <?php if ($e['venta_id_ref']): ?>
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
    <p class="text-xs text-gray-400 mb-1">Venta relacionada</p>
    <a href="<?= BASE_URL ?>/ventas/ver.php?id=<?= $e['venta_id_ref'] ?>" class="text-sm text-blue-600 hover:underline">
      Venta #<?= $e['venta_id_ref'] ?>
    </a>
  </div>
  <?php endif; ?>

  <?php if ($e['notas']): ?>
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
    <p class="text-xs text-gray-400 mb-1">Notas</p>
    <p class="text-sm text-gray-700 whitespace-pre-wrap"><?= esc($e['notas']) ?></p>
  </div>
  <?php endif; ?>

  <!-- Cambiar estado -->
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Estado del envío</p>
    <div class="flex items-center justify-between gap-3 flex-wrap">
      <form method="POST" class="flex flex-wrap gap-2">
        <?= csrf_field() ?>
        <?php foreach (['pendiente'=>'Pendiente','en_transito'=>'En tránsito','entregado'=>'Entregado'] as $est => $lbl): ?>
        <button type="submit" name="nuevo_estado" value="<?= $est ?>"
          class="px-3 py-1.5 rounded-lg text-sm border transition-colors
          <?= $e['estado'] === $est ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-50' ?>">
          <?= $lbl ?>
        </button>
        <?php endforeach; ?>
      </form>
      <form method="POST" onsubmit="return confirm('¿Eliminar este envío? Esta acción no se puede deshacer.')">
        <?= csrf_field() ?>
        <button type="submit" name="eliminar_envio" value="1"
          class="px-3 py-1.5 rounded-lg text-sm border border-red-200 text-red-500 hover:bg-red-50 transition-colors">
          Eliminar envío
        </button>
      </form>
    </div>
  </div>

</div>

<?php require_once '../includes/footer.php'; ?>
