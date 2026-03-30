<?php
declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $msg = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function app_setting(string $settingKey, mixed $default = null): mixed
{
    $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = :setting_key LIMIT 1');
    $stmt->execute(['setting_key' => $settingKey]);
    $row = $stmt->fetch();

    return $row ? $row['setting_value'] : $default;
}

function upsert_setting(string $settingKey, string $value): void
{
    $sql = 'INSERT INTO settings (setting_key, setting_value)
            VALUES (:setting_key, :setting_value)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)';
    $stmt = db()->prepare($sql);
    $stmt->execute([
        'setting_key' => $settingKey,
        'setting_value' => $value,
    ]);
}

function lending_interest(float $principal, float $annualRate, int $freezeDays): float
{
    return $principal * ($annualRate / 100.0) * ($freezeDays / 365.0);
}

function lending_total_return(float $principal, float $annualRate, int $freezeDays): float
{
    return $principal + lending_interest($principal, $annualRate, $freezeDays);
}

function lending_daily_value(float $principal, float $annualRate, int $freezeDays): float
{
    if ($freezeDays <= 0) {
        return 0.0;
    }

    // Requested formula: (principal + interest) / freeze days.
    return lending_total_return($principal, $annualRate, $freezeDays) / $freezeDays;
}

function money(float $amount): string
{
    // Formato argentino: separador de miles '.', decimales ','
    return 'AR$ ' . number_format($amount, 2, ',', '.');
}

