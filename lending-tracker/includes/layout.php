<?php
declare(strict_types=1);

function render_header(string $title, ?array $user = null): void
{
    $flashError = flash('error');
    $flashSuccess = flash('success');
    ?>
    <!doctype html>
    <html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo e($title . ' - ' . APP_NAME); ?></title>
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/styles.css">
    </head>
    <body>
    <header class="topbar">
        <div class="container topbar-inner">
            <div class="brand"><?php echo e(APP_NAME); ?></div>
            <?php if ($user): ?>
                <nav class="nav">
                    <?php if ($user['role'] === 'admin'): ?>
                        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php">Panel</a>
                        <a href="<?php echo BASE_URL; ?>/admin/users.php">Prestamistas</a>
                        <a href="<?php echo BASE_URL; ?>/admin/lendings.php">Préstamos</a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>/lender/dashboard.php">Mi panel</a>
                        <a href="<?php echo BASE_URL; ?>/lender/history.php">Depósitos y pagos</a>
                    <?php endif; ?>
                    <a href="<?php echo BASE_URL; ?>/change_password.php">Cambiar contraseña</a>
                    <a href="<?php echo BASE_URL; ?>/logout.php">Salir</a>
                </nav>
            <?php endif; ?>
        </div>
    </header>
    <main class="container">
        <?php if ($flashError): ?>
            <div class="alert alert-error"><?php echo e($flashError); ?></div>
        <?php endif; ?>
        <?php if ($flashSuccess): ?>
            <div class="alert alert-success"><?php echo e($flashSuccess); ?></div>
        <?php endif; ?>
    <?php
}

function render_footer(): void
{
    ?>
    </main>
    </body>
    </html>
    <?php
}

