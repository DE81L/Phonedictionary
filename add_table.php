<?php
require 'db.php';
session_start();

// Проверяем, что пользователь авторизован
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_POST['table_name']) || !isset($_POST['display_table_name'])) {
    die("Необходимо указать имя таблицы и отображаемое имя таблицы.");
}

$table_name = $_POST['table_name'];
$display_table_name = $_POST['display_table_name'];

// Проверка имени таблицы
if (!preg_match('/^[a-zA-Z0-9_]+$/', $table_name)) {
    die("Недопустимое имя таблицы.");
}

// Проверяем, существует ли таблица
$stmt = $pdo->prepare("SHOW TABLES LIKE ?");
$stmt->execute([$table_name]);
if ($stmt->rowCount() > 0) {
    die("Таблица уже существует.");
}

// Получаем структуру таблицы-шаблона (building_1)
$stmt = $pdo->query("SHOW CREATE TABLE building_1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$create_table_sql = $row['Create Table'];

// Заменяем имя таблицы
$create_table_sql = str_replace('`building_1`', "`$table_name`", $create_table_sql);

// Создаем новую таблицу
$pdo->exec($create_table_sql);

// Добавляем запись в table_metadata
$stmt = $pdo->prepare("INSERT INTO table_metadata (table_name, display_table_name) VALUES (?, ?)");
$stmt->execute([$table_name, $display_table_name]);

// Получаем колонки из building_1 для метаданных
$stmt = $pdo->prepare("SELECT column_name, display_column_name FROM column_metadata WHERE table_name = ?");
$stmt->execute(['building_1']);
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Добавляем колонки в column_metadata для новой таблицы
foreach ($columns as $column) {
    $stmt = $pdo->prepare("INSERT INTO column_metadata (table_name, column_name, display_column_name) VALUES (?, ?, ?)");
    $stmt->execute([$table_name, $column['column_name'], $column['display_column_name']]);
}

header("Location: index.php?table=$table_name");
exit;
?>