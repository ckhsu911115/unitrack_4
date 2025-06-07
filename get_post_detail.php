<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    echo json_encode(['error'=>'尚未登入']); exit;
}
$user_id = $_SESSION['user']['id'] ?? $_SESSION['user_id'];
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
if ($post_id <= 0) { echo json_encode(['error'=>'無效文章']); exit; }
// 取得文章
$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ? AND user_id = ?');
$stmt->execute([$post_id, $user_id]);
$post = $stmt->fetch();
if (!$post) { echo json_encode(['error'=>'無此文章']); exit; }
if (!empty($post['is_locked'])) { echo json_encode(['error'=>'這篇文章已上鎖，無法查看']); exit; }
// 取得所有模塊
$stmt = $pdo->prepare('SELECT * FROM blocks WHERE post_id = ? ORDER BY block_order ASC');
$stmt->execute([$post_id]);
$blocks = $stmt->fetchAll();
// 取得標籤
$stmt = $pdo->prepare('SELECT tag FROM post_tags WHERE post_id = ?');
$stmt->execute([$post_id]);
$tags = $stmt->fetchAll(PDO::FETCH_COLUMN);
// 取得所有分類
$catStmt = $pdo->prepare('SELECT id, name FROM categories WHERE user_id = ?');
$catStmt->execute([$user_id]);
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
// 整理 blocks
$blockArr = [];
foreach ($blocks as $block) {
    if ($block['block_type'] === 'text') {
        $blockArr[] = [ 'type'=>'text', 'content'=>$block['content'] ];
    } elseif ($block['block_type'] === 'image') {
        $blockArr[] = [ 'type'=>'image', 'url'=>$block['content'] ];
    } elseif ($block['block_type'] === 'file') {
        $filename = basename($block['content']);
        $blockArr[] = [ 'type'=>'file', 'url'=>$block['content'], 'filename'=>$filename ];
    }
}
echo json_encode([
    'id' => $post['id'],
    'title' => $post['title'],
    'post_date' => $post['post_date'] ?? $post['created_at'],
    'tags' => $tags,
    'blocks' => $blockArr,
    'category_id' => $post['category_id'] ?? '',
    'is_locked' => !empty($post['is_locked']),
    'is_private' => !empty($post['is_private']),
    'allow_teacher_view' => !empty($post['allow_teacher_view']),
    'categories' => $categories
], JSON_UNESCAPED_UNICODE); 