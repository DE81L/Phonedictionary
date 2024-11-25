<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}


if (!isset($_POST['display_table_name']) || !isset($_POST['template_name'])) {
    die("Необходимо указать отображаемое имя таблицы и выбрать шаблон.");
}

$display_table_name = $_POST['display_table_name'];
$template_name = $_POST['template_name'];

$table_name = 'building_1';

$stmt = $pdo->prepare("SHOW TABLES LIKE ?");
$stmt->execute([$table_name]);
if ($stmt->rowCount() > 0) {
    die("Таблица с именем '$table_name' уже существует.");
}

$stmt = $pdo->prepare("SELECT column_name, data_type, display_column_name FROM template_columns WHERE template_name = ?");
$stmt->execute([$template_name]);
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($columns)) {
    die("Выбранный шаблон не содержит колонок.");
}

$columns_sql = [];
foreach ($columns as $column) {
    $columns_sql[] = "`{$column['column_name']}` {$column['data_type']} NOT NULL";
}
$columns_sql[] = "`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY";
$create_table_sql = "CREATE TABLE `$table_name` (" . implode(", ", $columns_sql) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$pdo->exec($create_table_sql);

$stmt = $pdo->prepare("INSERT INTO table_metadata (table_name, display_table_name) VALUES (?, ?)");
$stmt->execute([$table_name, $display_table_name]);

foreach ($columns as $column) {
    $stmt = $pdo->prepare("INSERT INTO column_metadata (table_name, column_name, display_column_name) VALUES (?, ?, ?)");
    $stmt->execute([$table_name, $column['column_name'], $column['display_column_name']]);
}

header("Location: index.php?table=$table_name");
exit;
?>
