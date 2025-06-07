<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($username) || empty($password) || empty($confirm_password)) {
        die('請填寫所有欄位');
    }
    
    if ($password !== $confirm_password) {
        die('密碼不一致');
    }
    
    // 檢查帳號是否已存在
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        die('帳號已存在');
    }
    
    // 新增使用者
    $stmt = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, "student")');
    $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
    
    header('Location: login.php');
    exit;
}
?> 