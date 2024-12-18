<?php
require 'db.php';
session_start();

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

function error_and_back($message) {
    echo '<p>' . htmlspecialchars($message) . '</p>';
    echo '<p><a href="index.php">Вернуться на главную страницу</a></p>';
    exit;
}

if (!isset($_POST['template_name']) || !isset($_POST['display_template_name'])) {
    error_and_back("Необходимо указать имя шаблона и отображаемое имя шаблона.");
}

$template_name = $_POST['template_name'];
$display_template_name = $_POST['display_template_name'];

if (!preg_match('/^[a-zA-Z0-9_]+$/', $template_name)) {
    error_and_back("Недопустимое имя шаблона.");
}

if (empty($display_template_name)) {
    error_and_back("Отображаемое имя шаблона не может быть пустым.");
}

$stmt = $pdo->prepare("SELECT * FROM templates WHERE template_name = ?");
$stmt->execute([$template_name]);
if ($stmt->rowCount() > 0) {
    error_and_back("Шаблон с таким именем уже существует.");
}

$columns = $_POST['columns'] ?? [];

if (empty($columns)) {
    error_and_back("Необходимо добавить хотя бы одну колонку.");
}

foreach ($columns as $index => $column) {
    if (!isset($column['column_name'], $column['data_type'], $column['display_column_name'])) {
        error_and_back("Не все данные для колонки указаны. Проверьте ввод для колонки №" . ($index + 1) . ".");
    }

    $column_name = $column['column_name'];
    $data_type = $column['data_type'];
    $display_column_name = $column['display_column_name'];

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $column_name)) {
        error_and_back("Недопустимое имя колонки: $column_name");
    }

    $valid_data_types = ['VARCHAR(255)', 'INT', 'TEXT', 'DATE', 'DATETIME'];
    if (!in_array($data_type, $valid_data_types)) {
        error_and_back("Недопустимый тип данных: $data_type");
    }

    if (empty($display_column_name)) {
        error_and_back("Отображаемое имя колонки не может быть пустым для колонки: $column_name");
    }
}

$stmt = $pdo->prepare("INSERT INTO templates (template_name, display_template_name) VALUES (?, ?)");
$stmt->execute([$template_name, $display_template_name]);

foreach ($columns as $column) {
    $column_name = $column['column_name'];
    $data_type = $column['data_type'];
    $display_column_name = $column['display_column_name'];

    $stmt = $pdo->prepare("INSERT INTO template_columns (template_name, column_name, data_type, display_column_name) VALUES (?, ?, ?, ?)");
    $stmt->execute([$template_name, $column_name, $data_type, $display_column_name]);
}

header("Location: index.php");
exit;
?>
<p><a href="index.php">Вернуться на главную страницу</a></p>
