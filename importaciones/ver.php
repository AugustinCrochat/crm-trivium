<?php
require_once '../config/db.php';

$id  = (int)($_GET['id'] ?? 0);
$imp = $pdo->prepare("
    SELECT i.*, f.nombre AS forwarder_nombre
    FROM importaciones i
    LEFT JOIN forwarders f ON f.id = i.forwarder_id
    WHERE i.id = ?
");
$imp->execute([$id]);
$imp = $imp->fetch();
if (!$imp) { flash('Importación no encontrada.','error'); redirect('/importaciones/'); }

$docs = $pdo->prepare("SELECT * FROM importacion_documentos WHERE importacion_id = ? ORDER BY created_at DESC");
$docs->execute([$id]);
$docs = $docs->fetchAll();

$title = 'Importación #' . $id;
require_once '../includes/header.php';

// Acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (isset($_POST['nuevo_estado'])) {
        $validos = ['pendiente','embarcado','arribado','cerrado'];
        if (in_array($_POST['nuevo_estado'], $validos)) {
            $pdo->prepare("UPDATE importaciones SET estado=? WHERE id=?")->execute([$_POST['nuevo_estado'], $id]);
            flash('Estado actualizado.');
        }
        redirect('/importaciones/ver.php?id=' . $id);
    }

    // Agregar link externo
    if (isset($_POST['agregar_link'])) {
        $nombre_link = trim($_POST['nombre_link'] ?? '');
        $url_link    = trim($_POST['url_link']    ?? '');
        if ($nombre_link && $url_link) {
            $pdo->prepare("INSERT INTO importacion_documentos (importacion_id,tipo,nombre,url) VALUES (?,?,?,?)")
                ->execute([$id, 'link', $nombre_link, $url_link]);
            flash('Link agregado.');
        }
        redirect('/importaciones/ver.php?id=' . $id);
    }
}
?>

<div class="flex items-center justify-between mb-4 gap-3">
  <a href="<?= BASE_URL ?>/importaciones/" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Volver
  </a>
  <a href="<?= BASE_URL ?>/importaciones/editar.php?id=<?= $id ?>"
    class="text-sm text-blue-600 hover:underline">Editar</a>
</div>

