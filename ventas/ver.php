<?php
require_once '../config/db.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT v.*, c.nombre AS cliente_nombre, c.empresa, c.telefono,
           c.ciudad, c.provincia, c.direccion, c.email, c.cuit
    FROM ventas v
    LEFT JOIN clientes c ON c.id = v.cliente_id
    WHERE v.id = ?
");
$stmt->execute([$id]);
$v = $stmt->fetch();
if (!$v) { flash('Venta no encontrada.','error'); redirect('/ventas/'); }

$items_stmt = $pdo->prepare('
    SELECT vi.*, p.codigo_tango
    FROM venta_items vi
    LEFT JOIN productos p ON p.id = vi.producto_id
    WHERE vi.venta_id = ? ORDER BY vi.id
');
$items_stmt->execute([$id]);
$items = $items_stmt->fetchAll();

$title = 'Venta #' . $id;
require_once '../includes/header.php';

// ── Acciones POST ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // Cambio de estado
    if (isset($_POST['nuevo_estado'])) {
        $validos = ['pendiente','confirmada','entregada','cancelada'];
        $nuevo   = $_POST['nuevo_estado'];
        if (in_array($nuevo, $validos)) {
            $pdo->prepare("UPDATE ventas SET estado=? WHERE id=?")->execute([$nuevo, $id]);
            if ($nuevo === 'confirmada' && $v['cliente_id']) {
                $pdo->prepare("UPDATE clientes SET estado='en_envio' WHERE id=? AND estado='activo'")
                    ->execute([$v['cliente_id']]);
            }
            flash('Estado actualizado.');
            redirect('/ventas/ver.php?id=' . $id);
        }
    }

    // Enviar orden a Tango / Facturar
    if (isset($_POST['enviar_tango'])) {
        require_once '../tango/api.php';

        $tango_items = [];
        foreach ($items as $it) {
            if (empty($it['codigo_tango'])) continue;
            $tango_items[] = [
                'SKUCode'     => $it['codigo_tango'],
                'Description' => $it['descripcion'],
                'Quantity'    => (float)$it['cantidad'],
                'UnitPrice'   => (float)$it['precio_unitario'],
            ];
        }

        if (empty($tango_items)) {
            flash('No hay ítems con código Tango (SKU). Asociá los productos del catálogo antes de enviar.', 'error');
            redirect('/ventas/ver.php?id=' . $id);
        }

        $tipo_comp      = $_POST['tipo_comprobante']  ?? '36';
        $cond_venta     = $_POST['condicion_venta']   ?? TANGO_CONDICION_VENTA;
        $medio_pago     = $_POST['medio_pago']         ?? '';
        $cupon          = trim($_POST['cupon']          ?? '');
        $lote           = trim($_POST['lote']           ?? '');
        $cuit_form      = trim($_POST['cuit_factura']   ?? $v['cuit'] ?? '');
        $iva_cat        = $_POST['iva_categoria']       ?? ($cuit_form ? 'RI' : 'CF');
        $razon_social   = trim($_POST['razon_social']   ?? ($v['empresa'] ?: $v['cliente_nombre'] ?: ''));

        // Pago
        $pago = ['PaymentMethodCode' => $medio_pago, 'Amount' => (float)$v['total']];
        if ($cupon) $pago['CouponNumber'] = $cupon;
        if ($lote)  $pago['LotNumber']    = $lote;

        $payload = [
            'InvoiceTypeCode' => $tipo_comp,
            'PriceListNumber' => TANGO_LISTA_PRECIO,
            'SalespersonCode' => TANGO_VENDEDOR,
            'PaymentTermCode' => $cond_venta,
            'WarehouseCode'   => TANGO_DEPOSITO,
            'Customer' => [
                'BusinessName' => $razon_social ?: 'Sin nombre',
                'TaxIdNumber'  => $cuit_form    ?: '0',
                'IVACategory'  => $iva_cat,
                'Email'        => $v['email']     ?: '',
                'Address'      => $v['direccion'] ?: '',
                'City'         => $v['ciudad']    ?: '',
                'Province'     => provincia_afip($v['provincia'] ?: ''),
            ],
            'Payments' => [$pago],
            'Items'    => $tango_items,
        ];

        $resp = tango_post('order', $payload);

        if ($resp['isOk'] ?? false) {
            $orderId = $resp['OrderId'] ?? $resp['orderId'] ?? '';
            $pdo->prepare("UPDATE ventas SET tango_order_id=?, sincronizado_tango=1 WHERE id=?")
                ->execute([$orderId ?: null, $id]);
            // Actualizar CUIT en cliente si se ingresó uno nuevo
            if ($cuit_form && !$v['cuit'] && $v['cliente_id']) {
                $pdo->prepare("UPDATE clientes SET cuit=? WHERE id=?")
                    ->execute([$cuit_form, $v['cliente_id']]);
            }
            flash('Factura enviada a Tango.' . ($orderId ? " ID: {$orderId}" : ''));
        } else {
            $err = $resp['Message'] ?? $resp['error'] ?? json_encode($resp);
            flash("Error al facturar en Tango: {$err}", 'error');
        }
        redirect('/ventas/ver.php?id=' . $id);
    }

    // Descargar factura PDF guardada
    if (isset($_POST['descargar_pdf'])) {
        verify_csrf();
        $pdf = $pdo->prepare("SELECT factura_pdf FROM ventas WHERE id=?")->execute([$id]);
        $row = $pdo->query("SELECT factura_pdf FROM ventas WHERE id={$id}")->fetch();
        if ($row && $row['factura_pdf']) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="factura-venta-' . $id . '.pdf"');
            echo $row['factura_pdf'];
            exit;
        }
        flash('PDF no disponible aún.', 'error');
        redirect('/ventas/ver.php?id=' . $id);
    }
}
?>

