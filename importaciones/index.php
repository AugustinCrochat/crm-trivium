<?php
require_once '../config/db.php';
$title = 'Importaciones';
require_once '../includes/header.php';

$estado       = $_GET['estado']       ?? 'todos';
$q            = trim($_GET['q']       ?? '');
$familia      = trim($_GET['familia'] ?? '');
$forwarder_id = (int)($_GET['forwarder_id'] ?? 0);

$where  = [];
$params = [];

if ($estado !== 'todos') {
    $where[]  = 'i.estado = ?';
    $params[] = $estado;
}
if ($q !== '') {
    $like     = "%{$q}%";
    $where[]  = '(i.proveedor LIKE ? OR i.numero_proforma LIKE ? OR i.numero_bl LIKE ? OR i.familia_productos LIKE ? OR i.observaciones LIKE ?)';
    $params   = array_merge($params, [$like, $like, $like, $like, $like]);
}
if ($familia !== '') {
    $where[]  = 'i.familia_productos = ?';
    $params[] = $familia;
}
if ($forwarder_id > 0) {
    $where[]  = 'i.forwarder_id = ?';
    $params[] = $forwarder_id;
}

$sql = "SELECT i.*, f.nombre AS forwarder_nombre,
          (SELECT COUNT(*) FROM importacion_documentos WHERE importacion_id = i.id) AS doc_count,
          (SELECT url  FROM importacion_documentos WHERE importacion_id = i.id AND tipo='link' ORDER BY id ASC LIMIT 1) AS primer_link
        FROM importaciones i
        LEFT JOIN forwarders f ON f.id = i.forwarder_id";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= " ORDER BY FIELD(i.estado,'embarcado','pendiente','arribado','cerrado'), ISNULL(i.eta) ASC, i.eta ASC, i.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$importaciones = $stmt->fetchAll();

$familias   = $pdo->query("SELECT DISTINCT familia_productos FROM importaciones WHERE familia_productos IS NOT NULL AND familia_productos != '' ORDER BY familia_productos")->fetchAll(PDO::FETCH_COLUMN);
$forwarders = $pdo->query("SELECT id, nombre FROM forwarders WHERE activo=1 ORDER BY nombre")->fetchAll();

$labels_estado = ['todos'=>'Todos','pendiente'=>'Pendiente','embarcado'=>'Embarcado','arribado'=>'Arribado','cerrado'=>'Cerrado'];

$estado_clases = [
    'pendiente' => 'border-yellow-400 text-yellow-700 bg-yellow-50',
    'embarcado' => 'border-blue-400 text-blue-700 bg-blue-50',
    'arribado'  => 'border-indigo-400 text-indigo-700 bg-indigo-50',
    'cerrado'   => 'border-gray-400 text-gray-500 bg-gray-50',
];

function iUrl(array $override): string {
    $p = array_filter(array_merge([
        'estado'       => $_GET['estado']       ?? 'todos',
        'q'            => $_GET['q']            ?? '',
        'familia'      => $_GET['familia']      ?? '',
        'forwarder_id' => $_GET['forwarder_id'] ?? '',
    ], $override), fn($v) => $v !== '' && $v !== 'todos' && $v !== '0');
    return '?' . http_build_query($p);
}

$hay_filtros = $familia !== '' || $forwarder_id > 0 || $q !== '';
?>

<div class="flex items-center justify-between mb-3 gap-2">
  <form method="GET" class="flex gap-2 flex-1 flex-wrap" id="form-filtros">
    <input type="hidden" name="estado" value="<?= esc($estado) ?>">
    <input type="search" name="q" value="<?= esc($q) ?>" placeholder="Proveedor, proforma, B/L…"
      class="flex-1 min-w-0 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    <select name="familia" onchange="document.getElementById('form-filtros').submit()"
      class="border border-gray-300 rounded-lg px-2 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
      <option value="">Familia…</option>
      <?php foreach ($familias as $f): ?>
      <option value="<?= esc($f) ?>" <?= $familia === $f ? 'selected' : '' ?>><?= esc($f) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if ($forwarders): ?>
    <select name="forwarder_id" onchange="document.getElementById('form-filtros').submit()"
      class="border border-gray-300 rounded-lg px-2 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
      <option value="">Forwarder…</option>
      <?php foreach ($forwarders as $fw): ?>
      <option value="<?= $fw['id'] ?>" <?= $forwarder_id == $fw['id'] ? 'selected' : '' ?>><?= esc($fw['nombre']) ?></option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>
    <?php if ($hay_filtros): ?>
    <a href="?estado=<?= esc($estado) ?>" class="flex items-center px-2 text-gray-400 hover:text-gray-600 text-sm" title="Limpiar filtros">✕</a>
    <?php endif; ?>
  </form>
  <a href="<?= BASE_URL ?>/importaciones/nuevo.php"
    class="flex-shrink-0 bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700">
    + Nueva
  </a>
</div>

<!-- Tabs de estado -->
<div class="flex gap-1 mb-4 overflow-x-auto pb-1">
  <?php foreach ($labels_estado as $key => $lbl): ?>
  <a href="<?= iUrl(['estado' => $key]) ?>"
    class="flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium transition-colors
    <?= $estado === $key ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
    <?= $lbl ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if (!$importaciones): ?>
<div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
  <p class="text-gray-400 text-sm">No hay importaciones<?= $hay_filtros ? ' con esos filtros' : '' ?></p>
