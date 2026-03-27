<?php
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

$subtotal_sin_iva = 0;
$iva_total = 0;
foreach ($items as $it) {
    $base = (float)$it['cantidad'] * (float)$it['precio_unitario'];
    $subtotal_sin_iva += $base;
    $iva_total += $base * (float)($it['iva'] ?? 0) / 100;
}
$hay_iva = $iva_total > 0;

$logo_b64 = 'PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjwhLS0gQ3JlYXRlZCB3aXRoIElua3NjYXBlIChodHRwOi8vd3d3Lmlua3NjYXBlLm9yZy8pIC0tPgoKPHN2ZwogICB3aWR0aD0iOTUuNjEyMTM3bW0iCiAgIGhlaWdodD0iNDEuMjIyNjE4bW0iCiAgIHZpZXdCb3g9IjAgMCA5NS42MTIxMzcgNDEuMjIyNjE4IgogICB2ZXJzaW9uPSIxLjEiCiAgIGlkPSJzdmcxNjA3NCIKICAgaW5rc2NhcGU6dmVyc2lvbj0iMS4yLjIgKGIwYTg0ODY1NDEsIDIwMjItMTItMDEpIgogICBzb2RpcG9kaTpkb2NuYW1lPSJsb2dvIHRyaXZpdW0gY2VudGVyLnN2ZyIKICAgeG1sbnM6aW5rc2NhcGU9Imh0dHA6Ly93d3cuaW5rc2NhcGUub3JnL25hbWVzcGFjZXMvaW5rc2NhcGUiCiAgIHhtbG5zOnNvZGlwb2RpPSJodHRwOi8vc29kaXBvZGkuc291cmNlZm9yZ2UubmV0L0RURC9zb2RpcG9kaS0wLmR0ZCIKICAgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIgogICB4bWxuczpzdmc9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8c29kaXBvZGk6bmFtZWR2aWV3CiAgICAgaWQ9Im5hbWVkdmlldzE2MDc2IgogICAgIHBhZ2Vjb2xvcj0iI2ZmZmZmZiIKICAgICBib3JkZXJjb2xvcj0iIzAwMDAwMCIKICAgICBib3JkZXJvcGFjaXR5PSIwLjI1IgogICAgIGlua3NjYXBlOnNob3dwYWdlc2hhZG93PSIyIgogICAgIGlua3NjYXBlOnBhZ2VvcGFjaXR5PSIwLjAiCiAgICAgaW5rc2NhcGU6cGFnZWNoZWNrZXJib2FyZD0iMCIKICAgICBpbmtzY2FwZTpkZXNrY29sb3I9IiNkMWQxZDEiCiAgICAgaW5rc2NhcGU6ZG9jdW1lbnQtdW5pdHM9Im1tIgogICAgIHNob3dncmlkPSJmYWxzZSIKICAgICBpbmtzY2FwZTp6b29tPSIyLjIwMjkyNTciCiAgICAgaW5rc2NhcGU6Y3g9IjE5Ny4wMTA3MyIKICAgICBpbmtzY2FwZTpjeT0iMTM5LjU4NzA5IgogICAgIGlua3NjYXBlOndpbmRvdy13aWR0aD0iMTg1NCIKICAgICBpbmtzY2FwZTp3aW5kb3ctaGVpZ2h0PSIxMDExIgogICAgIGlua3NjYXBlOndpbmRvdy14PSI2NiIKICAgICBpbmtzY2FwZTp3aW5kb3cteT0iMzIiCiAgICAgaW5rc2NhcGU6d2luZG93LW1heGltaXplZD0iMSIKICAgICBpbmtzY2FwZTpjdXJyZW50LWxheWVyPSJsYXllcjEiIC8+CiAgPGRlZnMKICAgICBpZD0iZGVmczE2MDcxIiAvPgogIDxnCiAgICAgaW5rc2NhcGU6bGFiZWw9IkxheWVyIDEiCiAgICAgaW5rc2NhcGU6Z3JvdXBtb2RlPSJsYXllciIKICAgICBpZD0ibGF5ZXIxIgogICAgIHRyYW5zZm9ybT0idHJhbnNsYXRlKC05OS4yODMwMDUsLTEwMi4xMzkyOCkiPgogICAgPHBhdGgKICAgICAgIGlkPSJyZWN0MjQ3NiIKICAgICAgIHN0eWxlPSJmaWxsOiMxYzFjMWM7c3Ryb2tlLXdpZHRoOjAuNjE5Nzg2O3N0cm9rZS1saW5lY2FwOnJvdW5kIgogICAgICAgZD0iTSA5OS4yODMwMDUsMTAyLjEzOTI4IEggMTk0Ljg5NTE0IFYgMTQzLjM2MTkgSCA5OS4yODMwMDU...'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Presupuesto #<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
    <style>
        :root {
            --brand-primary: #111827;
            --brand-accent: #E11D48;
            --text-main: #1F2937;
            --text-muted: #6B7280;
            --bg-light: #F9FAFB;
            --border: #E5E7EB;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Inter', sans-serif; 
            color: var(--text-main); 
            background: #E5E7EB; 
            padding: 40px 0;
            -webkit-font-smoothing: antialiased;
        }

        .output-container {
            width: 800px;
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
            position: relative;
            min-height: 1000px;
            display: flex;
            flex-direction: column;
        }

        /* Decorative strip at the top */
        .top-strip { height: 6px; background: var(--brand-accent); width: 100%; }

        /* Header section */
        .header {
            padding: 60px 60px 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .logo-box img { height: 50px; width: auto; }
        
        .doc-info { text-align: right; }
        .doc-label { 
            font-size: 11px; font-weight: 800; text-transform: uppercase; 
            letter-spacing: 0.15em; color: var(--brand-accent); margin-bottom: 4px; 
        }
        .doc-number { font-size: 32px; font-weight: 800; color: var(--brand-primary); line-height: 1; }
        .doc-date { font-size: 13px; color: var(--text-muted); margin-top: 8px; font-weight: 500; }

        /* Main Content */
        .main-content { padding: 0 60px; flex: 1; }

        .client-info-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 40px;
            margin-bottom: 50px;
            padding: 30px 0;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .info-block-label { font-size: 10px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 12px; letter-spacing: 0.05em; }
        
        .client-card strong { font-size: 20px; font-weight: 800; color: var(--brand-primary); display: block; margin-bottom: 6px; }
        .client-card p { font-size: 14px; color: #4B5563; margin-bottom: 3px; }

        .meta-card { background: var(--bg-light); padding: 20px; border-radius: 12px; }
        .meta-item { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; }
        .meta-item:last-child { margin-bottom: 0; }
        .meta-item .lbl { color: var(--text-muted); }
        .meta-item .val { font-weight: 700; color: var(--brand-primary); }

        /* Table Styling */
        table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        th { 
            text-align: left; padding: 15px 0; font-size: 11px; font-weight: 800; 
            text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid var(--brand-primary);
        }
        td { padding: 20px 0; border-bottom: 1px solid var(--border); vertical-align: top; }
        
        .item-name { font-size: 14px; font-weight: 700; color: var(--brand-primary); margin-bottom: 4px; }
        .item-sub { font-size: 11px; color: var(--text-muted); font-weight: 500; }
        
        .col-qty { width: 80px; text-align: center; }
        .col-price { width: 120px; text-align: right; }
        .col-total { width: 140px; text-align: right; }

        .qty-val { font-weight: 600; color: var(--brand-primary); }
        .price-val { font-weight: 500; color: var(--text-muted); }
        .total-val { font-weight: 700; color: var(--brand-primary); }

        /* Summary Section */
        .summary-wrapper { display: flex; justify-content: flex-end; margin-bottom: 60px; }
        .summary-box { width: 300px; }
        .summary-row { display: flex; justify-content: space-between; padding: 10px 0; font-size: 14px; color: var(--text-muted); }
        .summary-row.grand-total { 
            margin-top: 15px; padding: 20px 0; border-top: 2px solid var(--brand-primary); 
            color: var(--brand-primary); 
        }
        .grand-total .lbl { font-weight: 800; font-size: 16px; text-transform: uppercase; }
        .grand-total .amt { font-size: 28px; font-weight: 900; color: var(--brand-accent); }

        /* Notes and Terms */
        .footer-notes { margin-top: 40px; padding: 30px; background: var(--bg-light); border-radius: 16px; }
        .notes-title { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 10px; }
        .notes-content { font-size: 13px; line-height: 1.6; color: #4B5563; white-space: pre-wrap; }

        /* Footer */
        .page-footer { 
            padding: 60px; background: var(--brand-primary); color: #fff; 
            display: flex; justify-content: space-between; align-items: center;
        }
        .footer-brand { font-weight: 800; font-size: 16px; letter-spacing: -0.02em; }
        .footer-info { text-align: right; font-size: 11px; opacity: 0.6; line-height: 1.5; }

        /* Action Buttons */
        .action-bar { position: fixed; bottom: 30px; right: 30px; display: flex; gap: 15px; z-index: 1000; }
        .btn { 
            padding: 14px 28px; border-radius: 12px; font-size: 14px; font-weight: 700; 
            cursor: pointer; border: none; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex; align-items: center; gap: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .btn-print { background: #fff; color: var(--brand-primary); }
        .btn-img { background: var(--brand-accent); color: #fff; }
        .btn:hover { transform: translateY(-4px); box-shadow: 0 15px 30px rgba(0,0,0,0.3); }

        @media print {
            body { background: #fff; padding: 0; }
            .action-bar { display: none; }
            .output-container { box-shadow: none; margin: 0; width: 100%; }
        }
    </style>
</head>
<body>

    <div class="action-bar">
        <button class="btn btn-print" onclick="window.print()">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Imprimir / PDF
        </button>
        <button class="btn btn-img" onclick="exportImage()">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Descargar Imagen
        </button>
    </div>

    <div class="output-container" id="capture-area">
        <div class="top-strip"></div>
        
        <header class="header">
            <div class="logo-box">
                <img src="data:image/svg+xml;base64,<?= $logo_b64 ?>" alt="Trivium Center">
            </div>
            <div class="doc-info">
                <p class="doc-label">Presupuesto Comercial</p>
                <h1 class="doc-number">#<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?></h1>
                <p class="doc-date">Fecha de emisión: <?= fecha($p['fecha']) ?></p>
            </div>
        </header>

        <main class="main-content">
            <div class="client-info-grid">
                <div class="client-card">
                    <p class="info-block-label">Preparado para</p>
                    <strong><?= esc($p['empresa'] ?: $p['cliente_nombre'] ?: 'Consumidor Final') ?></strong>
                    <?php if($p['empresa'] && $p['cliente_nombre']): ?><p><?= esc($p['cliente_nombre']) ?></p><?php endif; ?>
                    <p><?= esc($p['direccion'] ?: '') ?> <?= esc($p['ciudad'] ?: '') ?></p>
                    <p><?= esc($p['email'] ?: '') ?></p>
                </div>
                <div class="meta-card">
                    <p class="info-block-label">Detalles de validez</p>
                    <div class="meta-item">
                        <span class="lbl">Vence el:</span>
                        <span class="val"><?= $vence ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="lbl">Moneda:</span>
                        <span class="val">Pesos Argentinos</span>
                    </div>
                    <div class="meta-item">
                        <span class="lbl">Referencia:</span>
                        <span class="val">Cotización Directa</span>
                    </div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th class="col-qty">Cant.</th>
                        <th class="col-price">Unitario</th>
                        <th class="col-total">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $it): 
                        $base = (float)$it['cantidad'] * (float)$it['precio_unitario'];
                        $iva_p = (float)($it['iva'] ?? 0);
                        $sub = $base * (1 + $iva_p / 100);
                    ?>
                    <tr>
                        <td>
                            <div class="item-name"><?= esc($it['descripcion']) ?></div>
                            <div class="item-sub">Tasa de IVA: <?= $iva_p ?>%</div>
                        </td>
                        <td class="col-qty"><span class="qty-val"><?= (int)$it['cantidad'] ?></span></td>
                        <td class="col-price"><span class="price-val"><?= money($it['precio_unitario']) ?></span></td>
                        <td class="col-total"><span class="total-val"><?= money($sub) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="summary-wrapper">
                <div class="summary-box">
                    <?php if ($hay_iva): ?>
                    <div class="summary-row">
                        <span>Subtotal (Neto)</span>
                        <span><?= money($subtotal_sin_iva) ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Impuestos (IVA)</span>
                        <span><?= money($iva_total) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-row grand-total">
                        <span class="lbl">Total Final</span>
                        <span class="amt"><?= money($p['total']) ?></span>
                    </div>
                </div>
            </div>

            <?php if ($p['notas']): ?>
            <div class="footer-notes">
                <p class="notes-title">Observaciones y Condiciones</p>
                <div class="notes-content"><?= esc($p['notas']) ?></div>
            </div>
            <?php endif; ?>
        </main>

        <footer class="page-footer">
            <div class="footer-brand">TRIVIUM CENTER</div>
            <div class="footer-info">
                Presupuesto generado mediante Trivium CRM.<br>
                Válido para la República Argentina.
            </div>
        </footer>
    </div>

    <script>
    async function exportImage() {
        const area = document.getElementById('capture-area');
        const buttons = document.querySelector('.action-bar');
        
        buttons.style.display = 'none';
        
        try {
            const canvas = await html2canvas(area, {
                scale: 3, // High definition
                useCORS: true,
                backgroundColor: '#ffffff'
            });
            
            const link = document.createElement('a');
            link.download = 'Presupuesto_<?= str_pad($id, 5, "0", STR_PAD_LEFT) ?>_Trivium.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        } catch (e) {
            console.error(e);
            alert('Error al generar la imagen.');
        } finally {
            buttons.style.display = 'flex';
        }
    }
    </script>
</body>
</html>
