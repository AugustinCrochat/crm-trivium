<?php
/**
 * tango/api.php — Helpers para la API de Tango Tiendas
 */

define('TANGO_TOKEN',    '59e1c055-c4fd-43dc-a122-346275084a41_16482');
define('TANGO_BASE_URL', 'https://tiendas.axoft.com/api/Aperture');

// ── Configuración dinámica (config/tango.json sobreescribe defaults) ──
$_tcfg_file = dirname(__DIR__) . '/config/tango.json';
$_tcfg = file_exists($_tcfg_file) ? (json_decode(file_get_contents($_tcfg_file), true) ?? []) : [];
define('TANGO_DEPOSITO',        $_tcfg['deposito']        ?? '01');
define('TANGO_VENDEDOR',        $_tcfg['vendedor']        ?? '01');
define('TANGO_CONDICION_VENTA', $_tcfg['condicion_venta'] ?? '01');
define('TANGO_LISTA_PRECIO',    $_tcfg['lista_precio']    ?? '1');
unset($_tcfg, $_tcfg_file);

// ── GET ────────────────────────────────────────────────────────
function tango_get(string $path, array $params = []): array
{
    $url = TANGO_BASE_URL . '/' . $path;
    if ($params) $url .= '?' . http_build_query($params);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['accesstoken: ' . TANGO_TOKEN, 'Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err) return ['ok' => false, 'error' => $err, 'httpCode' => 0];
    $data = json_decode($body, true) ?? [];
    $data['httpCode'] = $code;
    return $data;
}

// ── POST ───────────────────────────────────────────────────────
function tango_post(string $path, array $payload): array
{
    $url = TANGO_BASE_URL . '/' . $path;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ['accesstoken: ' . TANGO_TOKEN, 'Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err) return ['ok' => false, 'error' => $err, 'httpCode' => 0];
    $data = json_decode($body, true) ?? [];
    $data['httpCode'] = $code;
    return $data;
}

// ── Mapeo provincia → código AFIP ─────────────────────────────
function provincia_afip(string $provincia): string
{
    $map = [
        'buenos aires'   => 'B', 'caba'           => 'C', 'capital federal' => 'C',
        'catamarca'      => 'K', 'chaco'           => 'H', 'chubut'          => 'U',
        'cordoba'        => 'X', 'córdoba'         => 'X', 'corrientes'      => 'W',
        'entre rios'     => 'R', 'entre ríos'      => 'R', 'formosa'         => 'P',
        'jujuy'          => 'Y', 'la pampa'        => 'L', 'la rioja'        => 'F',
        'mendoza'        => 'M', 'misiones'        => 'N', 'neuquen'         => 'Q',
        'neuquén'        => 'Q', 'rio negro'       => 'J', 'río negro'       => 'J',
        'salta'          => 'A', 'san juan'        => 'G', 'san luis'        => 'D',
        'santa cruz'     => 'Z', 'santa fe'        => 'S', 'santiago del estero' => 'G',
        'tierra del fuego' => 'V', 'tucuman'       => 'T', 'tucumán'         => 'T',
    ];
    return $map[strtolower(trim($provincia))] ?? 'B'; // default Buenos Aires
}
