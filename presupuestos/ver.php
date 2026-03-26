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

// Cambiar estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_estado'])) {
    verify_csrf();
    $nuevo_estado = $_POST['nuevo_estado'];
    if (in_array($nuevo_estado, ['borrador','enviado','aprobado','rechazado'])) {
        $pdo->prepare("UPDATE presupuestos SET estado=? WHERE id=?")->execute([$nuevo_estado, $id]);
        flash('Estado actualizado.');
        redirect('/presupuestos/ver.php?id=' . $id);
    }
}

$title = 'Presupuesto #' . $id;
require_once '../includes/header.php';

// WhatsApp
$telefono_raw = preg_replace('/\D/', '', $p['telefono'] ?? '');
$wa_number    = '';
if (strlen($telefono_raw) >= 10) {
    $wa_number = (substr($telefono_raw, 0, 2) === '54') ? $telefono_raw : '54' . $telefono_raw;
}
$vence = date('d/m/Y', strtotime($p['fecha'] . ' +' . $p['validez_dias'] . ' days'));
$cliente_display = $p['empresa'] ?: $p['cliente_nombre'] ?: 'Sin cliente';
$wa_msg = "Hola, le compartimos el Presupuesto #{$id} de Trivium Center.\n\nTotal: " . money($p['total']) . "\nVálido hasta: {$vence}\n\nConsultas: info@trivium.com.ar";

// Calcular subtotal s/IVA e IVA
$subtotal_sin_iva = 0;
$iva_total = 0;
foreach ($items as $it) {
    $base = (float)$it['cantidad'] * (float)$it['precio_unitario'];
    $subtotal_sin_iva += $base;
    $iva_total += $base * (float)($it['iva'] ?? 0) / 100;
}
$hay_iva = $iva_total > 0;

// Alerta enviado >7 días sin resolver
$dias_enviado = null;
if ($p['estado'] === 'enviado') {
    $dias_enviado = (int)((time() - strtotime($p['fecha'])) / 86400);
}
?>

<?php if ($dias_enviado !== null && $dias_enviado >= 7): ?>
<div class="mb-4 px-4 py-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-sm flex items-center gap-2">
  <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
  Enviado hace <strong><?= $dias_enviado ?> días</strong> sin respuesta. ¿Aprobado o rechazado?
</div>
<?php endif; ?>

<div class="flex items-center justify-between mb-4 gap-3 flex-wrap">
  <a href="<?= BASE_URL ?>/presupuestos/" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Volver
  </a>
  <div class="flex gap-2 flex-wrap">
    <?php if ($wa_number): ?>
    <a href="https://wa.me/<?= $wa_number ?>?text=<?= rawurlencode($wa_msg) ?>" target="_blank" rel="noopener"
      class="inline-flex items-center gap-1.5 bg-green-500 text-white text-sm font-medium px-3 py-2 rounded-lg hover:bg-green-600">
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
      WhatsApp
    </a>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/presupuestos/pdf.php?id=<?= $id ?>" target="_blank"
      class="bg-gray-800 text-white text-sm font-medium px-3 py-2 rounded-lg hover:bg-gray-900">
      PDF / Imprimir
    </a>
    <a href="<?= BASE_URL ?>/presupuestos/editar.php?id=<?= $id ?>"
      class="bg-white border border-gray-300 text-gray-700 text-sm font-medium px-3 py-2 rounded-lg hover:bg-gray-50">
      Editar
    </a>
  </div>
</div>

<div class="max-w-3xl space-y-4">

  <!-- Cabecera -->
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
    <div class="flex items-start justify-between gap-3">
      <div>
        <p class="text-xs text-gray-400 mb-1">Presupuesto #<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?></p>
        <h2 class="text-lg font-semibold text-gray-900"><?= esc($cliente_display) ?></h2>
        <?php if ($p['empresa'] && $p['cliente_nombre']): ?>
        <p class="text-sm text-gray-500"><?= esc($p['cliente_nombre']) ?></p>
        <?php endif; ?>
        <?php if ($p['email']): ?><p class="text-sm text-gray-400"><?= esc($p['email']) ?></p><?php endif; ?>
        <?php if ($p['telefono']): ?><p class="text-sm text-gray-400"><?= esc($p['telefono']) ?></p><?php endif; ?>
        <?php if ($p['ciudad']): ?>
        <p class="text-sm text-gray-400"><?= esc($p['ciudad']) ?><?= $p['provincia'] ? ', '.esc($p['provincia']) : '' ?></p>
        <?php endif; ?>
      </div>
      <div class="text-right flex-shrink-0">
        <?= badge($p['estado']) ?>
        <p class="text-xs text-gray-400 mt-1">Fecha: <?= fecha($p['fecha']) ?></p>
        <p class="text-xs text-gray-400">Válido <?= (int)$p['validez_dias'] ?> días</p>
        <p class="text-xs text-gray-400">Vence: <?= $vence ?></p>
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
            <th class="text-right px-4 py-3 font-medium text-gray-500 text-xs">P. Unit. s/IVA</th>
            <th class="text-right px-4 py-3 font-medium text-gray-500 text-xs">IVA</th>
            <th class="text-right px-4 py-3 font-medium text-gray-500 text-xs">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it):
            $base = (float)$it['cantidad'] * (float)$it['precio_unitario'];
            $iva_val = (float)($it['iva'] ?? 0);
            $subtotal = $base * (1 + $iva_val / 100);
          ?>
          <tr class="border-b border-gray-100 last:border-0">
            <td class="px-4 py-2.5 text-gray-800"><?= esc($it['descripcion']) ?></td>
            <td class="px-4 py-2.5 text-right text-gray-600"><?= number_format((float)$it['cantidad'], 2, ',', '.') ?></td>
            <td class="px-4 py-2.5 text-right text-gray-600"><?= money($it['precio_unitario']) ?></td>
            <td class="px-4 py-2.5 text-right text-gray-400"><?= $iva_val > 0 ? $iva_val.'%' : '—' ?></td>
            <td class="px-4 py-2.5 text-right font-semibold text-gray-800"><?= money($subtotal) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="border-t-2 border-gray-200 bg-gray-50">
          <?php if ($hay_iva): ?>
          <tr>
            <td colspan="4" class="px-4 py-2 text-right text-xs text-gray-500">Subtotal s/IVA</td>
            <td class="px-4 py-2 text-right text-sm text-gray-600"><?= money($subtotal_sin_iva) ?></td>
          </tr>
          <tr>
            <td colspan="4" class="px-4 py-2 text-right text-xs text-gray-500">IVA</td>
            <td class="px-4 py-2 text-right text-sm text-gray-600"><?= money($iva_total) ?></td>
          </tr>
          <?php endif; ?>
          <tr>
            <td colspan="4" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Total</td>
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

  <!-- Eliminar -->
  <div class="bg-white rounded-xl border border-red-100 shadow-sm p-4">
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Zona de peligro</p>
    <form method="POST" action="<?= BASE_URL ?>/presupuestos/eliminar.php"
      onsubmit="return confirm('¿Eliminar el presupuesto #<?= $id ?> permanentemente?')">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $id ?>">
      <button type="submit"
        class="inline-flex items-center gap-2 text-sm text-red-600 border border-red-200 px-3 py-1.5 rounded-lg hover:bg-red-50 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        Eliminar presupuesto
      </button>
    </form>
  </div>

</div>

<?php require_once '../includes/footer.php'; ?>
