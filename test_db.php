<?php
require_once 'db.php'; // 確保 db.php 檔案存在並可正確連線

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll();

    echo "<h2>✅ 成功連接到 Railway MySQL！</h2>";
    echo "<p>目前資料表如下：</p><ul>";
    foreach ($tables as $table) {
        echo "<li>" . $table[0] . "</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "<h2>❌ 連接失敗</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
