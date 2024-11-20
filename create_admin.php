<?php
require 'db.php';

// Создание пользователя 'admin'
$username = 'admin';
$password = '5tR0Ng3s7pAsSwOr9';

// Хешируем пароль
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Проверяем, существует ли пользователь
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->rowCount() > 0) {
    echo "Пользователь уже существует.<br>";
} else {
    $stmt = $pdo->prepare("INSERT INTO users (id, username, password) VALUES (1, ?, ?)");
    $stmt->execute([$username, $hashed_password]);
    echo "Пользователь 'admin' создан.<br>";
}

// Создание первой таблицы с данными здания
$table_name = 'building_1';
$display_table_name = 'Здание 1';

// Проверяем, существует ли таблица
$stmt = $pdo->prepare("SHOW TABLES LIKE ?");
$stmt->execute([$table_name]);
if ($stmt->rowCount() > 0) {
    echo "Таблица '$table_name' уже существует.<br>";
} else {
    // Создаем структуру таблицы
    $create_table_sql = "
    CREATE TABLE `$table_name` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `floor` VARCHAR(50) NOT NULL,
        `ip_number` VARCHAR(50) NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `landline_number` VARCHAR(50) NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    $pdo->exec($create_table_sql);
    echo "Таблица '$table_name' создана.<br>";

    // Добавляем запись в table_metadata
    $stmt = $pdo->prepare("INSERT INTO table_metadata (table_name, display_table_name) VALUES (?, ?)");
    $stmt->execute([$table_name, $display_table_name]);
    echo "Метаданные для таблицы '$table_name' добавлены.<br>";

    // Добавляем колонки в column_metadata
    $columns = [
        ['column_name' => 'id', 'display_column_name' => 'ID'],
        ['column_name' => 'floor', 'display_column_name' => 'Этаж'],
        ['column_name' => 'ip_number', 'display_column_name' => 'IP номер'],
        ['column_name' => 'name', 'display_column_name' => 'Название'],
        ['column_name' => 'landline_number', 'display_column_name' => 'Городской номер'],
    ];

    foreach ($columns as $column) {
        $stmt = $pdo->prepare("INSERT INTO column_metadata (table_name, column_name, display_column_name) VALUES (?, ?, ?)");
        $stmt->execute([$table_name, $column['column_name'], $column['display_column_name']]);
    }
    echo "Метаданные колонок для таблицы '$table_name' добавлены.<br>";
}

echo "Установка завершена.";
?>