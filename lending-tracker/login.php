<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

if (current_user()) {
    redirect('/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT id, password_hash, is_active FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && (int) $user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = (int) $user['id'];
        flash('success', '¡Bienvenido/a de nuevo!');
        redirect('/index.php');
    }

    flash('error', 'Credenciales inválidas.');
    redirect('/login.php');
}

render_header('Iniciar sesión');
?>
<div class="login-box card">
    <h2>Entrar</h2>
    <p class="small">Accedé a tu panel de prestamista o al panel admin.</p>
    <form method="post">
        <label for="email">Correo electrónico</label>
        <input id="email" type="email" name="email" required>

        <label for="password">Contraseña</label>
        <input id="password" type="password" name="password" required>

        <button type="submit">Entrar</button>
    </form>
</div>
<?php render_footer(); ?>

