<?php
session_start();
require 'db.php';

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

// Получение данных
$data = json_decode(file_get_contents('php://input'), true);
$ids = $data['ids'] ?? [];
$table = $data['table'] ?? '';
$page = $data['page'] ?? '1';

if (empty($ids) || empty($table)) {
    echo json_encode(['success' => false, 'message' => 'Отсутствуют необходимые данные']);
    exit;
}

// Проверка таблицы
$allowed_tables = [];
$stmt = $pdo->query("SELECT table_name FROM table_metadata");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $allowed_tables[] = $row['table_name'];
}
$allowed_tables[] = 'users';

if (!in_array($table, $allowed_tables)) {
    echo json_encode(['success' => false, 'message' => 'Недопустимая таблица']);
    exit;
}

try {
    // Защита от удаления администратора
    if ($table === 'users') {
        $ids = array_filter($ids, function($id) {
            return $id != 1;
        });
    }

    if (!empty($ids)) {
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "DELETE FROM `$table` WHERE id IN ($placeholders)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);
        
        echo json_encode(['success' => true, 'page' => $page]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Нет записей для удаления']);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка БД: ' . $e->getMessage()]);
}
