<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';

const DB_HOST = 'localhost';
const DB_NAME = 'u982106244_prestamos';
const DB_USER = 'u982106244_prestamos';
const DB_PASS = '_Empire1815';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

