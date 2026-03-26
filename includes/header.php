<?php
// Detectar módulo activo por ruta del script
$_scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
$_activeModule = 'dashboard';
foreach (['clientes','catalogo','presupuestos','ventas','transportes','logistica','importaciones','tango'] as $_m) {
    $match = match($_m) {
        'logistica'     => strpos($_scriptPath, '/viajes/') !== false || strpos($_scriptPath, '/envios/') !== false,
        'importaciones' => strpos($_scriptPath, '/importaciones/') !== false || strpos($_scriptPath, '/forwarders/') !== false,
        default         => strpos($_scriptPath, "/{$_m}/") !== false,
    };
    if ($match) { $_activeModule = $_m; break; }
}

function _navLink(string $module, string $label, string $svgPath, string $active, string $url = ''): void
{
    $url   = $url ?: BASE_URL . '/' . $module . '/';
    $isOn  = $active === $module;
    $base  = 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors duration-150';
    $cls   = $isOn
        ? "$base bg-blue-600 text-white font-medium"
        : "$base text-gray-300 hover:bg-gray-700 hover:text-white";
    echo "<a href=\"{$url}\" class=\"{$cls}\" onclick=\"closeMobile()\">"
       . "<svg class=\"w-5 h-5 flex-shrink-0\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">{$svgPath}</svg>"
       . esc($label)
       . "</a>\n";
}

function _navSection(string $label): void
{
    echo "<div class=\"px-3 pt-4 pb-1 text-xs font-semibold uppercase text-gray-500 tracking-wider\">{$label}</div>\n";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($title ?? 'CRM Trivium') ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body class="bg-gray-100 text-gray-900 font-sans antialiased">

<div id="overlay" class="fixed inset-0 bg-black/50 z-20 hidden lg:hidden" onclick="closeMobile()"></div>

<div class="flex h-screen overflow-hidden">

  <!-- ── Sidebar ── -->
  <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 z-30 w-64 bg-gray-900 flex flex-col flex-shrink-0 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out">

    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-700 flex-shrink-0">
      <span class="text-lg font-bold text-white">Trivium CRM</span>
      <button onclick="closeMobile()" class="lg:hidden text-gray-400 hover:text-white p-1 rounded">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <nav class="flex-1 px-3 py-2 overflow-y-auto space-y-0.5">

      <!-- Dashboard -->
      <?php
      $isDash = $_activeModule === 'dashboard';
      $dCls = $isDash
          ? 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm bg-blue-600 text-white font-medium'
          : 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-300 hover:bg-gray-700 hover:text-white transition-colors';
      ?>
      <a href="<?= BASE_URL ?>/" class="<?= $dCls ?>" onclick="closeMobile()">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        Dashboard
      </a>

      <?php _navSection('Comercial'); ?>

      <?php _navLink('clientes', 'Clientes',
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>',
      $_activeModule); ?>

      <?php _navLink('presupuestos', 'Presupuestos',
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
      $_activeModule); ?>

      <?php _navLink('ventas', 'Ventas',
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>',
      $_activeModule); ?>

      <?php _navSection('Catálogo'); ?>

      <?php _navLink('catalogo', 'Catálogo',
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
      $_activeModule); ?>

      <?php _navSection('Logística'); ?>

      <?php _navLink('logistica', 'Logística',
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
      $_activeModule, BASE_URL . '/viajes/'); ?>

      <?php _navLink('transportes', 'Transportes',
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1"/>',
      $_activeModule); ?>

      <?php _navLink('importaciones', 'Importaciones',
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>',
      $_activeModule); ?>

      <?php _navSection('Integración'); ?>

      <?php
      $_tIsOn = $_activeModule === 'tango';
      $_tCls  = $_tIsOn
          ? 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm bg-blue-600 text-white font-medium transition-colors duration-150'
          : 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-150';
      ?>
      <a href="<?= BASE_URL ?>/tango/catalogo.php" class="<?= $_tCls ?>" onclick="closeMobile()">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        Tango Gestión
      </a>

    </nav>

    <!-- Usuario -->
    <div class="px-4 py-3 border-t border-gray-700 flex-shrink-0">
      <div class="flex items-center gap-3 mb-2">
        <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-sm font-bold text-white flex-shrink-0">
          <?= strtoupper(substr($_SESSION['user_name'] ?? '?', 0, 1)) ?>
        </div>
        <div class="min-w-0">
          <p class="text-sm font-medium text-white truncate"><?= esc($_SESSION['user_name'] ?? '') ?></p>
          <p class="text-xs text-gray-400 capitalize"><?= esc($_SESSION['user_role'] ?? '') ?></p>
        </div>
      </div>
      <a href="<?= BASE_URL ?>/logout.php" class="text-xs text-gray-400 hover:text-white transition-colors">
        Cerrar sesión
      </a>
    </div>
  </aside>

  <!-- ── Contenido principal ── -->
  <div class="flex-1 flex flex-col overflow-hidden min-w-0">

    <!-- Topbar -->
    <header class="bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-3 flex-shrink-0">
      <button onclick="openMobile()" class="lg:hidden p-1.5 rounded-lg text-gray-500 hover:bg-gray-100">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>
      <h1 class="text-base font-semibold text-gray-800 truncate flex-1"><?= esc($title ?? 'CRM Trivium') ?></h1>
      <span class="text-xs text-gray-400 hidden sm:block"><?= date('d/m/Y') ?></span>
    </header>

    <!-- Flash message -->
    <?php $flash = get_flash(); if ($flash): ?>
    <div class="mx-4 mt-3 px-4 py-2.5 rounded-lg text-sm flex items-center gap-2
      <?= $flash['type'] === 'error'
          ? 'bg-red-50 border border-red-200 text-red-700'
          : 'bg-green-50 border border-green-200 text-green-700' ?>">
      <?= esc($flash['message']) ?>
    </div>
    <?php endif; ?>

    <!-- Contenido de la página -->
    <main class="flex-1 overflow-y-auto p-4 lg:p-6">
