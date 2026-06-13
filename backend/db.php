<?php
$host    = 'localhost';
$db_name = 'revsion'; // Your database name
$db_user = 'root';     // Default for local setups like XAMPP
$db_pass = '';         // Default is empty for local XAMPP setups

try {
    // Establish a secure UTF-8 connection link
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    
    // Turn on error exceptions so we can catch bugs quickly
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Kill the script and print the connection error if it fails
    die("Database connection failed: " . $e->getMessage());
}
?>