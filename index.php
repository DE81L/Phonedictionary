<?php
session_start();
require 'db.php';

$requiredTables = ['users', 'table_metadata', 'column_metadata', 'quick_info', 'templates', 'template_columns'];
foreach ($requiredTables as $table) {
    try {
        $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
    } catch (Exception $e) {
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
}

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

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = 1");
$stmt->execute();
$admin_exists = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin_exists) {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Создание администратора</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
    <div class="container">
        <div id="createAdminModal" class="modal" style="display: flex;">
            <div class="modal-content">
                <h2>Создание администратора</h2>
                <form id="createAdminForm" method="post" action="create_admin.php">
                    <label>Имя пользователя: <input type="text" name="username" required></label><br>
                    <label>Пароль: <input type="password" name="password" required></label><br>
                    <button type="submit">Создать администратора</button>
                </form>
            </div>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit();
}

$tables = [];
$stmt = $pdo->query("SELECT table_name, display_table_name FROM table_metadata");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $tables[$row['table_name']] = $row['display_table_name'];
}

if (isset($_SESSION['user'])) {
    $tables['users'] = 'Пользователи';
}

$current_table = isset($_GET['table']) ? $_GET['table'] : null;

if (empty($tables)) {
    if (isset($_SESSION['user'])) {
        ?>
        <!DOCTYPE html>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <title>Телефонный справочник</title>
            <link rel="stylesheet" href="style.css">
        </head>
        <body data-current-table="">
        <div class="container">
            <aside class="sidebar">
                <h2>Адресная книга</h2>
                <p>Вы вошли как <?= htmlspecialchars($_SESSION['user']) ?>. <a href="logout.php">Выйти</a></p>
                <nav>
                    <p>Нет доступных таблиц. Создайте новую таблицу или шаблон.</p>
                </nav>
                <button class="btn" id="addTableBtn">Создать таблицу по шаблону</button>
                <button class="btn" id="createTemplateBtn">Создать новый шаблон</button>
            </aside>
            <main>
                <div class="quick-info">
                    <h2>Информация</h2>
                    <p id="quickInfoText"></p>
                    <button id="editQuickInfoBtn">Редактировать информацию</button>
                </div>
            </main>
        </div>

        <div id="tableModal" class="modal">
            <div class="modal-content">
                <span class="close table-close">&times;</span>
                <h2>Создать таблицу по шаблону</h2>
                <form id="tableForm" method="post" action="add_table.php">
                    <label>Отображаемое имя таблицы: <input type="text" name="display_table_name" required></label><br>
                    <input type="hidden" name="table_name" value="building_1">
                    <label>Выберите шаблон:
                        <select name="template_name" id="templateSelect" required>
                            <option value="">-- Выберите шаблон --</option>
                            <?php
                            $stmt = $pdo->query("SELECT template_name, display_template_name FROM templates");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                                <option value="<?= htmlspecialchars($row['template_name']) ?>"><?= htmlspecialchars($row['display_template_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </label><br>
                    <button type="submit">Создать таблицу</button>
                </form>
            </div>
        </div>

        <div id="templateModal" class="modal">
            <div class="modal-content">
                <span class="close template-close">&times;</span>
                <h2>Создать новый шаблон</h2>
                <form id="templateForm" method="post" action="add_template.php">
                    <label>Имя шаблона (латиницей): <input type="text" name="template_name" required></label><br>
                    <label>Отображаемое имя шаблона: <input type="text" name="display_template_name" required></label><br>
                    <div id="columnsContainer">
                        <h3>Колонки:</h3>
                        <button type="button" id="addColumnBtn">Добавить колонку</button>
                    </div>
                    <button type="submit">Создать шаблон</button>
                </form>
            </div>
        </div>

        <div id="quickInfoModal" class="modal">
            <div class="modal-content">
                <span class="close quickInfo-close">&times;</span>
                <h2>Редактировать информацию</h2>
                <div class="modal-body">
                    <form id="quickInfoForm" method="post" action="update_quick_info.php">
                        <textarea name="content" id="quickInfoContent" rows="5" required></textarea><br>
                        <button type="submit">Сохранить</button>
                    </form>
                    <div class="markup-help">
                        <h3>Доступные функции:</h3>
                        <ul>
                            <li>Заголовок 1: <code>&lt;h1&gt;Текст&lt;/h1&gt;</code></li>
                            <li>Заголовок 2: <code>&lt;h2&gt;Текст&lt;/h2&gt;</code></li>
                            <li>Заголовок 3: <code>&lt;h3&gt;Текст&lt;/h3&gt;</code></li>
                            <li>Заголовок 4: <code>&lt;h4&gt;Текст&lt;/h4&gt;</code></li>
                            <li>Заголовок 5: <code>&lt;h5&gt;Текст&lt;/h5&gt;</code></li>
                            <li>Заголовок 6: <code>&lt;h6&gt;Текст&lt;/h6&gt;</code></li>
                            <li>Цветной текст: <code>&lt;color:цвет&gt;Текст&lt;/color&gt;</code> (цвет может быть названием или HEX кодом)</li>
                            <li>Перенос строки: <code>&lt;br&gt;</code></li>
                        </ul>
                        <h3>Примеры:</h3>
                        <p><code>&lt;h1&gt;Большой заголовок&lt;/h1&gt;</code> будет отображаться как:</p>
                        <h1>Большой заголовок</h1>
                        <p><code>&lt;color:#FF0000&gt;Красный текст&lt;/color&gt;</code> будет отображаться как:</p>
                        <span style="color:#FF0000;">Красный текст</span>
                    </div>
                </div>
            </div>
        </div>

        <script src="script.js"></script>
        </body>
        </html>
        <?php
        exit();
    } else {
        ?>
        <!DOCTYPE html>
        <html lang="ru">
        <head>
            <meta charset="UTF-8">
            <title>Телефонный справочник</title>
            <link rel="stylesheet" href="style.css">
        </head>
        <body>
        <div class="container">
            <p>Нет доступных таблиц. Пожалуйста, <a href="#" id="loginBtn">войдите</a> для создания новой таблицы.</p>
        </div>

        <div id="loginModal" class="modal" style="display: flex;">
            <div class="modal-content">
                <span class="close login-close">&times;</span>
                <h2>Вход</h2>
                <form method="post" action="login.php">
                    <label>Имя пользователя: <input type="text" name="username" required></label><br>
                    <label>Пароль: <input type="password" name="password" required></label><br>
                    <button type="submit">Войти</button>
                </form>
            </div>
        </div>

        <script src="script.js"></script>
        </body>
        </html>
        <?php
        exit();
    }
}

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
$quick_info = $stmt->fetchColumn();

if (!$quick_info) {
    $quick_info = '';
}

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
            <?php foreach ($tables as $table => $display_name): ?>
                <a href="?table=<?= htmlspecialchars($table) ?>" class="nav-item <?= $current_table == $table ? 'active' : '' ?>">
                    <?= htmlspecialchars($display_name) ?>
                </a>
            <?php endforeach; ?>
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

<div id="modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modalTitle">Добавить запись</h2>
        <form id="modalForm" method="post">
            <input type="hidden" name="id" id="recordId">
            <?php if ($columns): ?>
                <?php foreach ($columns as $col => $display_col): ?>
                    <?php if ($col != 'id'): ?>
                        <label><?= htmlspecialchars($display_col) ?>:
                            <?php if ($current_table == 'users' && $col == 'password'): ?>
                                <input type="password" name="<?= $col ?>" required>
                            <?php else: ?>
                                <input type="text" name="<?= $col ?>" required>
                            <?php endif; ?>
                        </label><br>
                    <?php endif; ?>
                <?php endforeach; ?>
                <button type="submit">Сохранить</button>
            <?php endif; ?>
        </form>
    </div>
</div>

<div id="quickInfoModal" class="modal">
    <div class="modal-content">
        <span class="close quickInfo-close">&times;</span>
        <h2>Редактировать информацию</h2>
        <div class="modal-body">
            <form id="quickInfoForm" method="post" action="update_quick_info.php">
                <textarea name="content" id="quickInfoContent" rows="5" required></textarea><br>
                <button type="submit">Сохранить</button>
            </form>
            <div class="markup-help">
                <h3>Доступные функции:</h3>
                <ul>
                    <li>Заголовок 1: <code>&lt;h1&gt;Текст&lt;/h1&gt;</code></li>
                    <li>Заголовок 2: <code>&lt;h2&gt;Текст&lt;/h2&gt;</code></li>
                    <li>Заголовок 3: <code>&lt;h3&gt;Текст&lt;/h3&gt;</code></li>
                    <li>Заголовок 4: <code>&lt;h4&gt;Текст&lt;/h4&gt;</code></li>
                    <li>Заголовок 5: <code>&lt;h5&gt;Текст&lt;/h5&gt;</code></li>
                    <li>Заголовок 6: <code>&lt;h6&gt;Текст&lt;/h6&gt;</code></li>
                    <li>Цветной текст: <code>&lt;color:цвет&gt;Текст&lt;/color&gt;</code> (цвет может быть названием или HEX кодом)</li>
                    <li>Перенос строки: <code>&lt;br&gt;</code></li>
                </ul>
                <h3>Примеры:</h3>
                <p><code>&lt;h1&gt;Большой заголовок&lt;/h1&gt;</code> будет отображаться как:</p>
                <h1>Большой заголовок</h1>
                <p><code>&lt;color:#FF0000&gt;Красный текст&lt;/color&gt;</code> будет отображаться как:</p>
                <span style="color:#FF0000;">Красный текст</span>
            </div>
        </div>
    </div>
</div>

<div id="tableModal" class="modal">
    <div class="modal-content">
        <span class="close table-close">&times;</span>
        <h2>Создать таблицу по шаблону</h2>
        <form id="tableForm" method="post" action="add_table.php">
            <label>Отображаемое имя таблицы: <input type="text" name="display_table_name" required></label><br>
            <input type="hidden" name="table_name" value="building_1">
            <label>Выберите шаблон:
                <select name="template_name" id="templateSelect" required>
                    <option value="">-- Выберите шаблон --</option>
                    <?php
                    $stmt = $pdo->query("SELECT template_name, display_template_name FROM templates");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                    ?>
                        <option value="<?= htmlspecialchars($row['template_name']) ?>"><?= htmlspecialchars($row['display_template_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </label><br>
            <button type="submit">Создать таблицу</button>
        </form>
    </div>
</div>

<div id="templateModal" class="modal">
    <div class="modal-content">
        <span class="close template-close">&times;</span>
        <h2>Создать новый шаблон</h2>
        <form id="templateForm" method="post" action="add_template.php">
            <label>Имя шаблона (латиницей): <input type="text" name="template_name" required></label><br>
            <label>Отображаемое имя шаблона: <input type="text" name="display_template_name" required></label><br>
            <div id="columnsContainer">
                <h3>Колонки:</h3>
                <button type="button" id="addColumnBtn">Добавить колонку</button>
            </div>
            <button type="submit">Создать шаблон</button>
        </form>
    </div>
</div>

<div id="loginModal" class="modal">
    <div class="modal-content">
        <span class="close login-close">&times;</span>
        <h2>Вход</h2>
        <form method="post" action="login.php">
            <label>Имя пользователя: <input type="text" name="username" required></label><br>
            <label>Пароль: <input type="password" name="password" required></label><br>
            <button type="submit">Войти</button>
        </form>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>