</div>
<?php else: ?>
<div class="space-y-2">
  <?php foreach ($importaciones as $imp):
    $cls = $estado_clases[$imp['estado']] ?? 'border-gray-300 text-gray-600 bg-white';
  ?>
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3 hover:border-blue-300 transition-colors cursor-pointer"
    onclick="if(!event.target.closest('a,button,select,form')){location.href='<?= BASE_URL ?>/importaciones/ver.php?id=<?= $imp['id'] ?>'}">

    <div class="flex items-start gap-3">
      <div class="min-w-0 flex-1">

        <!-- Fila 1: estado select · proveedor · familia · país -->
        <div class="flex items-center gap-2 flex-wrap mb-1">
          <form method="POST" action="<?= BASE_URL ?>/importaciones/cambiar_estado.php" class="flex-shrink-0">
            <?= csrf_field() ?>
            <input type="hidden" name="importacion_id" value="<?= $imp['id'] ?>">
            <select name="nuevo_estado" onchange="this.form.submit()"
              class="text-xs border rounded-full px-2 py-0.5 font-medium cursor-pointer focus:outline-none focus:ring-1 focus:ring-blue-400 <?= $cls ?>">
              <?php foreach (['pendiente'=>'Pendiente','embarcado'=>'Embarcado','arribado'=>'Arribado','cerrado'=>'Cerrado'] as $v => $l): ?>
              <option value="<?= $v ?>" <?= $imp['estado'] === $v ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </form>
          <span class="text-sm font-semibold text-gray-800"><?= esc($imp['proveedor'] ?: '—') ?></span>
          <?php if ($imp['familia_productos']): ?>
          <span class="text-xs text-gray-400"><?= esc($imp['familia_productos']) ?></span>
          <?php endif; ?>
          <?php if ($imp['origen']): ?>
          <span class="text-xs font-medium text-gray-500 ml-auto"><?= esc($imp['origen']) ?></span>
          <?php endif; ?>
        </div>

        <!-- Fila 2: proforma (con botón copiar) · B/L -->
        <?php if ($imp['numero_proforma'] || $imp['numero_bl']): ?>
        <p class="text-xs text-gray-500 mb-1">
          <?php if ($imp['numero_proforma']): ?>
          Proforma: <span id="pf-<?= $imp['id'] ?>"><?= esc($imp['numero_proforma']) ?></span>
          <button type="button"
            onclick="copiarTexto('<?= esc(addslashes($imp['numero_proforma'])) ?>', this)"
            class="ml-0.5 text-gray-300 hover:text-blue-500 transition-colors align-middle"
            title="Copiar número de proforma">
            <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
          </button>
          <?php endif; ?>
          <?php if ($imp['numero_bl']): ?>
          <?= $imp['numero_proforma'] ? ' · ' : '' ?>B/L: <?= esc($imp['numero_bl']) ?>
          <button type="button"
            onclick="copiarTexto('<?= esc(addslashes($imp['numero_bl'])) ?>', this)"
            class="ml-0.5 text-gray-300 hover:text-blue-500 transition-colors align-middle"
            title="Copiar B/L">
            <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
          </button>
          <?php endif; ?>
        </p>
        <?php endif; ?>

        <!-- Fila 3: ETD · ETA · FOB · forwarder · barco · doc link -->
        <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-gray-400">
          <?php if ($imp['etd']): ?><span>ETD <?= fecha($imp['etd']) ?></span><?php endif; ?>
          <?php if ($imp['eta']): ?><span>ETA <?= fecha($imp['eta']) ?></span><?php endif; ?>
          <?php if ($imp['monto_fob']): ?><span>FOB USD <?= number_format((float)$imp['monto_fob'], 2, ',', '.') ?></span><?php endif; ?>
          <?php if ($imp['nombre_barco']): ?><span><?= esc($imp['nombre_barco']) ?></span><?php endif; ?>
          <?php if ($imp['forwarder_nombre']): ?><span><?= esc($imp['forwarder_nombre']) ?></span><?php endif; ?>
          <?php if ($imp['primer_link']): ?>
          <a href="<?= esc($imp['primer_link']) ?>" target="_blank" rel="noopener"
            class="flex items-center gap-0.5 text-blue-400 hover:text-blue-600 font-medium">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
            <?= $imp['doc_count'] > 1 ? "Docs ({$imp['doc_count']})" : 'Doc' ?>
          </a>
          <?php elseif ($imp['doc_count'] > 0): ?>
          <a href="<?= BASE_URL ?>/importaciones/ver.php?id=<?= $imp['id'] ?>#documentos"
            class="flex items-center gap-0.5 text-gray-400 hover:text-gray-600">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
            <?= $imp['doc_count'] ?> archivo<?= $imp['doc_count'] > 1 ? 's' : '' ?>
          </a>
          <?php endif; ?>
        </div>

        <!-- Fila 4: observaciones -->
        <?php if ($imp['observaciones']): ?>
        <p class="text-xs text-gray-400 mt-1 truncate italic"><?= esc($imp['observaciones']) ?></p>
        <?php endif; ?>

      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function copiarTexto(texto, btn) {
  navigator.clipboard.writeText(texto).then(() => {
    const orig = btn.innerHTML;
    btn.innerHTML = '<svg class="w-3 h-3 inline text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
    setTimeout(() => { btn.innerHTML = orig; }, 1500);
  });
}
</script>

<?php require_once '../includes/footer.php'; ?>
