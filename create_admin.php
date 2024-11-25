<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = 1");
    $stmt->execute();
    $admin_exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin_exists) {
        echo "Администратор уже существует.";
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (id, username, password) VALUES (1, ?, ?)");
    $stmt->execute([$username, $hashed_password]);

    $_SESSION['user'] = $username;

    header("Location: index.php");
    exit();
}
?>
