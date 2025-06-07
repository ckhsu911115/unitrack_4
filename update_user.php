<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.html');
    exit;
}
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? '';

    if ($id && $username && $email && $role) {
        try {
            $pdo->beginTransaction();

            // 檢查用戶名是否已存在（排除當前用戶）
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? AND id != ?');
            $stmt->execute([$username, $id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('用戶名已存在');
            }

            // 檢查 email 是否已存在（排除當前用戶）
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id != ?');
            $stmt->execute([$email, $id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Email 已存在');
            }

            // 如果提供了新密碼，則更新密碼
            if ($password) {
                $stmt = $pdo->prepare('UPDATE users SET username = ?, password = ?, email = ?, role = ? WHERE id = ?');
                $stmt->execute([$username, $password, $email, $role, $id]);
            } else {
                $stmt = $pdo->prepare('UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?');
                $stmt->execute([$username, $email, $role, $id]);
            }

            $pdo->commit();
            $_SESSION['success_message'] = '用戶更新成功';
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = '請填寫所有必填欄位';
    }
}

header('Location: admin_home.php');
exit; 