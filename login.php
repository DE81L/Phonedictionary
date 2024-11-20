<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password']; // Получаем введенный пароль

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $username;
        header("Location: index.php");
        exit();
    } else {
        $error = "Неверное имя пользователя или пароль.";
        $_SESSION['error'] = $error;
        header("Location: index.php");
        exit();
    }
}
?>