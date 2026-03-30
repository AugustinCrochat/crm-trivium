CREATE DATABASE IF NOT EXISTS lending_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lending_tracker;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'lender') NOT NULL DEFAULT 'lender',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS lending_accounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    principal_amount DECIMAL(12, 2) NOT NULL,
    annual_rate DECIMAL(8, 2) NOT NULL,
    freeze_days INT NOT NULL,
    start_date DATE NOT NULL,
    status ENUM('active', 'closed') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lending_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS deposits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lending_id INT UNSIGNED NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    deposit_date DATE NOT NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_deposit_lending FOREIGN KEY (lending_id) REFERENCES lending_accounts(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS payment_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lending_id INT UNSIGNED NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    payment_date DATE NOT NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_lending FOREIGN KEY (lending_id) REFERENCES lending_accounts(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO settings (setting_key, setting_value)
VALUES ('default_rate', '12')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

INSERT INTO users (name, email, password_hash, role, is_active)
VALUES ('Administrator', 'admin@local.test', '$2y$10$lCqFnxd2JWq1YgdU8Yh3guOBqzerkOG5HeZNmW7B4j527ytEaq7OW', 'admin', 1)
ON DUPLICATE KEY UPDATE email = VALUES(email);

