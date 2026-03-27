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

// WhatsApp logic
$telefono_raw = preg_replace('/\D/', '', $p['telefono'] ?? '');
$wa_number    = '';
if (strlen($telefono_raw) >= 10) {
    $wa_number = (substr($telefono_raw, 0, 2) === '54') ? $telefono_raw : '54' . $telefono_raw;
}
$vence = date('d/m/Y', strtotime($p['fecha'] . ' +' . $p['validez_dias'] . ' days'));
$cliente_display = $p['empresa'] ?: $p['cliente_nombre'] ?: 'Sin cliente';
$wa_msg = "Hola, le compartimos el Presupuesto #{$id} de Trivium Center.\n\nTotal: " . money($p['total']) . "\nVálido hasta: {$vence}\n\nConsultas: info@trivium.com.ar";

// Totals calculation
$subtotal_sin_iva = 0;
$iva_total = 0;
foreach ($items as $it) {
    $base = (float)$it['cantidad'] * (float)$it['precio_unitario'];
    $subtotal_sin_iva += $base;
    $iva_total += $base * (float)($it['iva'] ?? 0) / 100;
}
$hay_iva = $iva_total > 0;

// Status steps
$steps = [
    'borrador'  => ['label' => 'Borrador', 'color' => 'gray'],
    'enviado'   => ['label' => 'Enviado', 'color' => 'blue'],
    'aprobado'  => ['label' => 'Aprobado', 'color' => 'green'],
    'rechazado' => ['label' => 'Rechazado', 'color' => 'red'],
];
$current_step_idx = array_search($p['estado'], array_keys($steps));
?>

