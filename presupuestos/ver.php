<?php
require_once '../config/db.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT p.*, c.nombre AS cliente_nombre, c.empresa, c.telefono, c.email, c.direccion, c.ciudad, c.provincia
    FROM presupuestos p
    LEFT JOIN clientes c ON c.id = p.cliente_id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { flash('Presupuesto no encontrado.','error'); redirect('/presupuestos/'); }

$items = $pdo->prepare("SELECT * FROM presupuesto_items WHERE presupuesto_id = ? ORDER BY id");
$items->execute([$id]);
$items = $items->fetchAll();

$title = 'Presupuesto #' . $id;
require_once '../includes/header.php';

// Acciones POST (cambiar estado)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_estado'])) {
    verify_csrf();
    $nuevo_estado = $_POST['nuevo_estado'];
    $validos = ['borrador','enviado','aprobado','rechazado'];
    if (in_array($nuevo_estado, $validos)) {
        $pdo->prepare("UPDATE presupuestos SET estado=? WHERE id=?")->execute([$nuevo_estado, $id]);
        flash('Estado actualizado.');
        redirect('/presupuestos/ver.php?id=' . $id);
    }
}
?>

<div class="flex items-center justify-between mb-4 gap-3">
  <a href="<?= BASE_URL ?>/presupuestos/" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Volver
  </a>
  <div class="flex gap-2">
    <a href="<?= BASE_URL ?>/presupuestos/pdf.php?id=<?= $id ?>" target="_blank"
      class="bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700">
      Ver PDF
    </a>
    <a href="<?= BASE_URL ?>/presupuestos/editar.php?id=<?= $id ?>"
      class="bg-white border border-gray-300 text-gray-700 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-50">
      Editar
    </a>
  </div>
</div>

<div class="max-w-3xl space-y-4">

  <!-- Cabecera -->
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
    <div class="flex items-start justify-between gap-3">
      <div>
        <p class="text-xs text-gray-400 mb-1">Presupuesto #<?= $id ?></p>
        <h2 class="text-lg font-semibold text-gray-900"><?= esc($p['empresa'] ?: $p['cliente_nombre'] ?: 'Sin cliente') ?></h2>
        <?php if ($p['empresa'] && $p['cliente_nombre']): ?>
        <p class="text-sm text-gray-500"><?= esc($p['cliente_nombre']) ?></p>
        <?php endif; ?>
        <?php if ($p['ciudad']): ?>
        <p class="text-sm text-gray-400"><?= esc($p['ciudad']) ?><?= $p['provincia'] ? ', ' . esc($p['provincia']) : '' ?></p>
        <?php endif; ?>
      </div>
      <div class="text-right flex-shrink-0">
        <?= badge($p['estado']) ?>
        <p class="text-xs text-gray-400 mt-1">Fecha: <?= fecha($p['fecha']) ?></p>
        <p class="text-xs text-gray-400">Válido <?= (int)$p['validez_dias'] ?> días</p>
      </div>
    </div>
  </div>

  <!-- Items -->
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="text-left px-4 py-3 font-medium text-gray-500 text-xs">Descripción</th>
            <th class="text-right px-4 py-3 font-medium text-gray-500 text-xs">Cant.</th>
            <th class="text-right px-4 py-3 font-medium text-gray-500 text-xs">P. Unit.</th>
            <th class="text-right px-4 py-3 font-medium text-gray-500 text-xs">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
          <tr>
            <td class="px-4 py-2.5 text-gray-800"><?= esc($it['descripcion']) ?></td>
            <td class="px-4 py-2.5 text-right text-gray-600"><?= number_format((float)$it['cantidad'], 2, ',', '.') ?></td>
            <td class="px-4 py-2.5 text-right text-gray-600"><?= money($it['precio_unitario']) ?></td>
            <td class="px-4 py-2.5 text-right font-semibold text-gray-800"><?= money((float)$it['cantidad'] * (float)$it['precio_unitario']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="border-t border-gray-200 bg-gray-50">
          <tr>
            <td colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Total</td>
            <td class="px-4 py-3 text-right text-base font-bold text-gray-900"><?= money($p['total']) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <?php if ($p['notas']): ?>
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
    <p class="text-xs text-gray-400 mb-1">Notas</p>
    <p class="text-sm text-gray-700 whitespace-pre-wrap"><?= esc($p['notas']) ?></p>
  </div>
  <?php endif; ?>

  <!-- Cambiar estado -->
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Cambiar estado</p>
    <form method="POST" class="flex flex-wrap gap-2">
      <?= csrf_field() ?>
      <?php foreach (['borrador'=>'Borrador','enviado'=>'Enviado','aprobado'=>'Aprobado','rechazado'=>'Rechazado'] as $est => $lbl): ?>
      <button type="submit" name="nuevo_estado" value="<?= $est ?>"
        class="px-3 py-1.5 rounded-lg text-sm border transition-colors
        <?= $p['estado'] === $est
            ? 'bg-blue-600 text-white border-blue-600'
            : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-50' ?>">
        <?= $lbl ?>
      </button>
      <?php endforeach; ?>
    </form>

    <?php if ($p['estado'] === 'aprobado'): ?>
    <div class="mt-3 pt-3 border-t border-gray-100">
      <a href="<?= BASE_URL ?>/ventas/nueva.php?presupuesto_id=<?= $id ?>"
        class="inline-flex items-center gap-2 text-sm text-green-600 font-medium hover:underline">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
        Convertir en venta
      </a>
    </div>
    <?php endif; ?>
  </div>

</div>

<?php require_once '../includes/footer.php'; ?>
