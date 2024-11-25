<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    die("Доступ запрещен.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content'];

    $stmt = $pdo->query("SELECT * FROM quick_info");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("UPDATE quick_info SET content = ? WHERE id = 1");
        $stmt->execute([$content]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO quick_info (id, content) VALUES (1, ?)");
        $stmt->execute([$content]);
    }

    header("Location: index.php");
    exit;
}
?>