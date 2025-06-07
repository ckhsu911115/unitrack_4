<?php
// login_process.php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // 查詢資料庫
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND password = ?');
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'email' => $user['email'],
            'name' => $user['name']
        ];
        // 根據身分導向
        if ($user['role'] === 'student') {
            header('Location: student_home.php');
        } elseif ($user['role'] === 'teacher') {
            header('Location: teacher_home.php');
        } elseif ($user['role'] === 'admin') {
            header('Location: admin_home.php');
        } else {
            header('Location: login.php?error=role');
        }
        exit;
    } else {
        // 登入失敗
        header('Location: login.php?error=1');
        exit;
    }
} else {
    header('Location: login.php');
    exit;
} 