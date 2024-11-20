<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $table_name = $_POST['table_name'];
    $display_table_name = $_POST['display_table_name'];
    $template_name = $_POST['template_name'];

    // Validate table name
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table_name)) {
        die("Недопустимое имя таблицы.");
    }

    // Check if table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$table_name]);
    if ($stmt->rowCount() > 0) {
        die("Таблица уже существует.");
    }

    if (!empty($template_name)) {
        // Create table based on selected template
        $stmt = $pdo->prepare("SELECT column_name, data_type, display_column_name FROM template_columns WHERE template_name = ?");
        $stmt->execute([$template_name]);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($columns)) {
            die("Шаблон не найден или не содержит колонок.");
        }

        $columns_sql = [];
        foreach ($columns as $column) {
            $columns_sql[] = "`{$column['column_name']}` {$column['data_type']} NOT NULL";
        }

        // Add id column
        array_unshift($columns_sql, "`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY");

        $create_table_sql = "CREATE TABLE `$table_name` (" . implode(", ", $columns_sql) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($create_table_sql);

        // Insert into table_metadata
        $stmt = $pdo->prepare("INSERT INTO table_metadata (table_name, display_table_name) VALUES (?, ?)");
        $stmt->execute([$table_name, $display_table_name]);

        // Insert into column_metadata
        foreach ($columns as $column) {
            $stmt = $pdo->prepare("INSERT INTO column_metadata (table_name, column_name, display_column_name) VALUES (?, ?, ?)");
            $stmt->execute([$table_name, $column['column_name'], $column['display_column_name']]);
        }
    } else {
        // Handle table creation via form
        // Retrieve columns from form data
        $columns = $_POST['columns'];
        if (empty($columns)) {
            die("Необходимо указать колонки для создания таблицы.");
        }

        $columns_sql = [];
        $column_data = [];
        foreach ($columns as $column) {
            $column_name = $column['name'];
            $data_type = $column['type'];
            $display_column_name = $column['display_name'];

            if (!preg_match('/^[a-zA-Z0-9_]+$/', $column_name)) {
                die("Недопустимое имя колонки: $column_name");
            }

            // Validate data type
            $valid_data_types = ['INT', 'VARCHAR(255)', 'TEXT', 'DATE', 'DATETIME'];
            if (!in_array($data_type, $valid_data_types)) {
                die("Недопустимый тип данных для колонки: $data_type");
            }

            $columns_sql[] = "`$column_name` $data_type NOT NULL";

            // Prepare for column_metadata
            $column_data[] = [
                'column_name' => $column_name,
                'display_column_name' => $display_column_name,
            ];
        }

        // Add id column
        array_unshift($columns_sql, "`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY");

        $create_table_sql = "CREATE TABLE `$table_name` (" . implode(", ", $columns_sql) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($create_table_sql);

        // Insert into table_metadata
        $stmt = $pdo->prepare("INSERT INTO table_metadata (table_name, display_table_name) VALUES (?, ?)");
        $stmt->execute([$table_name, $display_table_name]);

        // Insert into column_metadata
        foreach ($column_data as $column) {
            $stmt = $pdo->prepare("INSERT INTO column_metadata (table_name, column_name, display_column_name) VALUES (?, ?, ?)");
            $stmt->execute([$table_name, $column['column_name'], $column['display_column_name']]);
        }

        // Optionally, create a new template based on this table
        if (isset($_POST['create_template']) && $_POST['create_template'] == 'on') {
            $new_template_name = $_POST['new_template_name'];
            $new_display_template_name = $_POST['new_display_template_name'];

            if (!preg_match('/^[a-zA-Z0-9_]+$/', $new_template_name)) {
                die("Недопустимое имя шаблона.");
            }

            // Insert into templates
            $stmt = $pdo->prepare("INSERT INTO templates (template_name, display_template_name) VALUES (?, ?)");
            $stmt->execute([$new_template_name, $new_display_template_name]);

            // Insert into template_columns
            foreach ($columns as $column) {
                $stmt = $pdo->prepare("INSERT INTO template_columns (template_name, column_name, data_type, display_column_name) VALUES (?, ?, ?, ?)");
                $stmt->execute([$new_template_name, $column['name'], $column['type'], $column['display_name']]);
            }
        }
    }

    header("Location: index.php?table=$table_name");
    exit;
}
?>