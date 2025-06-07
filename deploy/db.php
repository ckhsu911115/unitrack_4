<?php
$host = 'sql306.infinityfree.com';
$dbname = 'if0_39174234_unitrack';
$username = 'if0_39174234';
$password = 'Aa911115';
$port = 3306;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    die("資料庫連線失敗: " . $e->getMessage());
}
?> 