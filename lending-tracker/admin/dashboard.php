<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$admin = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rate = (float) ($_POST['default_rate'] ?? 0);
    if ($rate <= 0 || $rate > 200) {
        flash('error', 'Configurá una tasa anual válida entre 0 y 200.');
    } else {
        upsert_setting('default_rate', (string) $rate);
        flash('success', 'Tasa anual global actualizada.');
    }
    redirect('/admin/dashboard.php');
}

$totalLent = (float) db()->query('SELECT COALESCE(SUM(principal_amount), 0) AS total FROM lending_accounts')->fetch()['total'];
$totalLenders = (int) db()->query("SELECT COUNT(*) AS total FROM users WHERE role = 'lender'")->fetch()['total'];
$activeLendings = (int) db()->query("SELECT COUNT(*) AS total FROM lending_accounts WHERE status = 'active'")->fetch()['total'];
$defaultRate = (float) app_setting('default_rate', '12');

render_header('Panel admin', $admin);
?>
<div class="card">
    <h2>Panel admin</h2>
    <p class="small">Controlá tu operación y configurá la tasa anual de retorno global.</p>
    <div class="grid">
        <div class="card">
            <div>Total prestado</div>
            <div class="metric"><?php echo money($totalLent); ?></div>
        </div>
        <div class="card">
            <div>Prestamistas</div>
            <div class="metric"><?php echo $totalLenders; ?></div>
        </div>
        <div class="card">
            <div>Préstamos activos</div>
            <div class="metric"><?php echo $activeLendings; ?></div>
        </div>
    </div>
</div>

<div class="card">
    <h3>Configurar tasa anual de retorno (%)</h3>
    <form method="post">
        <label for="default_rate">Tasa anual (%)</label>
        <input id="default_rate" type="number" step="0.01" min="0.01" max="200" name="default_rate" value="<?php echo e((string) $defaultRate); ?>" required>
        <button type="submit">Guardar tasa</button>
    </form>
</div>
<?php render_footer(); ?>

