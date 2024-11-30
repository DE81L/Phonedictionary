<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

function error_and_back($message) {
    echo '<p>' . htmlspecialchars($message) . '</p>';
    echo '<p><a href="index.php">Вернуться на главную страницу</a></p>';
    exit;
}

if (!isset($_POST['display_table_name']) || !isset($_POST['template_name']) || !isset($_POST['table_name'])) {
    error_and_back("Необходимо указать имя таблицы, отображаемое имя таблицы и выбрать шаблон.");
}

$table_name = $_POST['table_name'];
$display_table_name = $_POST['display_table_name'];
$template_name = $_POST['template_name'];

if (!preg_match('/^[a-zA-Z0-9_]+$/', $table_name)) {
    error_and_back("Недопустимое имя таблицы.");
}

if (empty($display_table_name)) {
    error_and_back("Отображаемое имя таблицы не может быть пустым.");
}

$stmt = $pdo->prepare("SHOW TABLES LIKE ?");
$stmt->execute([$table_name]);
if ($stmt->rowCount() > 0) {
    error_and_back("Таблица с именем '$table_name' уже существует.");
}

$stmt = $pdo->prepare("SELECT column_name, data_type, display_column_name FROM template_columns WHERE template_name = ?");
$stmt->execute([$template_name]);
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($columns)) {
    error_and_back("Выбранный шаблон не содержит колонок.");
}

$columns_sql = [];
foreach ($columns as $column) {
    $column_name = $column['column_name'];
    $data_type = $column['data_type'];

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $column_name)) {
        error_and_back("Недопустимое имя колонки в шаблоне: $column_name");
    }

    $valid_data_types = ['VARCHAR(255)', 'INT', 'TEXT', 'DATE', 'DATETIME'];
    if (!in_array($data_type, $valid_data_types)) {
        error_and_back("Недопустимый тип данных в шаблоне: $data_type");
    }

    $columns_sql[] = "`$column_name` $data_type NOT NULL";
}
$columns_sql[] = "`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY";
$create_table_sql = "CREATE TABLE `$table_name` (" . implode(", ", $columns_sql) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$pdo->exec($create_table_sql);

$stmt = $pdo->prepare("INSERT INTO table_metadata (table_name, display_table_name) VALUES (?, ?)");
$stmt->execute([$table_name, $display_table_name]);

foreach ($columns as $column) {
    $column_name = $column['column_name'];
    $display_column_name = $column['display_column_name'];
    $data_type = $column['data_type'];

    $stmt = $pdo->prepare("INSERT INTO column_metadata (table_name, column_name, display_column_name, data_type) VALUES (?, ?, ?, ?)");
    $stmt->execute([$table_name, $column_name, $display_column_name, $data_type]);
}

header("Location: index.php?table=$table_name");
exit;
?>
<p><a href="index.php">Вернуться на главную страницу</a></p>