<div class="max-w-2xl space-y-4">

  <!-- Cabecera -->
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
    <div class="flex items-start justify-between gap-3 mb-4">
      <div>
        <p class="text-xs text-gray-400 mb-0.5">Importación #<?= $id ?></p>
        <h2 class="text-lg font-bold text-gray-900"><?= esc($imp['proveedor'] ?: '—') ?></h2>
        <?php if ($imp['familia_productos']): ?>
        <p class="text-sm text-gray-500"><?= esc($imp['familia_productos']) ?></p>
        <?php endif; ?>
      </div>
      <div class="flex-shrink-0"><?= badge($imp['estado']) ?></div>
    </div>

    <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
      <?php
      $campos = [
          'Origen'        => $imp['origen'],
          'N° Proforma'   => $imp['numero_proforma'],
          'Monto FOB'     => $imp['monto_fob'] ? 'USD ' . number_format((float)$imp['monto_fob'], 2, ',', '.') : null,
          'ETD'           => $imp['etd'] ? fecha($imp['etd']) : null,
          'ETA'           => $imp['eta'] ? fecha($imp['eta']) : null,
          'N° B/L'        => $imp['numero_bl'],
          'Barco'         => $imp['nombre_barco'],
          'Forwarder'     => $imp['forwarder_nombre'],
      ];
      foreach ($campos as $lbl => $val):
          if (!$val) continue;
      ?>
      <div>
        <dt class="text-xs text-gray-400"><?= $lbl ?></dt>
        <dd class="font-medium text-gray-800"><?= esc($val) ?></dd>
      </div>
      <?php endforeach; ?>
    </dl>

    <?php if ($imp['observaciones']): ?>
    <div class="mt-4 pt-4 border-t border-gray-100">
      <p class="text-xs text-gray-400 mb-1">Observaciones</p>
      <p class="text-sm text-gray-700 whitespace-pre-wrap"><?= esc($imp['observaciones']) ?></p>
    </div>
    <?php endif; ?>

    <!-- Cambiar estado -->
    <div class="mt-4 pt-4 border-t border-gray-100">
      <form method="POST" class="flex flex-wrap gap-2">
        <?= csrf_field() ?>
        <?php foreach (['pendiente'=>'Pendiente','embarcado'=>'Embarcado','arribado'=>'Arribado','cerrado'=>'Cerrado'] as $est => $lbl): ?>
        <button type="submit" name="nuevo_estado" value="<?= $est ?>"
          class="px-3 py-1.5 rounded-lg text-sm border transition-colors
          <?= $imp['estado'] === $est ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-50' ?>">
          <?= $lbl ?>
        </button>
        <?php endforeach; ?>
      </form>
    </div>
  </div>

  <!-- Documentos -->
  <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
      <h3 class="font-semibold text-gray-800 text-sm">Documentos (<?= count($docs) ?>)</h3>
    </div>

    <?php if ($docs): ?>
    <ul class="divide-y divide-gray-50">
      <?php foreach ($docs as $doc): ?>
      <li class="px-5 py-3 flex items-center justify-between gap-3">
        <div class="min-w-0 flex-1 flex items-center gap-2">
          <?php if ($doc['tipo'] === 'link'): ?>
          <svg class="w-4 h-4 text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
          </svg>
          <a href="<?= esc($doc['url']) ?>" target="_blank" rel="noopener"
            class="text-sm text-blue-600 hover:underline truncate"><?= esc($doc['nombre']) ?></a>
          <?php else: ?>
          <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
          </svg>
          <a href="<?= BASE_URL . '/' . esc($doc['archivo_path']) ?>" target="_blank"
            class="text-sm text-gray-700 hover:underline truncate"><?= esc($doc['nombre']) ?></a>
          <?php endif; ?>
          <span class="text-xs text-gray-300 flex-shrink-0"><?= fecha($doc['created_at']) ?></span>
        </div>
        <form method="POST" action="<?= BASE_URL ?>/importaciones/delete_doc.php"
          onsubmit="return confirm('¿Eliminar este documento?')">
          <?= csrf_field() ?>
          <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
          <input type="hidden" name="importacion_id" value="<?= $id ?>">
          <button type="submit" class="text-xs text-gray-400 hover:text-red-500 flex-shrink-0">Eliminar</button>
        </form>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <!-- Agregar documentos -->
    <div class="px-5 py-4 border-t border-gray-100 space-y-3">
      <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Agregar documento</p>

      <!-- Tab selector -->
      <div class="flex gap-2 border-b border-gray-100 pb-3">
        <button type="button" id="tab-archivo" onclick="showTab('archivo')"
          class="px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-600 text-white">
          Subir archivo
        </button>
        <button type="button" id="tab-link" onclick="showTab('link')"
          class="px-3 py-1.5 rounded-lg text-xs font-medium bg-white border border-gray-200 text-gray-600 hover:bg-gray-50">
          Link externo
        </button>
      </div>

      <!-- Form: Archivo -->
      <form method="POST" action="<?= BASE_URL ?>/importaciones/upload_doc.php"
        enctype="multipart/form-data" id="form-archivo">
        <?= csrf_field() ?>
        <input type="hidden" name="importacion_id" value="<?= $id ?>">
        <div class="space-y-2">
          <input type="text" name="nombre_doc" placeholder="Nombre del documento (ej: Bill of Lading)"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          <input type="file" name="archivo"
            accept=".pdf,.jpg,.jpeg,.png,.xlsx,.xls,.docx,.doc"
            class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
          <button type="submit"
            class="w-full bg-blue-600 text-white text-sm font-medium py-2 rounded-lg hover:bg-blue-700">
            Subir
          </button>
        </div>
      </form>

      <!-- Form: Link -->
      <form method="POST" id="form-link" class="hidden">
        <?= csrf_field() ?>
        <div class="space-y-2">
          <input type="text" name="nombre_link" placeholder="Nombre del documento"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          <input type="url" name="url_link" placeholder="https://drive.google.com/…"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          <button type="submit" name="agregar_link" value="1"
            class="w-full bg-blue-600 text-white text-sm font-medium py-2 rounded-lg hover:bg-blue-700">
            Guardar link
          </button>
        </div>
      </form>
    </div>
  </div>

</div>

<script>
function showTab(tab) {
    const isArchivo = tab === 'archivo';
    document.getElementById('form-archivo').classList.toggle('hidden', !isArchivo);
    document.getElementById('form-link').classList.toggle('hidden', isArchivo);
    document.getElementById('tab-archivo').className = isArchivo
        ? 'px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-600 text-white'
        : 'px-3 py-1.5 rounded-lg text-xs font-medium bg-white border border-gray-200 text-gray-600 hover:bg-gray-50';
    document.getElementById('tab-link').className = !isArchivo
        ? 'px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-600 text-white'
        : 'px-3 py-1.5 rounded-lg text-xs font-medium bg-white border border-gray-200 text-gray-600 hover:bg-gray-50';
}
</script>

<?php require_once '../includes/footer.php'; ?>
