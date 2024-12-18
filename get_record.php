<?php
require 'db.php';

if (!isset($_GET['table']) || !isset($_GET['id'])) {
    die("Не указаны параметры.");
}

$table = $_GET['table'];
$id = $_GET['id'];

$allowed_tables = [];
$stmt = $pdo->query("SELECT table_name FROM table_metadata");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $allowed_tables[] = $row['table_name'];
}
$allowed_tables[] = 'users';

if (!in_array($table, $allowed_tables)) {
    die("Недопустимая таблица.");
}

$stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id = ?");
$stmt->execute([$id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    die("Запись не найдена.");
}

if ($table == 'users') {
    unset($record['password']);
}

echo json_encode($record);
?>
