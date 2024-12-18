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

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        echo "Недопустимое имя пользователя.";
        exit();
    }

    if (strlen($password) < 6) {
        echo "Пароль должен быть не менее 6 символов.";
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
<p><a href="index.php">Вернуться на главную страницу</a></p>
