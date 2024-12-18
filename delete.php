<?php
session_start();
require 'db.php';

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

// Получаем данные из запроса
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;
$table = $data['table'] ?? '';
$page = $data['page'] ?? '1';

if (!$id || !$table) {
    echo json_encode(['success' => false, 'message' => 'Отсутствуют необходимые данные']);
    exit;
}

// Проверка существования таблицы
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
    $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true, 'page' => $page]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Не удалось удалить запись']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка БД: ' . $e->getMessage()]);
}
