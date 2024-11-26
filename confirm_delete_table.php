<?php
session_start();
require 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['table'])) {
    die('Таблица не указана.');
}

$table = $_GET['table'];

if ($table == 'users') {
    die('Нельзя удалить таблицу пользователей.');
}

$stmt = $pdo->prepare("SELECT display_table_name FROM table_metadata WHERE table_name = ?");
$stmt->execute([$table]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die('Таблица не найдена.');
}

$display_table_name = $row['display_table_name'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['cancel'])) {
        header('Location: index.php?table=' . urlencode($table));
        exit;
    }

    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->execute([$_SESSION['user']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        try {
            $pdo->beginTransaction();

            $pdo->exec("DROP TABLE IF EXISTS `$table`");

            $stmt = $pdo->prepare("DELETE FROM column_metadata WHERE table_name = ?");
            $stmt->execute([$table]);
            $stmt = $pdo->prepare("DELETE FROM table_metadata WHERE table_name = ?");
            $stmt->execute([$table]);

            $pdo->commit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } finally {
            header('Location: index.php');
            exit;
        }
    } else {
        $error = 'Неверный пароль.';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Подтверждение удаления таблицы</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="confirmation">
        <div class="warning">
            <h2 style="color: red;">Вы удаляете таблицу <?= htmlspecialchars($display_table_name) ?></h2>
            <p>Эта операция необратима. Для подтверждения введите ваш пароль.</p>
            <?php if (isset($error)): ?>
                <p style="color: red;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <form method="post">
                <label>Пароль: <input type="password" name="password" required></label><br>
                <button type="submit">Удалить таблицу</button>
                <button type="submit" name="cancel">Отмена</button>
            </form>
            <p><a href="index.php">Вернуться на главную страницу</a></p>
        </div>
    </div>
</body>
</html>