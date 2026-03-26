<?php
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ? AND activo = 1 LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['nombre'];
        $_SESSION['user_role'] = $user['rol'];
        header('Location: ' . BASE_URL . '/');
        exit;
    }
    $error = 'Email o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ingresar — CRM Trivium</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
<div class="bg-white rounded-xl shadow-sm border border-gray-200 w-full max-w-sm p-8">
  <div class="text-center mb-8">
    <h1 class="text-2xl font-bold text-gray-900">Trivium CRM</h1>
    <p class="text-sm text-gray-500 mt-1">Ingresá con tu cuenta</p>
  </div>

  <?php if ($error): ?>
  <div class="mb-4 px-4 py-2.5 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
    <?= esc($error) ?>
  </div>
  <?php endif; ?>

  <form method="POST" class="space-y-4">
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
      <input type="email" name="email" required autofocus
        value="<?= esc($_POST['email'] ?? '') ?>"
        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
      <input type="password" name="password" required
        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
    </div>
    <button type="submit"
      class="w-full bg-blue-600 text-white font-medium text-sm py-2.5 rounded-lg hover:bg-blue-700 transition-colors mt-2">
      Ingresar
    </button>
  </form>
</div>
</body>
</html>
