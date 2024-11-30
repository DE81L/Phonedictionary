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

$columns = [];
if ($table == 'users') {
    $columns = [
        'username' => ['type' => 'VARCHAR(255)', 'required' => true],
        'password' => ['type' => 'VARCHAR(255)', 'required' => true],
    ];
} else {
    $stmt = $pdo->prepare("SELECT column_name, data_type FROM column_metadata WHERE table_name = ?");
    $stmt->execute([$table]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[$row['column_name']] = ['type' => $row['data_type'], 'required' => true];
    }
}

function validate_input($value, $type) {
    if (strpos($type, 'INT') !== false) {
        return ctype_digit($value);
    } elseif ($type == 'DATE') {
        return (bool)strtotime($value);
    } elseif ($type == 'DATETIME') {
        return (bool)strtotime($value);
    } elseif (strpos($type, 'VARCHAR') !== false || $type == 'TEXT') {
        return !empty($value);
    } else {
        return true;
    }
}

if ($action === 'add') {
    $fields = [];
    $values = [];
    foreach ($columns as $field => $meta) {
        if (!isset($_POST[$field])) {
            die("Поле '$field' не заполнено.");
        }
        $value = $_POST[$field];

        if (!validate_input($value, $meta['type'])) {
            die("Неверное значение для поля '$field'.");
        }

        $fields[] = $field;
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
    foreach ($columns as $field => $meta) {
        if (!isset($_POST[$field])) {
            die("Поле '$field' не заполнено.");
        }
        $value = $_POST[$field];

        if (!validate_input($value, $meta['type'])) {
            die("Неверное значение для поля '$field'.");
        }

        if ($table == 'users' && $field == 'password') {
            $value = password_hash($value, PASSWORD_DEFAULT);
        }
        $fields[] = "`$field` = ?";
        $values[] = $value;
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
?>
<p><a href="index.php">Вернуться на главную страницу</a></p>
