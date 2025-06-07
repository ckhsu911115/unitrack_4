<?php
$host = 'centerbeam.proxy.rlwy.net';
$port = 11549;
$dbname = 'railway';
$username = 'root';
$password = 'wfcIVaUVQwJNyQvYzzlqcMoXPagCGJlu';

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
