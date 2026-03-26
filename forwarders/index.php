<?php
require_once '../config/db.php';
$title = 'Forwarders';
require_once '../includes/header.php';

$forwarders = $pdo->query("SELECT * FROM forwarders ORDER BY nombre")->fetchAll();
?>

<div class="flex items-center justify-between mb-4 gap-3">
  <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Forwarders</h2>
  <a href="<?= BASE_URL ?>/forwarders/nuevo.php"
    class="bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700">
    + Nuevo
  </a>
</div>

<?php if (!$forwarders): ?>
<div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
  <p class="text-gray-400 text-sm">No hay forwarders cargados</p>
</div>
<?php else: ?>
<div class="space-y-2">
  <?php foreach ($forwarders as $f): ?>
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3 flex items-start justify-between gap-3">
    <div class="min-w-0">
      <div class="flex items-center gap-2">
        <p class="text-sm font-semibold text-gray-800"><?= esc($f['nombre']) ?></p>
        <?php if (!$f['activo']): ?>
        <span class="text-xs text-gray-400">(inactivo)</span>
        <?php endif; ?>
      </div>
      <?php if ($f['contacto']): ?>
      <p class="text-xs text-gray-500 mt-0.5"><?= esc($f['contacto']) ?></p>
      <?php endif; ?>
      <div class="flex gap-3 mt-0.5">
        <?php if ($f['telefono']): ?>
        <a href="tel:<?= esc($f['telefono']) ?>" class="text-xs text-gray-400 hover:underline">☎ <?= esc($f['telefono']) ?></a>
        <?php endif; ?>
        <?php if ($f['email']): ?>
        <a href="mailto:<?= esc($f['email']) ?>" class="text-xs text-gray-400 hover:underline"><?= esc($f['email']) ?></a>
        <?php endif; ?>
      </div>
    </div>
    <a href="<?= BASE_URL ?>/forwarders/editar.php?id=<?= $f['id'] ?>"
      class="flex-shrink-0 text-xs text-blue-600 hover:underline">Editar</a>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
