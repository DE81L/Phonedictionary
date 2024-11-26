<?php
session_start();
require 'db.php';

// Check if standard template exists, create it if it doesn't
$stmt = $pdo->prepare("SELECT * FROM templates WHERE template_name = 'standard_template'");
$stmt->execute();
$template_exists = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$template_exists) {
    $pdo->prepare("INSERT INTO templates (template_name, display_template_name) VALUES (?, ?)")
        ->execute(['standard_template', 'Стандартный шаблон']);

    $columns = [
        ['column_name' => 'floor', 'data_type' => 'VARCHAR(50)', 'display_column_name' => 'Этаж'],
        ['column_name' => 'ip_number', 'data_type' => 'VARCHAR(50)', 'display_column_name' => 'IP номер'],
        ['column_name' => 'name', 'data_type' => 'VARCHAR(100)', 'display_column_name' => 'Название'],
        ['column_name' => 'landline_number', 'data_type' => 'VARCHAR(50)', 'display_column_name' => 'Городской номер'],
    ];

    foreach ($columns as $column) {
        $pdo->prepare("INSERT INTO template_columns (template_name, column_name, data_type, display_column_name) VALUES (?, ?, ?, ?)")
            ->execute(['standard_template', $column['column_name'], $column['data_type'], $column['display_column_name']]);
    }
}

// Check if admin exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = 1");
$stmt->execute();
$admin_exists = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle admin creation
if (!$admin_exists) {
    // If admin does not exist, show modal window to create admin
    $show_create_admin_modal = true;
} else {
    $show_create_admin_modal = false;
}

// Fetch available tables
$tables = [];
$stmt = $pdo->query("SELECT table_name, display_table_name FROM table_metadata");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $tables[$row['table_name']] = $row['display_table_name'];
}

// If user is logged in, add 'users' table to the list
if (isset($_SESSION['user'])) {
    $tables['users'] = 'Пользователи';
}

$current_table = isset($_GET['table']) ? $_GET['table'] : null;

// Fetch quick info content
$stmt = $pdo->query("SELECT content FROM quick_info WHERE id = 1");
$quick_info = $stmt->fetchColumn();

if (!$quick_info) {
    $quick_info = '';
}

// Function to parse custom markup
function parseCustomMarkup($text) {
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    // Replace <br> tags
    $text = str_replace('&lt;br&gt;', '<br>', $text);

    // Replace header tags
    for ($i = 1; $i <= 6; $i++) {
        $text = preg_replace('/&lt;h' . $i . '&gt;(.*?)&lt;\/h' . $i . '&gt;/', '<h' . $i . '>$1</h' . $i . '>', $text);
    }

    // Replace color tags
    $text = preg_replace_callback('/&lt;color:(.*?)&gt;(.*?)&lt;\/color&gt;/s', function($matches) {
        $color = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
        $content = $matches[2];
        if (preg_match('/^#[0-9a-fA-F]{3,6}$/', $color) || preg_match('/^[a-zA-Z]+$/', $color)) {
            return '<span style="color:' . $color . '">' . $content . '</span>';
        } else {
            return '<span style="color:black">' . $content . '</span>';
        }
    }, $text);

    // Replace size tags
    $text = preg_replace_callback('/&lt;s:(\d+)&gt;(.*?)&lt;\/s&gt;/s', function($matches) {
        $size = intval($matches[1]);
        $content = $matches[2];
        return '<span style="font-size:' . $size . 'px">' . $content . '</span>';
    }, $text);

    return $text;
}


$quick_info_html = parseCustomMarkup($quick_info);

// Fetch columns for current table
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

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Телефонный справочник</title>
    <link rel="stylesheet" href="style.css">
</head>
<body data-current-table="<?= htmlspecialchars($current_table ?? '') ?>">
<div class="container">
    <aside class="sidebar">
        <h2>Адресная книга</h2>
        <?php if (isset($_SESSION['user'])): ?>
            <p>Вы вошли как <?= htmlspecialchars($_SESSION['user']) ?>. <a href="logout.php">Выйти</a></p>
        <?php else: ?>
            <p><a href="#" id="loginBtn">Войти</a> для редактирования записей.</p>
        <?php endif; ?>
        <nav>
            <?php if (!empty($tables)): ?>
                <?php foreach ($tables as $table => $display_name): ?>
                    <a href="?table=<?= htmlspecialchars($table) ?>" class="nav-item <?= $current_table == $table ? 'active' : '' ?>">
                        <?= htmlspecialchars($display_name) ?>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Нет доступных таблиц. Создайте новую таблицу или шаблон.</p>
            <?php endif; ?>
        </nav>
        <?php if (isset($_SESSION['user'])): ?>
            <?php if ($current_table && $current_table != 'users'): ?>
                <button class="btn deleteTableBtn">Удалить таблицу</button>
            <?php endif; ?>
            <button class="btn" id="addTableBtn">Создать таблицу по шаблону</button>
            <button class="btn" id="createTemplateBtn">Создать новый шаблон</button>
            <?php if ($current_table): ?>
                <button class="btn" id="addBtn">Добавить запись</button>
            <?php endif; ?>
        <?php endif; ?>
    </aside>

    <main>
        <div class="quick-info">
            <h2>Информация</h2>
            <p id="quickInfoText"><?= $quick_info_html ?></p>
            <?php if (isset($_SESSION['user'])): ?>
                <button id="editQuickInfoBtn">Редактировать информацию</button>
            <?php endif; ?>
        </div>

        <?php if ($current_table && isset($tables[$current_table])): ?>
            <h1>Телефонный справочник: <?= htmlspecialchars($tables[$current_table]) ?></h1>

            <input type="text" id="searchInput" placeholder="Поиск...">

            <table>
                <thead>
                    <tr>
                        <?php foreach ($columns as $col => $display_col): ?>
                            <th><?= htmlspecialchars($display_col) ?><div class="resizer"></div></th>
                        <?php endforeach; ?>
                        <?php if (isset($_SESSION['user'])): ?>
                            <th>Действия</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT * FROM `$current_table`");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                    ?>
                        <tr>
                            <?php foreach ($columns as $col => $display_col): ?>
                                <td><?= htmlspecialchars($row[$col]) ?></td>
                            <?php endforeach; ?>
                            <?php if (isset($_SESSION['user'])): ?>
                                <td>
                                    <?php
                                    $disable_edit = false;
                                    $disable_delete = false;

                                    if ($current_table == 'users' && $row['id'] == 1) {
                                        $disable_edit = true;
                                        $disable_delete = true;
                                    }
                                    ?>
                                    <?php if (!$disable_edit): ?>
                                        <button class="editBtn" data-id="<?= $row['id'] ?>">Редактировать</button>
                                    <?php endif; ?>
                                    <?php if (!$disable_delete): ?>
                                        <button class="deleteBtn" data-id="<?= $row['id'] ?>">Удалить</button>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <h1>Телефонный справочник</h1>
            <p>Выберите таблицу из списка или создайте новую.</p>
        <?php endif; ?>
    </main>
</div>

<!-- Modals -->
<?php include 'modals.php'; ?>

<script src="script.js"></script>
</body>
</html>
