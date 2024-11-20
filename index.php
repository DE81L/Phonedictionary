<?php
session_start();
require 'db.php';

// Check if users table exists
try {
    $stmt = $pdo->query("SELECT 1 FROM users LIMIT 1");
} catch (Exception $e) {
    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

// Check if there is at least one user
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$user_count = $stmt->fetchColumn();

if ($user_count == 0) {
    // No users exist, display form to create admin user
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert admin user with id = 1
        $stmt = $pdo->prepare("INSERT INTO users (id, username, password) VALUES (1, ?, ?)");
        $stmt->execute([$username, $hashed_password]);

        $_SESSION['user'] = $username;
        header("Location: index.php");
        exit();
    }

    // Display form to create admin user
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
        <h1>Создание администратора</h1>
        <form method="post">
            <label>Имя пользователя: <input type="text" name="username" required></label><br>
            <label>Пароль: <input type="password" name="password" required></label><br>
            <button type="submit">Создать администратора</button>
        </form>
    </div>
    </body>
    </html>
    <?php
    exit();
}

// Check if necessary tables exist, create if not
$requiredTables = ['table_metadata', 'column_metadata', 'quick_info', 'templates', 'template_columns'];
foreach ($requiredTables as $table) {
    try {
        $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
    } catch (Exception $e) {
        switch ($table) {
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

// Get list of tables and their display names
$tables = [];
$stmt = $pdo->query("SELECT table_name, display_table_name FROM table_metadata");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $tables[$row['table_name']] = $row['display_table_name'];
}

// If user is logged in, add 'users' table for admin
if (isset($_SESSION['user'])) {
    $tables['users'] = 'Пользователи';
}

// Determine current table
$current_table = isset($_GET['table']) ? $_GET['table'] : null;

// If there are no tables
if (empty($tables)) {
    if (isset($_SESSION['user'])) {
        // User is logged in, display options to create a new table
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
                <!-- Sidebar -->
                <aside class="sidebar">
                    <h2>Адресная книга</h2>
                    <p>Вы вошли как <?= htmlspecialchars($_SESSION['user']) ?>. <a href="logout.php">Выйти</a></p>
                    <nav>
                        <p>Нет доступных таблиц. Создайте новую таблицу.</p>
                    </nav>
                    <button class="btn" id="addTableBtn">Добавить таблицу</button>
                </aside>
                <!-- Main content -->
                <main>
                    <!-- Quick info block -->
                    <div class="quick-info">
                        <h2>Информация</h2>
                        <p id="quickInfoText"></p>
                        <?php if (isset($_SESSION['user'])): ?>
                            <button id="editQuickInfoBtn">Редактировать информацию</button>
                        <?php endif; ?>
                    </div>
                </main>
            </div>

            <!-- Modals -->
            <!-- Include your modal code here -->

            <script src="script.js"></script>
        </body>
        </html>
        <?php
        exit();
    } else {
        // User is not logged in, suggest logging in
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

            <!-- Login Modal -->
            <!-- Include your login modal code here -->

            <script src="script.js"></script>
        </body>
        </html>
        <?php
        exit();
    }
}

// Fetch quick_info content
$stmt = $pdo->query("SELECT content FROM quick_info WHERE id = 1");
$quick_info = $stmt->fetchColumn();

if (!$quick_info) {
    $quick_info = '';
}

function parseCustomMarkup($text) {
    // Escape HTML special characters
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    // Handle <br> tags
    $text = str_replace('&lt;br&gt;', '<br>', $text);

    // Handle <h1> and <h2> tags
    $text = preg_replace('/&lt;h([12])&gt;(.*?)&lt;\/h\1&gt;/', '<h$1>$2</h$1>', $text);

    // Handle <color:colorname>...</color>
    $text = preg_replace_callback('/&lt;color:(.*?)&gt;(.*?)&lt;\/color&gt;/s', function($matches) {
        $color = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
        $content = $matches[2];
        return '<span style="color:' . $color . '">' . $content . '</span>';
    }, $text);

    return $text;
}

$quick_info_html = parseCustomMarkup($quick_info);

// Get columns for current table
$columns = [];

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
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Телефонный справочник</title>
    <link rel="stylesheet" href="style.css">
</head>
<body data-current-table="<?= htmlspecialchars($current_table) ?>">
<div class="container">
    <!-- Sidebar -->
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
            <?php if ($current_table != 'users'): ?>
                <button class="btn deleteTableBtn">Удалить таблицу</button>
            <?php endif; ?>
            <button class="btn" id="addTableBtn">Добавить таблицу</button>
            <button class="btn" id="addBtn">Добавить запись</button>
        <?php endif; ?>
    </aside>

    <!-- Main content -->
    <main>
        <!-- Quick info block -->
        <div class="quick-info">
            <h2>Информация</h2>
            <p id="quickInfoText"><?= $quick_info_html ?></p>
            <?php if (isset($_SESSION['user'])): ?>
                <button id="editQuickInfoBtn">Редактировать информацию</button>
            <?php endif; ?>
        </div>

        <h1>Телефонный справочник: <?= htmlspecialchars($tables[$current_table]) ?></h1>

        <!-- Search input -->
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
    </main>
</div>

<!-- Modals -->
<!-- Quick Info Modal -->
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
                    <li>Цветной текст: <code>&lt;color:цвет&gt;Текст&lt;/color&gt;</code></li>
                    <li>Перенос строки: <code>&lt;br&gt;</code></li>
                </ul>
                <h3>Примеры:</h3>
                <p><code>&lt;h1&gt;Большой заголовок&lt;/h1&gt;</code> будет отображаться как:</p>
                <h1>Большой заголовок</h1>
                <p><code>&lt;color:red&gt;Красный текст&lt;/color&gt;</code> будет отображаться как:</p>
                <span style="color:red;">Красный текст</span>
            </div>
        </div>
    </div>
</div>

<!-- Table Modal -->
<div id="tableModal" class="modal">
    <div class="modal-content">
        <span class="close table-close">&times;</span>
        <h2>Добавить новую таблицу</h2>
        <form id="tableForm" method="post" action="add_table.php">
            <label>Имя таблицы (латиницей): <input type="text" name="table_name" required></label><br>
            <label>Отображаемое имя таблицы: <input type="text" name="display_table_name" required></label><br>
            <label>Выберите шаблон:
                <select name="template_name" id="templateSelect">
                    <option value="">-- Выберите шаблон --</option>
                    <?php
                    $stmt = $pdo->query("SELECT template_name, display_template_name FROM templates");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                    ?>
                        <option value="<?= htmlspecialchars($row['template_name']) ?>"><?= htmlspecialchars($row['display_template_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </label><br>
            <div id="columnsContainer" style="display: none;">
                <h3>Колонки:</h3>
                <button type="button" id="addColumnBtn">Добавить колонку</button>
                <!-- Columns will be added here dynamically -->
            </div>
            <div id="templateDetails" style="display: none;">
                <label>Имя шаблона (латиницей): <input type="text" name="new_template_name"></label><br>
                <label>Отображаемое имя шаблона: <input type="text" name="new_display_template_name"></label><br>
                <label><input type="checkbox" name="create_template"> Создать шаблон на основе этой таблицы</label><br>
            </div>
            <button type="submit">Создать таблицу</button>
        </form>
    </div>
</div>

<!-- Template Modal -->
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

<!-- Login Modal -->
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

<!-- Add/Edit Record Modal -->
<div id="modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modalTitle">Добавить запись</h2>
        <form id="modalForm" method="post">
            <input type="hidden" name="id" id="recordId">
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
        </form>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>