<div class="flex items-center justify-between mb-4 gap-3">
  <a href="<?= BASE_URL ?>/ventas/" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Volver
  </a>
</div>

<div class="max-w-3xl space-y-4">

  <!-- Cabecera -->
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
    <div class="flex items-start justify-between gap-3">
      <div>
        <p class="text-xs text-gray-400 mb-1">Venta #<?= $id ?></p>
        <h2 class="text-lg font-semibold text-gray-900"><?= esc($v['empresa'] ?: $v['cliente_nombre'] ?: 'Sin cliente') ?></h2>
        <?php if ($v['ciudad']): ?>
        <p class="text-sm text-gray-400"><?= esc($v['ciudad']) ?></p>
        <?php endif; ?>
        <p class="text-xs text-gray-400 mt-1"><?= fecha($v['fecha']) ?></p>
      </div>
      <div class="text-right flex-shrink-0">
        <?= badge($v['estado']) ?>
        <?php if ($v['sincronizado_tango']): ?>
        <p class="text-xs text-green-600 font-medium mt-1">✓ Enviado a Tango</p>
        <?php if ($v['factura_numero']): ?>
        <p class="text-xs text-green-700 font-semibold"><?= esc($v['factura_numero']) ?></p>
        <?php endif; ?>
        <?php endif; ?>
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
            <td class="px-4 py-2.5 text-gray-800">
              <?= esc($it['descripcion']) ?>
              <?php if ($it['codigo_tango']): ?>
              <span class="ml-1 text-xs text-gray-400 font-mono"><?= esc($it['codigo_tango']) ?></span>
              <?php else: ?>
              <span class="ml-1 text-xs text-amber-500">sin SKU</span>
              <?php endif; ?>
            </td>
            <td class="px-4 py-2.5 text-right text-gray-600"><?= number_format((float)$it['cantidad'], 2, ',', '.') ?></td>
            <td class="px-4 py-2.5 text-right text-gray-600"><?= money($it['precio_unitario']) ?></td>
            <td class="px-4 py-2.5 text-right font-semibold"><?= money((float)$it['cantidad'] * (float)$it['precio_unitario']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="border-t border-gray-200 bg-gray-50">
          <tr>
            <td colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Total</td>
            <td class="px-4 py-3 text-right text-base font-bold text-gray-900"><?= money($v['total']) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <?php if ($v['notas']): ?>
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
    <p class="text-xs text-gray-400 mb-1">Notas</p>
    <p class="text-sm text-gray-700 whitespace-pre-wrap"><?= esc($v['notas']) ?></p>
  </div>
  <?php endif; ?>

  <!-- Acciones -->
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 space-y-4">

    <!-- Estado -->
    <div>
      <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Estado</p>
      <form method="POST" class="flex flex-wrap gap-2">
        <?= csrf_field() ?>
        <?php foreach (['pendiente'=>'Pendiente','confirmada'=>'Confirmada','entregada'=>'Entregada','cancelada'=>'Cancelada'] as $est => $lbl): ?>
        <button type="submit" name="nuevo_estado" value="<?= $est ?>"
          class="px-3 py-1.5 rounded-lg text-sm border transition-colors
          <?= $v['estado'] === $est ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-50' ?>">
          <?= $lbl ?>
        </button>
        <?php endforeach; ?>
      </form>
    </div>

    <!-- Tango Gestión -->
    <div class="border-t border-gray-100 pt-4">
      <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Tango Gestión</p>

      <?php if ($v['sincronizado_tango']): ?>
        <div class="space-y-2">
          <div class="flex items-center gap-2 text-sm text-green-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Orden enviada a Tango
            <?php if ($v['tango_order_id']): ?>
            <span class="text-xs text-gray-400 font-mono">(<?= esc($v['tango_order_id']) ?>)</span>
            <?php endif; ?>
          </div>
          <?php if ($v['factura_numero']): ?>
          <p class="text-sm text-gray-700">Factura: <strong><?= esc($v['factura_numero']) ?></strong></p>
          <?php if ($v['factura_url']): ?>
          <a href="<?= esc($v['factura_url']) ?>" target="_blank" rel="noopener"
            class="text-sm text-blue-600 hover:underline">Ver factura online →</a>
          <?php endif; ?>
          <?php if ($v['factura_pdf']): ?>
          <form method="POST">
            <?= csrf_field() ?>
            <button type="submit" name="descargar_pdf" value="1"
              class="text-sm text-blue-600 hover:underline">Descargar factura PDF</button>
          </form>
          <?php endif; ?>
          <?php else: ?>
          <p class="text-xs text-gray-400">Factura pendiente — llegará por webhook cuando Tango la emita.</p>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <?php
        $sin_sku  = array_filter($items, fn($i) => empty($i['codigo_tango']));
        $es_ri    = !empty($v['cuit']);
        $medios   = [
            '11101'  => 'Efectivo',
            '11102'  => 'Cheque',
            '111105' => 'Transferencia / Depósito',
            '11140'  => 'Cuenta corriente',
            '111201' => 'Débito Visa',
            '111202' => 'Débito Mastercard',
            '111211' => 'Crédito Visa',
            '111212' => 'Crédito Mastercard',
            '111213' => 'American Express',
            '111214' => 'Otras tarjetas',
        ];
        $medios_tarjeta = ['111201','111202','111211','111212','111213','111214'];
        ?>

        <?php if ($sin_sku): ?>
        <p class="text-xs text-amber-600 mb-3">
          <?= count($sin_sku) ?> ítem(s) sin código Tango (SKU) — no se enviarán.
        </p>
        <?php endif; ?>

        <button onclick="document.getElementById('form-facturar').classList.toggle('hidden')"
          type="button"
          class="inline-flex items-center gap-2 text-sm font-medium bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors mb-3">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Facturar en Tango
        </button>

        <form method="POST" id="form-facturar" class="hidden border border-gray-200 rounded-xl p-4 space-y-3 bg-gray-50">
          <?= csrf_field() ?>
          <div class="grid sm:grid-cols-2 gap-3">

            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Tipo de comprobante</label>
              <select name="tipo_comprobante"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                <option value="30" <?= !$es_ri ? '' : 'selected' ?>>Factura A</option>
                <option value="36" <?= !$es_ri ? 'selected' : '' ?>>Factura B</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Condición de venta</label>
              <select name="condicion_venta"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                <?php for ($i = 1; $i <= 22; $i++): ?>
                <option value="<?= $i ?>" <?= $i == TANGO_CONDICION_VENTA ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
              </select>
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">CUIT / DNI</label>
              <input type="text" name="cuit_factura" value="<?= esc($v['cuit'] ?? '') ?>"
                placeholder="20-12345678-5"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Categoría IVA</label>
              <select name="iva_categoria"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                <option value="RI" <?= $es_ri ? 'selected' : '' ?>>Responsable Inscripto</option>
                <option value="CF" <?= !$es_ri ? 'selected' : '' ?>>Consumidor Final</option>
                <option value="MO">Monotributista</option>
                <option value="EX">Exento</option>
              </select>
            </div>

            <div class="sm:col-span-2">
              <label class="block text-xs font-medium text-gray-600 mb-1">Razón social</label>
              <input type="text" name="razon_social"
                value="<?= esc($v['empresa'] ?: $v['cliente_nombre'] ?? '') ?>"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
            </div>

            <div class="sm:col-span-2">
              <label class="block text-xs font-medium text-gray-600 mb-1">Medio de pago</label>
              <select name="medio_pago" id="medio_pago"
                onchange="toggleTarjeta(this.value)"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                <option value="">— Seleccioná —</option>
                <?php foreach ($medios as $cod => $label): ?>
                <option value="<?= $cod ?>"><?= $label ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div id="datos-tarjeta" class="sm:col-span-2 hidden grid sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">N° de cupón</label>
                <input type="text" name="cupon" placeholder="Ej: 000123"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">N° de lote</label>
                <input type="text" name="lote" placeholder="Ej: 001"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
              </div>
            </div>

          </div>

          <div class="flex justify-end pt-1">
            <button type="submit" name="enviar_tango" value="1"
              class="bg-green-600 text-white text-sm font-medium px-5 py-2 rounded-lg hover:bg-green-700 transition-colors">
              Confirmar y facturar
            </button>
          </div>
        </form>

        <script>
        const tarjetaCodes = <?= json_encode(array_values($medios_tarjeta)) ?>;
        function toggleTarjeta(val) {
            const div = document.getElementById('datos-tarjeta');
            div.classList.toggle('hidden', !tarjetaCodes.includes(val));
        }
        </script>
      <?php endif; ?>
    </div>

    <!-- Logística -->
    <div class="border-t border-gray-100 pt-4">
      <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Logística</p>
      <a href="<?= BASE_URL ?>/envios/nuevo.php?venta_id=<?= $id ?>"
        class="text-sm text-blue-600 hover:underline">
        + Crear envío para esta venta
      </a>
    </div>

  </div>

</div>

<?php require_once '../includes/footer.php'; ?>
