<?php
$host = 'unitrack-mysql'; // 注意：這是「Render 內部服務名稱」
$port = 3306;
$dbname = 'unitrack_5';
$username = 'unitrack_user';
$password = 'unitrack_pass';

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
