<?php
require 'db.php';
session_start();

// Проверяем, что пользователь авторизован
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['table']) || !isset($_GET['id'])) {
    die("Не указаны параметры.");
}

$table = $_GET['table'];
$id = (int)$_GET['id'];

// Проверяем, что таблица разрешена
$allowed_tables = [];
$stmt = $pdo->query("SELECT table_name FROM table_metadata");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $allowed_tables[] = $row['table_name'];
}
$allowed_tables[] = 'users'; // добавляем таблицу пользователей

if (!in_array($table, $allowed_tables)) {
    die("Недопустимая таблица.");
}



// Запрещаем удаление первой записи в таблице пользователей
if ($table === 'users' && $id === 1) {
    die("Нельзя удалить первую запись в таблице пользователей.");
}

$stmt = $pdo->prepare("DELETE FROM `$table` WHERE id = ?");
$stmt->execute([$id]);

header("Location: index.php?table=$table");
exit;
?>