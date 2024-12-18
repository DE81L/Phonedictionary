<?php
require 'db.php';
session_start();

// Параметры запроса
$data = json_decode(file_get_contents('php://input'), true);
$table = $data['table'] ?? '';
$globalQuery = trim($data['globalQuery'] ?? '');
$columnQueries = $data['columnQueries'] ?? [];
$page = isset($data['page']) ? (int)$data['page'] : 1;
$per_page = 50; // фиксированный размер страницы

// Проверяем таблицу
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

// Формируем WHERE
$conditions = [];
$params = [];

// Если есть колоночный поиск, игнорируем глобальный
if (!empty($columnQueries)) {
    foreach ($columnQueries as $col => $val) {
        $conditions[] = "`$col` LIKE ?";
        $params[] = '%'.$val.'%';
    }
} else if (!empty($globalQuery)) {
    // Глобальный поиск по всем колонкам
    $columns = [];
    if ($table == 'users') {
        $columns = ['username','password'];
    } else {
        $stmt = $pdo->prepare("SELECT column_name FROM column_metadata WHERE table_name = ?");
        $stmt->execute([$table]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['column_name'];
        }
    }

    $likeParts = [];
    foreach ($columns as $col) {
        $likeParts[] = "`$col` LIKE ?";
        $params[] = '%'.$globalQuery.'%';
    }
    if (!empty($likeParts)) {
        $conditions[] = '(' . implode(' OR ', $likeParts) . ')';
    }
}

// Формируем SQL
$sql = "SELECT * FROM `$table`";
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

// Сначала получим общее количество
$count_sql = preg_replace('/SELECT \* FROM/', 'SELECT COUNT(*) as cnt FROM', $sql, 1);
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$count_row = $count_stmt->fetch(PDO::FETCH_ASSOC);
$total = (int)$count_row['cnt'];

// Пагинация
$total_pages = ceil($total / $per_page);
if ($page < 1) $page = 1;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
$offset = ($page - 1) * $per_page;

$sql .= " LIMIT $offset, $per_page";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true, 
    'data' => $results,
    'total' => $total,
    'page' => $page,
    'per_page' => $per_page,
    'total_pages' => $total_pages
]);
