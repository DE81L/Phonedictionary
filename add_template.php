<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_POST['template_name']) || !isset($_POST['display_template_name'])) {
    die("Необходимо указать имя шаблона и отображаемое имя шаблона.");
}

$template_name = $_POST['template_name'];
$display_template_name = $_POST['display_template_name'];

if (!preg_match('/^[a-zA-Z0-9_]+$/', $template_name)) {
    die("Недопустимое имя шаблона.");
}

$stmt = $pdo->prepare("SELECT * FROM templates WHERE template_name = ?");
$stmt->execute([$template_name]);
if ($stmt->rowCount() > 0) {
    die("Шаблон с таким именем уже существует.");
}

$columns = $_POST['columns'];

if (empty($columns)) {
    die("Необходимо добавить хотя бы одну колонку.");
}

$stmt = $pdo->prepare("INSERT INTO templates (template_name, display_template_name) VALUES (?, ?)");
$stmt->execute([$template_name, $display_template_name]);

foreach ($columns as $column) {
    $column_name = $column['column_name'];
    $data_type = $column['data_type'];
    $display_column_name = $column['display_column_name'];

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $column_name)) {
        die("Недопустимое имя колонки: $column_name");
    }

    $valid_data_types = ['VARCHAR(255)', 'INT', 'TEXT', 'DATE', 'DATETIME'];
    if (!in_array($data_type, $valid_data_types)) {
        die("Недопустимый тип данных: $data_type");
    }

    $stmt = $pdo->prepare("INSERT INTO template_columns (template_name, column_name, data_type, display_column_name) VALUES (?, ?, ?, ?)");
    $stmt->execute([$template_name, $column_name, $data_type, $display_column_name]);
}

header("Location: index.php");
exit;
?>
