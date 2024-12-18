<?php
session_start();
require 'db.php';

// Проверяем, существует ли шаблон 'standard_template'
$stmt = $pdo->prepare("SELECT * FROM templates WHERE template_name = 'standard_template'");
$stmt->execute();
$template_exists = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$template_exists) {
    $pdo->prepare("INSERT INTO templates (template_name, display_template_name) VALUES (?, ?)")
        ->execute(['standard_template', 'Стандартный шаблон']);

    $columns_def = [
        ['column_name' => 'floor', 'data_type' => 'VARCHAR(255)', 'display_column_name' => 'Этаж'],
        ['column_name' => 'ip_number', 'data_type' => 'VARCHAR(255)', 'display_column_name' => 'IP номер'],
        ['column_name' => 'name', 'data_type' => 'VARCHAR(255)', 'display_column_name' => 'Название'],
        ['column_name' => 'landline_number', 'data_type' => 'VARCHAR(255)', 'display_column_name' => 'Городской номер'],
    ];

    foreach ($columns_def as $column) {
        $pdo->prepare("INSERT INTO template_columns (template_name, column_name, data_type, display_column_name) VALUES (?, ?, ?, ?)")
            ->execute(['standard_template', $column['column_name'], $column['data_type'], $column['display_column_name']]);
    }
}

// Проверка существования администратора
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = 1");
$stmt->execute();
$admin_exists = $stmt->fetch(PDO::FETCH_ASSOC);

$show_create_admin_modal = !$admin_exists;

// Получаем список таблиц
$tables = [];
$stmt = $pdo->query("SELECT table_name, display_table_name FROM table_metadata");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $tables[$row['table_name']] = $row['display_table_name'];
}

if (isset($_SESSION['user'])) {
    $tables['users'] = 'Пользователи';
}

$current_table = isset($_GET['table']) ? $_GET['table'] : null;

$stmt = $pdo->query("SELECT content FROM quick_info WHERE id = 1");
$quick_info = $stmt->fetchColumn() ?: '';

// Функция для обработки разметки
function parseCustomMarkup($text) {
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $text = str_replace('&lt;br&gt;', '<br>', $text);

    for ($i = 1; $i <= 6; $i++) {
        $text = preg_replace('/&lt;h' . $i . '&gt;(.*?)&lt;\/h' . $i . '&gt;/', '<h' . $i . '>$1</h' . $i . '>', $text);
    }

    $text = preg_replace_callback('/&lt;color:(.*?)&gt;(.*?)&lt;\/color&gt;/s', function($matches) {
        $color = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
        $content = $matches[2];
        if (preg_match('/^#[0-9a-fA-F]{3,6}$/', $color) || preg_match('/^[a-zA-Z]+$/', $color)) {
            return '<span style="color:' . $color . '">' . $content . '</span>';
        } else {
            return '<span style="color:black">' . $content . '</span>';
        }
    }, $text);

    $text = preg_replace_callback('/&lt;s:(\d+)&gt;(.*?)&lt;\/s&gt;/s', function($matches) {
        $size = intval($matches[1]);
        $content = $matches[2];
        return '<span style="font-size:' . $size . 'px">' . $content . '</span>';
    }, $text);

    return $text;
}

$quick_info_html = parseCustomMarkup($quick_info);

$columns = [];

if ($current_table && isset($tables[$current_table])) {
    if ($current_table == 'users') {
        $columns = [
            'id' => 'ID',
            'username' => 'Имя пользователя',
            'password' => 'Пароль (хешированный)'
        ];
    } else {
        $stmt = $pdo->prepare("SELECT column_name, display_column_name FROM column_metadata WHERE table_name = ?");
        $stmt->execute([$current_table]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[$row['column_name']] = $row['display_column_name'];
        }
    }
}

// Пагинация - при первоначальной загрузке
if ($current_table) {
    $records_per_page = 50;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $records_per_page;

    $total_records_stmt = $pdo->query("SELECT COUNT(*) FROM `$current_table`");
    $total_records = $total_records_stmt->fetchColumn();
    $total_pages = ceil($total_records / $records_per_page);
}
?>
<!DOCTYPE html>
<html lang="ru" class="sidebar-expanded">
<head>
    <meta charset="UTF-8">
    <title>Телефонный справочник</title>
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body data-current-table="<?= htmlspecialchars($current_table ?? '') ?>">
<script>
    var colNames = <?php echo json_encode(array_keys($columns ?? [])); ?>;
    var actionColumn = <?php echo isset($_SESSION['user']) ? 'true' : 'false'; ?>;
