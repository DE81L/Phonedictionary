<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['table']) || !isset($_GET['action'])) {
    die("Не указаны параметры.");
}

$table = $_GET['table'];
$action = $_GET['action'];

$allowed_tables = [];
$stmt = $pdo->query("SELECT table_name FROM table_metadata");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $allowed_tables[] = $row['table_name'];
}
$allowed_tables[] = 'users';

if (!in_array($table, $allowed_tables)) {
    die("Недопустимая таблица.");
}

if ($action === 'add') {
    $fields = array_keys($_POST);
    if (($key = array_search('id', $fields)) !== false) {
        unset($fields[$key]);
    }
    $values = [];
    foreach ($fields as $field) {
        $value = $_POST[$field];
        if ($table == 'users' && $field == 'password') {
            $value = password_hash($value, PASSWORD_DEFAULT);
        }
        $values[] = $value;
    }
    $placeholders = implode(",", array_fill(0, count($values), "?"));
    $field_list = implode(",", $fields);
    $stmt = $pdo->prepare("INSERT INTO `$table` ($field_list) VALUES ($placeholders)");
    $stmt->execute($values);
} elseif ($action === 'edit') {
    if (!isset($_POST['id'])) {
        die("Не указан идентификатор записи.");
    }
    $id = (int)$_POST['id'];

    if ($table === 'users' && $id === 1) {
        die("Нельзя редактировать первую запись в таблице пользователей.");
    }

    $fields = [];
    $values = [];
    foreach ($_POST as $field => $value) {
        if ($field != 'id') {

            if ($table == 'users' && $field == 'password') {
                $value = password_hash($value, PASSWORD_DEFAULT);
            }
            $fields[] = "`$field` = ?";
            $values[] = $value;
        }
    }
    $values[] = $id;
    $field_list = implode(",", $fields);
    $stmt = $pdo->prepare("UPDATE `$table` SET $field_list WHERE id = ?");
    $stmt->execute($values);
} else {
    die("Недопустимое действие.");
}

header("Location: index.php?table=$table");
exit;