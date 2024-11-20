<?php
require 'db.php';

if (!isset($_GET['table']) || !isset($_GET['id'])) {
    die("Не указаны параметры.");
}

$table = $_GET['table'];
$id = $_GET['id'];

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

$stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id = ?");
$stmt->execute([$id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    die("Запись не найдена.");
}

// Убираем поле 'password' из записи при редактировании пользователя
if ($table == 'users') {
    unset($record['password']);
}

echo json_encode($record);
?>