<?php
// login_process.php
ob_start(); // 保險措施避免 header() 錯誤
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 安全地取得表單資料
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // 檢查是否為空
    if (empty($username) || empty($password)) {
        header('Location: login.php?error=missing');
        exit;
    }

    // 資料庫查詢（實際開發應加密密碼）
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND password = ?');
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'email' => $user['email'],
            'name' => $user['name'] ?? ''  // 防止 undefined
        ];

        // 導向不同身分首頁
        switch ($user['role']) {
            case 'student':
                header('Location: student_home.php');
                break;
            case 'teacher':
                header('Location: teacher_home.php');
                break;
            case 'admin':
                header('Location: admin_home.php');
                break;
            default:
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

ob_end_flush(); // 結束緩衝
