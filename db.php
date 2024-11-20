<?php
$host = 'localhost';
$dbname = 'phone_directory';
$username = 'root';  // замените на имя пользователя MySQL
$password = 'root';  // замените на пароль MySQL

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}
?>