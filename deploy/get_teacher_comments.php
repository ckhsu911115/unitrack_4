<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';

$post_id = $_GET['post_id'] ?? 0;

// 取得該文章的所有老師評論
$sql = "SELECT c.*, u.username as teacher_name 
        FROM post_comments c 
        INNER JOIN users u ON c.teacher_id = u.id 
        WHERE c.post_id = ? 
        ORDER BY c.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll();

echo json_encode(['comments' => $comments]);
?> 