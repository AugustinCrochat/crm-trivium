<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$lender = require_role('lender');

$depositsStmt = db()->prepare(
    'SELECT d.amount, d.deposit_date, d.note, la.id AS lending_id
     FROM deposits d
     JOIN lending_accounts la ON la.id = d.lending_id
     WHERE la.user_id = :user_id
     ORDER BY d.deposit_date DESC'
);
$depositsStmt->execute(['user_id' => $lender['id']]);
$deposits = $depositsStmt->fetchAll();

$paymentsStmt = db()->prepare(
    'SELECT p.amount, p.payment_date, p.note, la.id AS lending_id
     FROM payment_logs p
     JOIN lending_accounts la ON la.id = p.lending_id
     WHERE la.user_id = :user_id
     ORDER BY p.payment_date DESC'
);
$paymentsStmt->execute(['user_id' => $lender['id']]);
$payments = $paymentsStmt->fetchAll();

render_header('Depósitos y pagos', $lender);
?>
<div class="card">
    <h2>Depósitos</h2>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Préstamo</th>
                <th>Monto</th>
                <th>Fecha</th>
                <th>Nota</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($deposits as $deposit): ?>
            <tr>
                <td>#<?php echo (int) $deposit['lending_id']; ?></td>
                <td><?php echo money((float) $deposit['amount']); ?></td>
                <td><?php echo e($deposit['deposit_date']); ?></td>
                <td><?php echo e((string) ($deposit['note'] ?? '')); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="card">
    <h2>Fechas de pago</h2>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Préstamo</th>
                <th>Monto</th>
                <th>Fecha</th>
                <th>Nota</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($payments as $payment): ?>
            <tr>
                <td>#<?php echo (int) $payment['lending_id']; ?></td>
                <td><?php echo money((float) $payment['amount']); ?></td>
                <td><?php echo e($payment['payment_date']); ?></td>
                <td><?php echo e((string) ($payment['note'] ?? '')); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php render_footer(); ?>

