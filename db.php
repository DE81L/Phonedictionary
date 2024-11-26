<?php
$host = 'localhost';
$dbname = 'phone_directory';
$username = 'root';  // Replace with your own credentials
$password = 'root';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");

    // Create necessary tables if they don't exist
    $requiredTables = ['users', 'table_metadata', 'column_metadata', 'quick_info', 'templates', 'template_columns'];

    foreach ($requiredTables as $table) {
        switch ($table) {
            case 'users':
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS users (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        username VARCHAR(255) NOT NULL UNIQUE,
                        password VARCHAR(255) NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");
                break;
            case 'table_metadata':
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS table_metadata (
                        table_name VARCHAR(255) PRIMARY KEY,
                        display_table_name VARCHAR(255) NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");
                break;
            case 'column_metadata':
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS column_metadata (
                        table_name VARCHAR(255) NOT NULL,
                        column_name VARCHAR(255) NOT NULL,
                        display_column_name VARCHAR(255) NOT NULL,
                        PRIMARY KEY (table_name, column_name)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");
                break;
            case 'quick_info':
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS quick_info (
                        id INT PRIMARY KEY DEFAULT 1,
                        content TEXT
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");
                break;
            case 'templates':
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS templates (
                        template_name VARCHAR(255) PRIMARY KEY,
                        display_template_name VARCHAR(255) NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");
                break;
            case 'template_columns':
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS template_columns (
                        template_name VARCHAR(255) NOT NULL,
                        column_name VARCHAR(255) NOT NULL,
                        data_type VARCHAR(255) NOT NULL,
                        display_column_name VARCHAR(255) NOT NULL,
                        PRIMARY KEY (template_name, column_name)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");
                break;
        }
    }

} catch (PDOException $e) {
    die("Connection error: " . $e->getMessage());
}
?>
