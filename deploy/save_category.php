<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';

$user_id = $_SESSION['user']['id'];
$name = $_POST['name'] ?? '';

if (empty($name)) {
    echo json_encode(['success' => false, 'error' => '請輸入分類名稱']);
    exit;
}

// 新增分類
$stmt = $pdo->prepare('INSERT INTO categories (user_id, name) VALUES (?, ?)');
$stmt->execute([$user_id, $name]);
$category_id = $pdo->lastInsertId();

echo json_encode(['success' => true, 'category_id' => $category_id]);
?> 