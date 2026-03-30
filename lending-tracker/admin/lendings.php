<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$admin = require_role('admin');
$defaultRate = (float) app_setting('default_rate', '12');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'create_lending');

    if ($action === 'add_deposit') {
        $lendingId = (int) ($_POST['lending_id'] ?? 0);
        $amount = (float) ($_POST['amount'] ?? 0);
        $depositDate = (string) ($_POST['deposit_date'] ?? '');
        $note = trim((string) ($_POST['note'] ?? ''));

        if ($lendingId <= 0 || $amount <= 0 || $depositDate === '') {
            flash('error', 'El depósito necesita préstamo, monto y fecha.');
            redirect('/admin/lendings.php');
        }

        $stmt = db()->prepare(
            'INSERT INTO deposits (lending_id, amount, deposit_date, note) VALUES (:lending_id, :amount, :deposit_date, :note)'
        );
        $stmt->execute([
            'lending_id' => $lendingId,
            'amount' => $amount,
            'deposit_date' => $depositDate,
            'note' => $note === '' ? null : $note,
        ]);

        flash('success', 'Depósito registrado.');
        redirect('/admin/lendings.php');
    }

    if ($action === 'add_payment') {
        $lendingId = (int) ($_POST['lending_id'] ?? 0);
        $amount = (float) ($_POST['amount'] ?? 0);
        $paymentDate = (string) ($_POST['payment_date'] ?? '');
        $note = trim((string) ($_POST['note'] ?? ''));

        if ($lendingId <= 0 || $amount <= 0 || $paymentDate === '') {
            flash('error', 'El pago necesita préstamo, monto y fecha.');
            redirect('/admin/lendings.php');
        }

        $stmt = db()->prepare(
            'INSERT INTO payment_logs (lending_id, amount, payment_date, note) VALUES (:lending_id, :amount, :payment_date, :note)'
        );
        $stmt->execute([
            'lending_id' => $lendingId,
            'amount' => $amount,
            'payment_date' => $paymentDate,
            'note' => $note === '' ? null : $note,
        ]);

        flash('success', 'Pago registrado.');
        redirect('/admin/lendings.php');
    }

    $userId = (int) ($_POST['user_id'] ?? 0);
    $principal = (float) ($_POST['principal_amount'] ?? 0);
    $annualRate = (float) ($_POST['annual_rate'] ?? 0);
    $freezeDays = (int) ($_POST['freeze_days'] ?? 0);
    $startDate = (string) ($_POST['start_date'] ?? '');

    if ($userId <= 0 || $principal <= 0 || $annualRate <= 0 || $freezeDays <= 0 || $startDate === '') {
        flash('error', 'Completá todos los campos del préstamo con valores válidos.');
        redirect('/admin/lendings.php');
    }

    $stmt = db()->prepare(
        'INSERT INTO lending_accounts (user_id, principal_amount, annual_rate, freeze_days, start_date, status)
         VALUES (:user_id, :principal_amount, :annual_rate, :freeze_days, :start_date, :status)'
    );
    $stmt->execute([
        'user_id' => $userId,
        'principal_amount' => $principal,
        'annual_rate' => $annualRate,
        'freeze_days' => $freezeDays,
        'start_date' => $startDate,
        'status' => 'active',
    ]);

    flash('success', 'Préstamo creado.');
    redirect('/admin/lendings.php');
}

$lenders = db()->query("SELECT id, name, email FROM users WHERE role = 'lender' AND is_active = 1 ORDER BY name")->fetchAll();
$lendings = db()->query(
    "SELECT la.*, u.name AS lender_name, u.email
     FROM lending_accounts la
     JOIN users u ON u.id = la.user_id
     ORDER BY la.created_at DESC"
)->fetchAll();

render_header('Préstamos', $admin);
?>
<div class="card">
    <h2>Crear préstamo</h2>
    <form method="post">
        <input type="hidden" name="action" value="create_lending">
        <label for="user_id">Prestamista</label>
        <select id="user_id" name="user_id" required>
            <option value="">Seleccioná un prestamista</option>
            <?php foreach ($lenders as $lender): ?>
                <option value="<?php echo (int) $lender['id']; ?>">
                    <?php echo e($lender['name'] . ' (' . $lender['email'] . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="principal_amount">Monto a prestar</label>
        <input id="principal_amount" type="number" min="1" step="0.01" name="principal_amount" required>

        <label for="annual_rate">Tasa anual de retorno (%)</label>
        <input id="annual_rate" type="number" min="0.01" max="200" step="0.01" name="annual_rate" value="<?php echo e((string) $defaultRate); ?>" required>

        <label for="freeze_days">Días inmovilizado</label>
        <input id="freeze_days" type="number" min="1" step="1" name="freeze_days" required>

        <label for="start_date">Fecha de inicio</label>
        <input id="start_date" type="date" name="start_date" required>

        <button type="submit">Crear préstamo</button>
    </form>
</div>

<div class="grid">
    <div class="card">
        <h3>Registrar depósito</h3>
        <form method="post">
            <input type="hidden" name="action" value="add_deposit">
            <label for="dep_lending_id">Préstamo</label>
            <select id="dep_lending_id" name="lending_id" required>
                <option value="">Seleccioná el préstamo</option>
                <?php foreach ($lendings as $lending): ?>
                    <option value="<?php echo (int) $lending['id']; ?>">
                        #<?php echo (int) $lending['id']; ?> - <?php echo e($lending['lender_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label for="dep_amount">Monto</label>
            <input id="dep_amount" type="number" min="0.01" step="0.01" name="amount" required>
            <label for="dep_date">Fecha del depósito</label>
            <input id="dep_date" type="date" name="deposit_date" required>
            <label for="dep_note">Nota</label>
            <input id="dep_note" type="text" name="note">
            <button type="submit">Registrar depósito</button>
        </form>
    </div>
    <div class="card">
        <h3>Registrar pago</h3>
        <form method="post">
            <input type="hidden" name="action" value="add_payment">
            <label for="pay_lending_id">Préstamo</label>
            <select id="pay_lending_id" name="lending_id" required>
                <option value="">Seleccioná el préstamo</option>
                <?php foreach ($lendings as $lending): ?>
                    <option value="<?php echo (int) $lending['id']; ?>">
                        #<?php echo (int) $lending['id']; ?> - <?php echo e($lending['lender_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label for="pay_amount">Monto</label>
            <input id="pay_amount" type="number" min="0.01" step="0.01" name="amount" required>
            <label for="pay_date">Fecha del pago</label>
            <input id="pay_date" type="date" name="payment_date" required>
            <label for="pay_note">Nota</label>
            <input id="pay_note" type="text" name="note">
            <button type="submit">Registrar pago</button>
        </form>
    </div>
</div>

<div class="card">
    <h3>Todos los préstamos</h3>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Prestamista</th>
                <th>Monto</th>
                <th>Tasa</th>
                <th>Días</th>
                <th>Total a devolver</th>
                <th>Valor diario</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($lendings as $lending): ?>
            <?php
                $principal = (float) $lending['principal_amount'];
                $rate = (float) $lending['annual_rate'];
                $days = (int) $lending['freeze_days'];
                $totalReturn = lending_total_return($principal, $rate, $days);
                $daily = lending_daily_value($principal, $rate, $days);
            ?>
            <tr>
                <td><?php echo e($lending['lender_name']); ?></td>
                <td><?php echo money($principal); ?></td>
                <td><?php echo e((string) $rate); ?>%</td>
                <td><?php echo $days; ?></td>
                <td><?php echo money($totalReturn); ?></td>
                <td><?php echo money($daily); ?>/día</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php render_footer(); ?>

