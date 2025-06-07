<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    echo json_encode(['error'=>'尚未登入']); exit;
}
$user_id = $_SESSION['user']['id'] ?? $_SESSION['user_id'];
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($post_id <= 0) { echo json_encode(['error'=>'無效文章']); exit; }
// 取得文章
$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ? AND user_id = ?');
$stmt->execute([$post_id, $user_id]);
$post = $stmt->fetch();
if (!$post) { echo json_encode(['error'=>'無此文章']); exit; }
if (!empty($post['is_locked'])) { echo json_encode(['error'=>'這篇文章已上鎖，無法查看']); exit; }
// 取得第一個文字 block 作為摘要
$stmt = $pdo->prepare('SELECT content FROM blocks WHERE post_id = ? AND block_type = "text" ORDER BY block_order ASC LIMIT 1');
$stmt->execute([$post_id]);
$block = $stmt->fetch();
$summary = $block ? mb_substr($block['content'], 0, 100) : '';
// 取得標籤
$stmt = $pdo->prepare('SELECT tag FROM post_tags WHERE post_id = ?');
$stmt->execute([$post_id]);
$tags = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo json_encode([
    'title' => $post['title'],
    'post_date' => $post['post_date'] ?? $post['created_at'],
    'tags' => $tags,
    'summary' => $summary
], JSON_UNESCAPED_UNICODE); 