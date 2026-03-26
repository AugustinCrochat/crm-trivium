<?php
require_once '../config/db.php';
$title = 'Viajes — Camión Plancha';
require_once '../includes/header.php';

$mostrar = $_GET['ver'] ?? 'proximos'; // proximos | todos

$where = $mostrar === 'proximos'
    ? "WHERE v.estado != 'completado' AND v.fecha >= CURDATE() - INTERVAL 7 DAY"
    : '';

$viajes = $pdo->query("
    SELECT v.*, COUNT(e.id) AS cant_envios
    FROM viajes v
    LEFT JOIN envios e ON e.viaje_id = v.id
    $where
    GROUP BY v.id
    ORDER BY v.fecha ASC, v.created_at DESC
")->fetchAll();
?>

<div class="flex items-center justify-between mb-4 gap-3">
  <div class="flex gap-1">
    <a href="?ver=proximos"
      class="px-3 py-1.5 rounded-full text-xs font-medium
      <?= $mostrar === 'proximos' ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
      Próximos
    </a>
    <a href="?ver=todos"
      class="px-3 py-1.5 rounded-full text-xs font-medium
      <?= $mostrar === 'todos' ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
      Todos
    </a>
  </div>
  <a href="<?= BASE_URL ?>/viajes/nuevo.php"
    class="flex-shrink-0 bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700">
    + Nuevo viaje
  </a>
</div>

<?php if (!$viajes): ?>
<div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
  <p class="text-gray-400 text-sm">No hay viajes planificados</p>
</div>
<?php else: ?>
<div class="space-y-3">
  <?php foreach ($viajes as $vj): ?>
  <a href="<?= BASE_URL ?>/viajes/ver.php?id=<?= $vj['id'] ?>"
    class="block bg-white rounded-xl border border-gray-200 shadow-sm p-4 hover:border-blue-300 transition-colors">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <div class="flex items-center gap-2 mb-1">
          <?= badge($vj['estado']) ?>
          <span class="text-sm font-semibold text-gray-800"><?= fecha($vj['fecha']) ?></span>
        </div>
        <?php if ($vj['descripcion']): ?>
        <p class="text-sm text-gray-700"><?= esc($vj['descripcion']) ?></p>
        <?php endif; ?>
        <p class="text-xs text-gray-400 mt-1"><?= (int)$vj['cant_envios'] ?> envíos</p>
      </div>
      <?php if ($vj['foto_url']): ?>
      <img src="<?= BASE_URL . '/' . esc($vj['foto_url']) ?>" alt="Foto"
        class="w-16 h-16 object-cover rounded-lg flex-shrink-0">
      <?php endif; ?>
    </div>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
