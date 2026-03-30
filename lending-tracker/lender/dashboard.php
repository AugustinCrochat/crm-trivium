<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$lender = require_role('lender');
$defaultRate = (float) app_setting('default_rate', '12');
$simulation = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $principal = (float) ($_POST['sim_principal'] ?? 0);
    $days = (int) ($_POST['sim_days'] ?? 0);

    if ($principal > 0 && $days > 0) {
        $simInterest = lending_interest($principal, $defaultRate, $days);
        $simTotal = lending_total_return($principal, $defaultRate, $days);
        $simDaily = lending_daily_value($principal, $defaultRate, $days);
        $simulation = [
            'principal' => $principal,
            'days' => $days,
            'interest' => $simInterest,
            'total' => $simTotal,
            'daily' => $simDaily,
        ];
    } else {
        flash('error', 'La simulación necesita un monto válido y días de inmovilización válidos.');
        redirect('/lender/dashboard.php');
    }
}

$stmt = db()->prepare(
    'SELECT id, principal_amount, annual_rate, freeze_days, start_date, status
     FROM lending_accounts
     WHERE user_id = :user_id
     ORDER BY created_at DESC'
);
$stmt->execute(['user_id' => $lender['id']]);
$lendings = $stmt->fetchAll();

$totalPrincipal = 0.0;
$totalInterest = 0.0;
$totalDaily = 0.0;
foreach ($lendings as $loan) {
    $p = (float) $loan['principal_amount'];
    $r = (float) $loan['annual_rate'];
    $d = (int) $loan['freeze_days'];
    $totalPrincipal += $p;
    $totalInterest += lending_interest($p, $r, $d);
    $totalDaily += lending_daily_value($p, $r, $d);
}

render_header('Panel del prestamista', $lender);
?>
<div class="card">
    <h2>Hola, <?php echo e($lender['name']); ?></h2>
    <p class="small">Tu panel muestra cuánto estás ganando por día (proyección).</p>
    <div class="grid">
        <div class="card">
            <div>Monto total prestado</div>
            <div class="metric"><?php echo money($totalPrincipal); ?></div>
        </div>
        <div class="card">
            <div>Interés total (proyectado)</div>
            <div class="metric"><?php echo money($totalInterest); ?></div>
        </div>
        <div class="card">
            <div>Valor de ganancia diaria</div>
            <div class="metric"><?php echo money($totalDaily); ?>/día</div>
        </div>
    </div>
</div>

<div class="card">
    <h3>Simular un nuevo préstamo (tasa del admin: <?php echo e((string) $defaultRate); ?>%)</h3>
    <form method="post">
        <label for="sim_principal">Monto a prestar</label>
        <input id="sim_principal" type="number" min="1" step="0.01" name="sim_principal" required>

        <label for="sim_days">Días inmovilizado</label>
        <input id="sim_days" type="number" min="1" step="1" name="sim_days" required>

        <button type="submit">Simular</button>
    </form>

    <?php if ($simulation): ?>
        <div class="card">
            <h4>Resultado de la simulación</h4>
            <p>Capital: <strong><?php echo money($simulation['principal']); ?></strong></p>
            <p>Interés: <strong><?php echo money($simulation['interest']); ?></strong></p>
            <p>Total a devolver: <strong><?php echo money($simulation['total']); ?></strong></p>
            <p>Valor diario ((capital + interés) / días): <strong><?php echo money($simulation['daily']); ?>/día</strong></p>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Mis préstamos</h3>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Fecha de inicio</th>
                <th>Capital</th>
                <th>Tasa</th>
                <th>Días</th>
                <th>Total a devolver</th>
                <th>Valor diario</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($lendings as $loan): ?>
            <?php
                $principal = (float) $loan['principal_amount'];
                $rate = (float) $loan['annual_rate'];
                $days = (int) $loan['freeze_days'];
                $totalReturn = lending_total_return($principal, $rate, $days);
                $daily = lending_daily_value($principal, $rate, $days);
            ?>
            <tr>
                <td><?php echo e($loan['start_date']); ?></td>
                <td><?php echo money($principal); ?></td>
                <td><?php echo e((string) $rate); ?>%</td>
                <td><?php echo $days; ?> días</td>
                <td><?php echo money($totalReturn); ?></td>
                <td><?php echo money($daily); ?>/día</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php render_footer(); ?>

