<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$admin = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($name === '' || $email === '' || strlen($password) < 6) {
        flash('error', 'Necesitás nombre/correo y la contraseña debe tener al menos 6 caracteres.');
        redirect('/admin/users.php');
    }

    $stmt = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        flash('error', 'Ese correo ya está registrado.');
        redirect('/admin/users.php');
    }

    $insert = db()->prepare('INSERT INTO users (name, email, password_hash, role, is_active) VALUES (:name, :email, :password_hash, :role, :is_active)');
    $insert->execute([
        'name' => $name,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'lender',
        'is_active' => 1,
    ]);

    flash('success', 'Cuenta de prestamista creada.');
    redirect('/admin/users.php');
}

$lenders = db()->query("SELECT id, name, email, is_active, created_at FROM users WHERE role = 'lender' ORDER BY created_at DESC")->fetchAll();

render_header('Prestamistas', $admin);
?>
<div class="card">
    <h2>Crear acceso de prestamista</h2>
    <form method="post">
        <label for="name">Nombre y apellido</label>
        <input id="name" type="text" name="name" required>

        <label for="email">Correo electrónico</label>
        <input id="email" type="email" name="email" required>

        <label for="password">Contraseña temporal</label>
        <input id="password" type="text" name="password" required>

        <button type="submit">Crear cuenta</button>
    </form>
</div>

<div class="card">
    <h3>Todos los prestamistas</h3>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Estado</th>
                <th>Creado</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($lenders as $lender): ?>
            <tr>
                <td><?php echo e($lender['name']); ?></td>
                <td><?php echo e($lender['email']); ?></td>
                <td><?php echo ((int) $lender['is_active'] === 1) ? 'Activo' : 'Inactivo'; ?></td>
                <td><?php echo e($lender['created_at']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php render_footer(); ?>

