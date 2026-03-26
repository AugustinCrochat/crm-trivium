<?php
/**
 * tango/webhook.php — Recibe webhooks de Tango Tiendas.
 * Configurar esta URL en el portal Tango Tiendas.
 *
 * Eventos esperados:
 *   StockProductUpdate  → actualiza stock en productos
 *   PriceProductUpdate  → actualiza precio en productos
 *   OrderBilled         → guarda número de factura en ventas
 *   InvoiceFile         → guarda PDF de factura en ventas
 */

// No requiere sesión (llamada externa de Tango)
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/tango_webhook_bootstrap.php';

// Leer y validar payload
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

$event = $data['Event'] ?? $data['event'] ?? '';

// Log básico (opcional, útil para debugging)
// file_put_contents(BASE_PATH . '/logs/tango_webhook.log', date('c') . " $event\n" . $raw . "\n\n", FILE_APPEND);

switch ($event) {

    // ── Stock ────────────────────────────────────────────────────
    case 'StockProductUpdate':
        $items = $data['Data'] ?? [$data];
        foreach ($items as $s) {
            $sku     = $s['SKUCode'] ?? '';
            $balance = isset($s['Quantity']) ? (float)$s['Quantity'] : (isset($s['Balance']) ? (float)$s['Balance'] : null);
            if (!$sku || $balance === null) continue;
            $pdo->prepare("UPDATE productos SET stock=? WHERE codigo_tango=?")
                ->execute([(int)$balance, $sku]);
        }
        break;

    // ── Precios ──────────────────────────────────────────────────
    case 'PriceProductUpdate':
        $lista = TANGO_LISTA_PRECIO;
        $items = $data['Data'] ?? [$data];
        foreach ($items as $p) {
            $sku   = $p['SKUCode'] ?? '';
            $price = isset($p['Price']) ? (float)$p['Price'] : null;
            $list  = (string)($p['PriceListNumber'] ?? '');
            if (!$sku || $price === null) continue;
            if ($list && $list !== (string)$lista) continue; // solo nuestra lista
            $pdo->prepare("UPDATE productos SET precio=? WHERE codigo_tango=?")
                ->execute([$price, $sku]);
        }
        break;

    // ── Factura emitida ──────────────────────────────────────────
    case 'OrderBilled':
        $orderId  = $data['OrderId']       ?? $data['orderId']       ?? '';
        $factNum  = $data['InvoiceNumber'] ?? $data['invoiceNumber'] ?? '';
        $factUrl  = $data['InvoiceUrl']    ?? $data['invoiceUrl']    ?? '';
        if ($orderId) {
            $pdo->prepare("
                UPDATE ventas
                SET factura_numero=?, factura_url=?, sincronizado_tango=1
                WHERE tango_order_id=?
            ")->execute([$factNum ?: null, $factUrl ?: null, $orderId]);
        }
        break;

    // ── PDF de factura ───────────────────────────────────────────
    case 'InvoiceFile':
        $orderId = $data['OrderId'] ?? $data['orderId'] ?? '';
        $b64     = $data['File']    ?? $data['file']    ?? '';
        if ($orderId && $b64) {
            $pdf = base64_decode($b64);
            if ($pdf !== false) {
                $pdo->prepare("UPDATE ventas SET factura_pdf=? WHERE tango_order_id=?")
                    ->execute([$pdf, $orderId]);
            }
        }
        break;

    default:
        // Evento desconocido — responder OK igual para no generar reintentos
        break;
}

http_response_code(200);
echo json_encode(['ok' => true]);
