<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $created_at = date('Y-m-d H:i:s');

    // 基本檢查
    if (!$username || !$password || !$confirm_password || !$email || !$role) {
        header('Location: register.php?error=empty');
        exit;
    }
    if ($password !== $confirm_password) {
        header('Location: register.php?error=nomatch');
        exit;
    }

    // 檢查帳號是否已存在
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        header('Location: register.php?error=exists');
        exit;
    }

    // 寫入資料庫
    $stmt = $pdo->prepare('INSERT INTO users (username, password, email, role, created_at) VALUES (?, ?, ?, ?, ?)');
    try {
        $stmt->execute([$username, $password, $email, $role, $created_at]);
        header('Location: login.php?register=success');
        exit;
    } catch (PDOException $e) {
        header('Location: register.php?error=fail');
        exit;
    }
} else {
    header('Location: register.php');
    exit;
} 