<?php
// db.php
$host = 'localhost';
$dbname = 'phone_directory';
$username = 'root';  // replace with your MySQL username
$password = 'root';  // replace with your MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set error mode to exceptions
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}
?>
