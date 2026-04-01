<?php
require_once __DIR__ . '/../config/db.php';

$title = 'Sistema de Préstamos';
include BASE_PATH . '/includes/header.php';
?>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col h-[calc(100vh-140px)]">
    <div class="px-4 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-gray-700">Módulo de Préstamos</h2>
        <div class="flex gap-2">
            <a href="<?= BASE_URL ?>/prestamo/app/" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                Abrir en nueva pestaña
            </a>
        </div>
    </div>
    <div class="flex-1 min-h-0 bg-gray-100">
        <iframe 
            src="<?= BASE_URL ?>/prestamo/app/" 
            class="w-full h-full border-0"
            title="Préstamos App"
        ></iframe>
    </div>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
