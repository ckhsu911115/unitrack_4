<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header('Location: index.html');
    exit;
}
$user_id = $_SESSION['user']['id'];
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($post_id <= 0) {
    header('Location: student_home.php');
    exit;
}
// 確認文章屬於本人
$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ? AND user_id = ?');
$stmt->execute([$post_id, $user_id]);
$post = $stmt->fetch();
if (!$post) {
    header('Location: student_home.php');
    exit;
}
// 刪除相關資料
$stmt = $pdo->prepare('DELETE FROM post_comments WHERE post_id = ?');
$stmt->execute([$post_id]);
$stmt = $pdo->prepare('DELETE FROM post_tags WHERE post_id = ?');
$stmt->execute([$post_id]);
$stmt = $pdo->prepare('DELETE FROM blocks WHERE post_id = ?');
$stmt->execute([$post_id]);
$stmt = $pdo->prepare('DELETE FROM posts WHERE id = ?');
$stmt->execute([$post_id]);
// 導回首頁並提示
header('Location: student_home.php?deleted=1');
exit; 