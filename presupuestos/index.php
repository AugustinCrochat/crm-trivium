<?php
require_once '../config/db.php';
$title = 'Presupuestos';
require_once '../includes/header.php';

$estado = $_GET['estado'] ?? 'todos';
$q      = trim($_GET['q'] ?? '');

$where  = [];
$params = [];
if ($estado !== 'todos') {
    $where[]  = 'p.estado = ?';
    $params[] = $estado;
}
if ($q !== '') {
    $where[]  = '(c.nombre LIKE ? OR c.empresa LIKE ?)';
    $like     = "%{$q}%";
    $params   = array_merge($params, [$like, $like]);
}

$sql = "SELECT p.*, c.nombre AS cliente_nombre, c.empresa AS cliente_empresa
        FROM presupuestos p
        LEFT JOIN clientes c ON c.id = p.cliente_id";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY p.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$presupuestos = $stmt->fetchAll();

// KPIs
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'enviado' THEN 1 ELSE 0 END) as enviados,
        SUM(CASE WHEN estado = 'aprobado' AND MONTH(fecha) = MONTH(NOW()) THEN 1 ELSE 0 END) as aprobados_mes,
        SUM(CASE WHEN estado = 'enviado' THEN total ELSE 0 END) as monto_pendiente
    FROM presupuestos
")->fetch();

// Enviados hace +7 días sin resolver
$pa = $pdo->query("
    SELECT p.id, p.fecha, p.total,
            c.nombre AS cliente_nombre, c.empresa AS cliente_empresa,
            DATEDIFF(NOW(), p.fecha) AS dias
    FROM presupuestos p
    LEFT JOIN clientes c ON c.id = p.cliente_id
    WHERE p.estado = 'enviado' AND p.fecha <= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY p.fecha ASC
")->fetchAll();
$pendientes_alerta = $pa;

$labels = ['todos'=>'Todos','borrador'=>'Borrador','enviado'=>'Enviado','aprobado'=>'Aprobado','rechazado'=>'Rechazado'];
?>

<div class="space-y-6">
  
  <!-- Stats Grid -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
      <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Enviados</p>
      <p class="text-2xl font-black text-blue-600"><?= (int)$stats['enviados'] ?></p>
      <p class="text-[10px] text-gray-400 mt-1">Esperando respuesta</p>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
      <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Aprobados Mes</p>
      <p class="text-2xl font-black text-green-600"><?= (int)$stats['aprobados_mes'] ?></p>
      <p class="text-[10px] text-gray-400 mt-1">Efectividad este mes</p>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm lg:col-span-2">
      <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Monto en Seguimiento</p>
      <p class="text-2xl font-black text-gray-900"><?= money($stats['monto_pendiente'] ?? 0) ?></p>
      <p class="text-[10px] text-gray-400 mt-1">Total de presupuestos en estado 'Enviado'</p>
    </div>
  </div>

  <!-- Alerta enviados sin respuesta -->
  <?php if ($pendientes_alerta): ?>
  <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5">
    <div class="flex items-center gap-3 mb-3">
      <div class="bg-amber-100 p-2 rounded-lg text-amber-600">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      </div>
      <div>
        <h3 class="text-sm font-bold text-amber-900">Seguimiento Requerido</h3>
        <p class="text-xs text-amber-700">Presupuestos enviados hace más de 7 días sin respuesta.</p>
      </div>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
      <?php foreach ($pendientes_alerta as $pa): ?>
      <a href="<?= BASE_URL ?>/presupuestos/ver.php?id=<?= $pa['id'] ?>"
        class="bg-white/50 border border-amber-200 rounded-xl px-3 py-2 flex items-center justify-between hover:bg-white transition-colors">
        <div class="min-w-0">
          <p class="text-xs font-bold text-amber-900 truncate">#<?= $pa['id'] ?> · <?= esc($pa['cliente_empresa'] ?: $pa['cliente_nombre']) ?></p>
          <p class="text-[10px] text-amber-600"><?= $pa['dias'] ?> días de demora</p>
        </div>
        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
    <!-- Toolbar -->
    <div class="p-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4 bg-gray-50/50">
      <div class="flex gap-1 overflow-x-auto">
        <?php foreach ($labels as $key => $lbl): ?>
        <a href="?estado=<?= $key ?><?= $q ? '&q=' . urlencode($q) : '' ?>"
          class="flex-shrink-0 px-4 py-1.5 rounded-xl text-xs font-bold transition-all border
          <?= $estado === $key 
              ? 'bg-blue-600 border-blue-600 text-white shadow-lg shadow-blue-200' 
              : 'bg-white border-gray-200 text-gray-500 hover:border-gray-300 hover:text-gray-700' ?>">
          <?= $lbl ?>
        </a>
        <?php endforeach; ?>
      </div>

      <div class="flex items-center gap-3 ml-auto">
        <form method="GET" class="relative group">
          <input type="hidden" name="estado" value="<?= esc($estado) ?>">
          <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input type="search" name="q" value="<?= esc($q) ?>" placeholder="Buscar presupuesto..."
            class="pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all w-48 lg:w-64">
        </form>
        <a href="<?= BASE_URL ?>/presupuestos/nuevo.php"
          class="inline-flex items-center gap-2 bg-blue-600 text-white text-sm font-bold px-4 py-2 rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-200">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Nuevo
        </a>
      </div>
    </div>

    <!-- Results Table -->
    <div id="search-results">
      <?php if (!$presupuestos): ?>
      <div class="p-20 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 text-gray-300 mb-4">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <p class="text-gray-400 font-medium">No se encontraron presupuestos</p>
      </div>
      <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50/50 border-b border-gray-100">
              <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Nº Presupuesto</th>
              <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Cliente / Empresa</th>
              <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Monto Total</th>
              <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <?php foreach ($presupuestos as $p): ?>
            <tr class="hover:bg-gray-50/80 transition-colors group">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <span class="text-sm font-bold text-gray-900">#<?= str_pad($p['id'],5,'0',STR_PAD_LEFT) ?></span>
                  <?= badge($p['estado']) ?>
                </div>
                <p class="text-[10px] text-gray-400 mt-0.5"><?= fecha($p['fecha']) ?></p>
              </td>
              <td class="px-6 py-4">
                <p class="text-sm font-bold text-gray-800"><?= esc($p['cliente_empresa'] ?: $p['cliente_nombre'] ?: 'Sin asignar') ?></p>
                <p class="text-xs text-gray-400"><?= esc($p['cliente_nombre'] ?: '—') ?></p>
              </td>
              <td class="px-6 py-4 text-right">
                <p class="text-sm font-black text-gray-900"><?= money($p['total']) ?></p>
                <p class="text-[10px] text-gray-400 mt-0.5"><?= (int)$p['validez_dias'] ?> días de validez</p>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-center gap-2">
                  <a href="<?= BASE_URL ?>/presupuestos/ver.php?id=<?= $p['id'] ?>" 
                    class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="Ver Detalles">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  </a>
                  <a href="<?= BASE_URL ?>/presupuestos/pdf.php?id=<?= $p['id'] ?>" target="_blank"
                    class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Ver PDF">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                  </a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