<div class="max-w-5xl mx-auto space-y-6">

  <!-- Header & Status Tracker -->
  <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="p-6 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <a href="<?= BASE_URL ?>/presupuestos/" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
          <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-xl font-bold text-gray-900">Presupuesto #<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?></h1>
            <?= badge($p['estado']) ?>
          </div>
          <p class="text-sm text-gray-500">Creado el <?= fecha($p['fecha']) ?> · Vence el <?= $vence ?></p>
        </div>
      </div>

      <div class="flex gap-2">
        <a href="<?= BASE_URL ?>/presupuestos/pdf.php?id=<?= $id ?>" target="_blank"
          class="inline-flex items-center gap-2 bg-gray-900 text-white text-sm font-semibold px-4 py-2.5 rounded-xl hover:bg-black transition-all">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
          PDF / Imprimir
        </a>
        <?php if ($wa_number): ?>
        <a href="https://wa.me/<?= $wa_number ?>?text=<?= rawurlencode($wa_msg) ?>" target="_blank"
          class="inline-flex items-center gap-2 bg-green-500 text-white text-sm font-semibold px-4 py-2.5 rounded-xl hover:bg-green-600 transition-all shadow-sm shadow-green-200">
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          WhatsApp
        </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Tracker -->
    <div class="px-6 py-8 bg-gray-50/50">
      <div class="relative">
        <div class="absolute inset-0 flex items-center" aria-hidden="true">
          <div class="w-full border-t-2 border-gray-200"></div>
        </div>
        <div class="relative flex justify-between">
          <?php 
          $tracker_steps = ['borrador', 'enviado', 'aprobado'];
          foreach ($tracker_steps as $idx => $s): 
            $is_done = array_search($p['estado'], $tracker_steps) >= $idx && $p['estado'] !== 'rechazado';
            $is_current = $p['estado'] === $s;
          ?>
          <div class="flex flex-col items-center">
            <span class="relative flex h-8 w-8 items-center justify-center rounded-full border-2 transition-all
              <?= $is_done ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-gray-300 text-gray-400' ?>">
              <?php if ($is_done && !$is_current): ?>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
              <?php else: ?>
                <span class="text-xs font-bold"><?= $idx + 1 ?></span>
              <?php endif; ?>
              <?php if ($is_current): ?>
                <span class="absolute -inset-1 rounded-full border-2 border-blue-600 animate-pulse"></span>
              <?php endif; ?>
            </span>
            <span class="mt-2 text-xs font-bold uppercase tracking-wider <?= $is_done ? 'text-blue-700' : 'text-gray-400' ?>"><?= $steps[$s]['label'] ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="grid lg:grid-cols-3 gap-6">
    
    <!-- Info & Items (Main Column) -->
    <div class="lg:col-span-2 space-y-6">
      
      <!-- Cliente -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Información del Cliente</h2>
        <div class="flex items-start gap-4">
          <div class="h-12 w-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="text-lg font-bold text-gray-900 truncate"><?= esc($cliente_display) ?></h3>
            <div class="mt-1 grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-sm text-gray-500">
              <?php if ($p['email']): ?>
                <span class="flex items-center gap-1.5 truncate">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                  <?= esc($p['email']) ?>
                </span>
              <?php endif; ?>
              <?php if ($p['telefono']): ?>
                <span class="flex items-center gap-1.5">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                  <?= esc($p['telefono']) ?>
                </span>
              <?php endif; ?>
              <?php if ($p['ciudad']): ?>
                <span class="flex items-center gap-1.5 sm:col-span-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  <?= esc($p['direccion'] ?: '') ?> <?= esc($p['ciudad']) ?><?= $p['provincia'] ? ', '.esc($p['provincia']) : '' ?>
                </span>
              <?php endif; ?>
            </div>
          </div>
          <a href="<?= BASE_URL ?>/clientes/ver.php?id=<?= $p['cliente_id'] ?>" class="text-blue-600 text-xs font-bold hover:underline">Ver Perfil</a>
        </div>
      </div>

      <!-- Tabla de Items -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-100">
              <th class="text-left px-6 py-4 font-bold text-gray-400 text-xs uppercase tracking-widest">Descripción</th>
              <th class="text-right px-6 py-4 font-bold text-gray-400 text-xs uppercase tracking-widest">Cant.</th>
              <th class="text-right px-6 py-4 font-bold text-gray-400 text-xs uppercase tracking-widest">Total</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($items as $it): 
              $base = (float)$it['cantidad'] * (float)$it['precio_unitario'];
              $iva_v = (float)($it['iva'] ?? 0);
              $subt = $base * (1 + $iva_v / 100);
            ?>
            <tr>
              <td class="px-6 py-4">
                <p class="font-bold text-gray-900"><?= esc($it['descripcion']) ?></p>
                <p class="text-xs text-gray-500"><?= money($it['precio_unitario']) ?> unit. <?= $iva_v > 0 ? "· {$iva_v}% IVA" : '' ?></p>
              </td>
              <td class="px-6 py-4 text-right font-medium text-gray-600">
                <?= number_format((float)$it['cantidad'], 2, ',', '.') ?>
              </td>
              <td class="px-6 py-4 text-right font-bold text-gray-900">
                <?= money($subt) ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot class="bg-gray-50/50">
            <?php if ($hay_iva): ?>
            <tr>
              <td colspan="2" class="px-6 py-2 text-right text-gray-500 font-medium">Subtotal s/IVA</td>
              <td class="px-6 py-2 text-right text-gray-700"><?= money($subtotal_sin_iva) ?></td>
            </tr>
            <tr>
              <td colspan="2" class="px-6 py-2 text-right text-gray-500 font-medium border-b border-gray-100">IVA Total</td>
              <td class="px-6 py-2 text-right text-gray-700 border-b border-gray-100"><?= money($iva_total) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
              <td colspan="2" class="px-6 py-6 text-right text-gray-900 font-bold text-lg">Total del Presupuesto</td>
              <td class="px-6 py-6 text-right text-blue-600 font-black text-2xl tracking-tight"><?= money($p['total']) ?></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <?php if ($p['notas']): ?>
      <div class="bg-amber-50 rounded-2xl border border-amber-100 p-6">
        <h2 class="text-xs font-bold text-amber-800 uppercase tracking-widest mb-2">Notas internas / Observaciones</h2>
        <p class="text-sm text-amber-900 whitespace-pre-wrap leading-relaxed"><?= esc($p['notas']) ?></p>
      </div>
      <?php endif; ?>

    </div>

    <!-- Sidebar (Controls) -->
    <div class="space-y-6">
      
      <!-- Actions -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Gestión de Estado</h2>
        <form method="POST" class="space-y-2">
          <?= csrf_field() ?>
          <?php foreach ($steps as $est => $cfg): 
            $is_active = $p['estado'] === $est;
          ?>
          <button type="submit" name="nuevo_estado" value="<?= $est ?>"
            class="w-full flex items-center justify-between px-4 py-3 rounded-xl border text-sm font-bold transition-all
            <?= $is_active 
                ? 'bg-blue-50 border-blue-200 text-blue-700 ring-2 ring-blue-600/10' 
                : 'bg-white border-gray-100 text-gray-600 hover:border-gray-300' ?>">
            <?= $cfg['label'] ?>
            <?php if ($is_active): ?>
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <?php endif; ?>
          </button>
          <?php endforeach; ?>
        </form>

        <?php if ($p['estado'] === 'aprobado'): ?>
        <div class="mt-6 pt-6 border-t border-gray-100">
          <a href="<?= BASE_URL ?>/ventas/nueva.php?presupuesto_id=<?= $id ?>"
            class="flex items-center justify-center gap-2 w-full bg-green-600 text-white font-bold py-3 rounded-xl hover:bg-green-700 transition-all shadow-lg shadow-green-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            Convertir en Venta
          </a>
        </div>
        <?php endif; ?>
      </div>

      <!-- Danger Zone -->
      <div class="bg-red-50 rounded-2xl border border-red-100 p-6">
        <h2 class="text-xs font-bold text-red-800 uppercase tracking-widest mb-4">Administración</h2>
        <div class="space-y-3">
          <a href="<?= BASE_URL ?>/presupuestos/editar.php?id=<?= $id ?>"
            class="flex items-center justify-center gap-2 w-full bg-white border border-gray-200 text-gray-700 font-bold py-2.5 rounded-xl hover:bg-gray-50 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
            Modificar Datos
          </a>
          <form method="POST" action="<?= BASE_URL ?>/presupuestos/eliminar.php"
            onsubmit="return confirm('¿Eliminar el presupuesto #<?= $id ?> permanentemente?')">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit"
              class="flex items-center justify-center gap-2 w-full text-red-600 text-sm font-bold py-2 hover:bg-red-100/50 rounded-xl transition-all">
              Eliminar Definitivamente
            </button>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