</script>
<div class="container-fluid">
    <div class="row">
        <aside class="sidebar p-3">
            <h2 class="text-success">Адресная книга</h2>
            <?php if (isset($_SESSION['user'])): ?>
                <p>Вы вошли как <?= htmlspecialchars($_SESSION['user']) ?>. <a href="logout.php">Выйти</a></p>
            <?php else: ?>
                <p><a href="#" id="loginBtn">Войти</a> для редактирования записей.</p>
            <?php endif; ?>
            <nav class="nav flex-column">
                <?php if (!empty($tables)): ?>
                    <?php foreach ($tables as $table => $display_name): ?>
                        <a href="?table=<?= htmlspecialchars($table) ?>" class="btn btn-table w-100 mb-2 <?= $current_table == $table ? 'active' : '' ?>">
                            <?= htmlspecialchars($display_name) ?>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Нет доступных таблиц. Создайте новую таблицу или шаблон.</p>
                <?php endif; ?>
            </nav>
            <?php if (isset($_SESSION['user'])): ?>
                <?php if ($current_table && $current_table != 'users'): ?>
                    <button class="btn btn-danger w-100 mt-2 deleteTableBtn">Удалить таблицу</button>
                <?php endif; ?>
                <button class="btn btn-primary w-100 mt-2" id="addTableBtn">Создать таблицу по шаблону</button>
                <button class="btn btn-secondary w-100 mt-2" id="createTemplateBtn">Создать новый шаблон</button>
                <?php if ($current_table): ?>
                    <button class="btn btn-add w-100 mt-2" id="addBtn">Добавить запись</button>
                <?php endif; ?>
            <?php endif; ?>
        </aside>

        <main class="main-content">
            <div class="quick-info p-3 mb-4 rounded">
                <h2>Информация</h2>
                <p id="quickInfoText"><?= $quick_info_html ?></p>
                <?php if (isset($_SESSION['user'])): ?>
                    <button class="btn btn-light" id="editQuickInfoBtn">Редактировать информацию</button>
                <?php endif; ?>
            </div>

            <?php if ($current_table && isset($tables[$current_table])): ?>
                <h1>Телефонный справочник: <?= htmlspecialchars($tables[$current_table]) ?></h1>
                
                <!-- Блок для поиска -->
                <div class="search-container mb-3">
                    <div class="global-search mb-2">
                        <input type="text" id="globalSearchInput" class="form-control" placeholder="Поиск по всей таблице...">
                    </div>
                    <div id="columnSearchContainer" class="mb-2" style="display:none;">
                        <!-- Поля для поиска по столбцам будут добавляться динамически -->
                    </div>
                    <button id="applySearchBtn" class="btn btn-info">Поиск</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTable">
                        <thead class="table-light">
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <?php foreach ($columns as $col => $display_col): ?>
                                    <th>
                                        <?= htmlspecialchars($display_col) ?>
                                        <i class="bi bi-search column-search-icon" data-column="<?= htmlspecialchars($col) ?>" style="cursor:pointer; margin-left:5px;"></i>
                                        <div class="resizer"></div>
                                    </th>
                                <?php endforeach; ?>
                                <?php if (isset($_SESSION['user'])): ?>
                                    <th>Действия</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($current_table) {
                                $stmt = $pdo->query("SELECT * FROM `$current_table` LIMIT $offset, $records_per_page");
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                                <tr>
                                    <td><input type="checkbox" class="record-checkbox" data-id="<?= $row['id']; ?>"></td>
                                    <?php foreach ($columns as $col => $display_col): ?>
                                        <td><?= htmlspecialchars($row[$col]) ?></td>
                                    <?php endforeach; ?>
                                    <?php if (isset($_SESSION['user'])): ?>
                                        <td>
                                            <?php
                                            $disable_edit = ($current_table == 'users' && $row['id'] == 1);
                                            $disable_delete = ($current_table == 'users' && $row['id'] == 1);
                                            ?>
                                            <div class="btn-group" role="group">
                                                <?php if (!$disable_edit): ?>
                                                    <button class="btn btn-sm btn-edit action-btn editBtn" data-id="<?= $row['id'] ?>">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if (!$disable_delete): ?>
                                                    <button class="btn btn-sm btn-delete action-btn deleteBtn" data-id="<?= $row['id'] ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endwhile; } ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($current_table && $total_pages > 1): ?>
                    <nav aria-label="Навигация по страницам" id="paginationNav">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?table=<?= urlencode($current_table) ?>&page=<?= ($page - 1) ?>">Предыдущая</a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?table=' . urlencode($current_table) . '&page=1">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }

                            for ($i = $start_page; $i <= $end_page; $i++) {
                                echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">';
                                echo '<a class="page-link" href="?table=' . urlencode($current_table) . '&page=' . $i . '">' . $i . '</a>';
                                echo '</li>';
                            }

                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?table=' . urlencode($current_table) . '&page=' . $total_pages . '">' . $total_pages . '</a></li>';
                            }
                            ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?table=<?= urlencode($current_table) ?>&page=<?= ($page + 1) ?>">Следующая</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <h1>Телефонный справочник</h1>
                <p>Выберите таблицу из списка или создайте новую.</p>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'modals.php'; ?>

<div class="floating-buttons">
    <!-- Кнопка скрытия/показа боковой панели -->
    <button id="floatingSidebarToggleBtn" class="float-btn">
        <i class="bi bi-layout-sidebar-inset"></i>
    </button>
    <!-- Кнопка удаления записей -->
    <button id="floatingDeleteBtn" class="float-btn" style="display:none;">
        <i class="bi bi-trash-fill"></i>
    </button>
</div>


<script
    src="https://code.jquery.com/jquery-3.6.0.min.js"
></script>
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
></script>
<script src="script.js"></script>
</body>
</html>
