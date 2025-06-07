<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.html');
    exit;
}
require_once 'db.php';

$id = $_GET['id'] ?? null;
if ($id) {
    // 僅允許刪除 student
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ? AND role = ?');
    $stmt->execute([$id, 'student']);
}
header('Location: account_manage.php?deleted=1');
exit; 