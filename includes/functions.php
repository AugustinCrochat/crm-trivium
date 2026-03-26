<?php
/**
 * Escapa HTML para output seguro.
 */
function esc(mixed $val): string
{
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}

/**
 * Formatea un número como moneda argentina.
 */
function money(float|int|string $amount): string
{
    return '$ ' . number_format((float)$amount, 2, ',', '.');
}

/**
 * Formatea una fecha de MySQL (Y-m-d) a d/m/Y.
 */
function fecha(?string $date): string
{
    if (!$date) return '';
    return date('d/m/Y', strtotime($date));
}

/**
 * Devuelve un badge HTML coloreado según el estado.
 */
function badge(string $estado): string
{
    $colors = [
        // Clientes
        'prospecto'               => 'bg-yellow-100 text-yellow-800',
        'activo'                  => 'bg-green-100 text-green-800',
        'en_envio'                => 'bg-blue-100 text-blue-800',
        'guardado'                => 'bg-gray-100 text-gray-600',
        // Presupuestos
        'borrador'                => 'bg-gray-100 text-gray-600',
        'enviado'                 => 'bg-blue-100 text-blue-800',
        'aprobado'                => 'bg-green-100 text-green-800',
        'rechazado'               => 'bg-red-100 text-red-800',
        // Ventas
        'pendiente'               => 'bg-yellow-100 text-yellow-800',
        'confirmada'              => 'bg-blue-100 text-blue-800',
        'entregada'               => 'bg-green-100 text-green-800',
        'cancelada'               => 'bg-red-100 text-red-800',
        // Viajes
        'planificado'             => 'bg-yellow-100 text-yellow-800',
        'en_curso'                => 'bg-blue-100 text-blue-800',
        'completado'              => 'bg-green-100 text-green-800',
        // Envíos
        'en_transito'             => 'bg-blue-100 text-blue-800',
        // Importaciones
        'embarcado'               => 'bg-blue-100 text-blue-800',
        'arribado'                => 'bg-indigo-100 text-indigo-800',
        'cerrado'                 => 'bg-gray-100 text-gray-600',
    ];
    $class = $colors[$estado] ?? 'bg-gray-100 text-gray-600';
    $labels = [
        'prospecto'   => 'Prospecto',
        'activo'      => 'Activo',
        'en_envio'    => 'En envío',
        'guardado'    => 'Guardado',
        'borrador'    => 'Borrador',
        'enviado'     => 'Enviado',
        'aprobado'    => 'Aprobado',
        'rechazado'   => 'Rechazado',
        'pendiente'   => 'Pendiente',
        'confirmada'  => 'Confirmada',
        'entregada'   => 'Entregada',
        'cancelada'   => 'Cancelada',
        'planificado' => 'Planificado',
        'en_curso'    => 'En curso',
        'completado'  => 'Completado',
        'en_transito' => 'En tránsito',
        'embarcado'   => 'Embarcado',
        'arribado'    => 'Arribado',
        'cerrado'     => 'Cerrado',
    ];
    $label = $labels[$estado] ?? ucfirst(str_replace('_', ' ', $estado));
    return "<span class=\"inline-block px-2 py-0.5 text-xs font-medium rounded-full {$class}\">{$label}</span>";
}

/**
 * Genera el campo hidden con el token CSRF.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . esc($_SESSION['csrf_token']) . '">';
}

/**
 * Verifica el token CSRF. Mata la ejecución si es inválido.
 */
function verify_csrf(): void
{
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        die('Token de seguridad inválido. Volvé atrás e intentá de nuevo.');
    }
}

/**
 * Redirige a una URL (usa BASE_URL como prefijo).
 */
function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

/**
 * Guarda un mensaje flash en la sesión.
 */
function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

/**
 * Recupera y elimina el mensaje flash.
 */
function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Devuelve el tipo de envío formateado.
 */
function tipo_envio(string $tipo): string
{
    return match($tipo) {
        'expreso'                  => 'Expreso',
        'camion_plancha_deposito'  => 'Camión → Depósito',
        'camion_plancha_directo'   => 'Camión → Directo',
        default                    => $tipo,
    };
}
