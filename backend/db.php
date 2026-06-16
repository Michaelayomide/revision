<?php

// Centralized Database Credentials
$host     = 'localhost';
$db_name  = 'revsion'; // 
$username = 'root';
$password = '';

try {
    // Establish global PDO link instance
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Safety bridge variable for index.php and user.php
    $db = $pdo; 
    
} catch (PDOException $e) {
    // If connection drops, stop page execution cleanly and report why
    die("Database Connection Failure: " . $e->getMessage());
}
?>