<?php
require 'db.php';

$stmt = $pdo->query("SELECT content FROM quick_info LIMIT 1");
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['content'];
} else {
    echo '';
}
?>
