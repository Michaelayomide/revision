<?php

$host = 'localhost';
$db_name = 'revsion';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$db_name};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $db = $pdo;
} catch (PDOException $e) {
    http_response_code(500);
    die('Database connection failed. Check backend/db.php credentials and database availability.');
}
