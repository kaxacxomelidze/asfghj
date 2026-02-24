<?php
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';

try {
    $pdo = db();
    echo "DB connected successfully<br>";

    $stmt = $pdo->query("SELECT 1");
    echo "Query OK<br>";

    $stmt = $pdo->query("SHOW TABLES LIKE 'admins'");
    $row = $stmt->fetch(PDO::FETCH_NUM);

    if ($row) {
        echo "admins table exists ✅<br>";
    } else {
        echo "admins table NOT found ❌<br>";
    }

} catch (Throwable $e) {
    echo "DB ERROR: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}