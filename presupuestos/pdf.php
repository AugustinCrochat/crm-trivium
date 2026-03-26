<?php
/**
 * Vista de impresión / PDF del presupuesto.
 * El usuario hace Ctrl+P → Guardar como PDF desde el browser.
 * No requiere librerías externas.
 */
require_once '../config/db.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT p.*, c.nombre AS cliente_nombre, c.empresa, c.telefono, c.email,
           c.direccion, c.ciudad, c.provincia
    FROM presupuestos p
    LEFT JOIN clientes c ON c.id = p.cliente_id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) die('Presupuesto no encontrado.');

$items = $pdo->prepare('SELECT * FROM presupuesto_items WHERE presupuesto_id = ? ORDER BY id');
$items->execute([$id]);
$items = $items->fetchAll();

$vence = date('d/m/Y', strtotime($p['fecha'] . ' +' . $p['validez_dias'] . ' days'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Presupuesto #<?= $id ?> — Trivium Center</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #111; font-size: 14px; background: #fff; }
.page { max-width: 800px; margin: 0 auto; padding: 40px 32px; }

header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; }
.brand { font-size: 22px; font-weight: 700; color: #1d4ed8; }
.brand-sub { font-size: 12px; color: #6b7280; margin-top: 2px; }
.doc-title { text-align: right; }
.doc-title h1 { font-size: 20px; font-weight: 700; color: #111; }
.doc-title .num { font-size: 13px; color: #6b7280; margin-top: 2px; }

.meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px; }
.meta-label { font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #9ca3af; margin-bottom: 4px; }
.meta-value { font-size: 14px; color: #111; }
.meta-value strong { display: block; font-size: 16px; font-weight: 600; }

table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
thead th { background: #1d4ed8; color: #fff; padding: 10px 12px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
thead th:last-child, thead th:nth-last-child(2), thead th:nth-last-child(3) { text-align: right; }
tbody td { padding: 10px 12px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
tbody td:nth-child(2), tbody td:nth-child(3), tbody td:nth-child(4) { text-align: right; white-space: nowrap; }
tbody tr:last-child td { border-bottom: none; }

.total-row { display: flex; justify-content: flex-end; margin-top: 12px; }
.total-box { background: #f9fafb; border: 2px solid #e5e7eb; border-radius: 8px; padding: 14px 20px; text-align: right; }
.total-box .label { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
.total-box .amount { font-size: 24px; font-weight: 700; color: #1d4ed8; }

.notes { margin-top: 24px; background: #f9fafb; border-left: 4px solid #1d4ed8; padding: 12px 16px; font-size: 13px; color: #374151; border-radius: 0 6px 6px 0; }
.notes .label { font-size: 11px; text-transform: uppercase; color: #9ca3af; margin-bottom: 4px; }

footer { margin-top: 40px; text-align: center; font-size: 11px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 16px; }

.print-btn { position: fixed; top: 16px; right: 16px; background: #1d4ed8; color: #fff; border: none; padding: 8px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
@media print {
  .print-btn { display: none; }
  .page { padding: 20px 16px; }
}
</style>
</head>
<body>
<button class="print-btn" onclick="window.print()">Imprimir / PDF</button>

<div class="page">
  <header>
    <div>
      <div class="brand">Trivium Center</div>
      <div class="brand-sub">Distribuidora de maquinaria automotriz</div>
    </div>
    <div class="doc-title">
      <h1>Presupuesto</h1>
      <div class="num">#<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?></div>
    </div>
  </header>

  <div class="meta-grid">
    <div>
      <div class="meta-label">Cliente</div>
      <div class="meta-value">
        <strong><?= esc($p['empresa'] ?: $p['cliente_nombre'] ?: 'Sin asignar') ?></strong>
        <?php if ($p['empresa'] && $p['cliente_nombre']): ?>
        <?= esc($p['cliente_nombre']) ?>
        <?php endif; ?>
        <?php if ($p['email']): ?><br><?= esc($p['email']) ?><?php endif; ?>
        <?php if ($p['telefono']): ?><br><?= esc($p['telefono']) ?><?php endif; ?>
        <?php if ($p['ciudad']): ?><br><?= esc($p['ciudad']) ?><?= $p['provincia'] ? ', ' . esc($p['provincia']) : '' ?><?php endif; ?>
      </div>
    </div>
    <div style="text-align:right">
      <div class="meta-label">Fecha</div>
      <div class="meta-value"><?= fecha($p['fecha']) ?></div>
      <div class="meta-label" style="margin-top:12px">Validez</div>
      <div class="meta-value">Hasta el <?= $vence ?></div>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Descripción</th>
        <th>Cant.</th>
        <th>P. Unit.</th>
        <th>Subtotal</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it): ?>
      <tr>
        <td><?= esc($it['descripcion']) ?></td>
        <td><?= number_format((float)$it['cantidad'], 2, ',', '.') ?></td>
        <td><?= money($it['precio_unitario']) ?></td>
        <td><?= money((float)$it['cantidad'] * (float)$it['precio_unitario']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="total-row">
    <div class="total-box">
      <div class="label">Total</div>
      <div class="amount"><?= money($p['total']) ?></div>
    </div>
  </div>

  <?php if ($p['notas']): ?>
  <div class="notes">
    <div class="label">Observaciones</div>
    <?= nl2br(esc($p['notas'])) ?>
  </div>
  <?php endif; ?>

  <footer>
    Precios expresados en pesos argentinos. Válido hasta el <?= $vence ?>.<br>
    Trivium Center — Distribuidora de maquinaria para talleres automotrices
  </footer>
</div>
</body>
</html>
