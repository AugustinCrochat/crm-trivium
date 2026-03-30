<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPassword = (string) ($_POST['old_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $newPasswordConfirm = (string) ($_POST['new_password_confirm'] ?? '');

    if ($oldPassword === '' || $newPassword === '' || $newPasswordConfirm === '') {
        flash('error', 'Completá todos los campos.');
        redirect('/change_password.php');
    }

    if (strlen($newPassword) < 6) {
        flash('error', 'La nueva contraseña debe tener al menos 6 caracteres.');
        redirect('/change_password.php');
    }

    if ($newPassword !== $newPasswordConfirm) {
        flash('error', 'La confirmación de la nueva contraseña no coincide.');
        redirect('/change_password.php');
    }

    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = :id AND is_active = 1 LIMIT 1');
    $stmt->execute(['id' => $user['id']]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($oldPassword, $row['password_hash'])) {
        flash('error', 'La contraseña anterior no es correcta.');
        redirect('/change_password.php');
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $update = db()->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
    $update->execute([
        'password_hash' => $newHash,
        'id' => $user['id'],
    ]);

    flash('success', 'Contraseña actualizada.');
    redirect('/index.php');
}

render_header('Cambiar contraseña', $user);
?>
<div class="card login-box">
    <h2>Cambiar contraseña</h2>
    <form method="post">
        <label for="old_password">Contraseña anterior</label>
        <input id="old_password" type="password" name="old_password" required>

        <label for="new_password">Nueva contraseña</label>
        <input id="new_password" type="password" name="new_password" required>

        <label for="new_password_confirm">Confirmar nueva contraseña</label>
        <input id="new_password_confirm" type="password" name="new_password_confirm" required>

        <button type="submit">Guardar</button>
    </form>
</div>
<?php render_footer(); ?>

