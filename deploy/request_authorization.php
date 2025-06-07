<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header('Location: login.php');
    exit;
}
require_once 'db.php';

$user_id = $_SESSION['user']['id'];

// 檢查是否已經有授權請求
$stmt = $pdo->prepare('SELECT * FROM authorization_requests WHERE user_id = ? AND status = "pending"');
$stmt->execute([$user_id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => '您已經有一個待處理的授權請求']);
    exit;
}

// 新增授權請求
$stmt = $pdo->prepare('INSERT INTO authorization_requests (user_id, status, created_at) VALUES (?, "pending", NOW())');
$stmt->execute([$user_id]);

echo json_encode(['success' => true]);
?> 