<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    echo json_encode(['success'=>false,'error'=>'尚未登入']); exit;
}
$user_id = $_SESSION['user']['id'] ?? $_SESSION['user_id'];
// 新增分類
if (isset($_POST['add_category'])) {
    $name = trim($_POST['add_category']);
    if ($name === '') {
        echo json_encode(['success'=>false, 'error'=>'分類名稱不得為空']); exit;
    }
    // 檢查是否已存在
    $stmt = $pdo->prepare('SELECT id FROM categories WHERE user_id=? AND name=?');
    $stmt->execute([$user_id, $name]);
    if ($stmt->fetch()) {
        echo json_encode(['success'=>false, 'error'=>'分類已存在']); exit;
    }
    $stmt = $pdo->prepare('INSERT INTO categories (user_id, name) VALUES (?, ?)');
    $stmt->execute([$user_id, $name]);
    $cat_id = $pdo->lastInsertId();
    echo json_encode(['success'=>true, 'category_id'=>$cat_id]);
    exit;
}
$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
if ($post_id <= 0) { echo json_encode(['success'=>false,'error'=>'無效文章']); exit; }
// 欄位
$title = trim($_POST['title'] ?? '');
$category_id = $_POST['category_id'] ?? null;
$tags = isset($_POST['tags']) ? explode(',', trim($_POST['tags'])) : [];
$post_date = $_POST['post_date'] ?? null;
$is_locked = !empty($_POST['is_locked']) ? 1 : 0;
$is_private = !empty($_POST['is_private']) ? 1 : 0;
$allow_teacher_view = !empty($_POST['allow_teacher_view']) ? 1 : 0;
$block_contents = $_POST['block_content'] ?? [];
$block_types = $_POST['block_type'] ?? [];
if ($title === '') { echo json_encode(['success'=>false,'error'=>'標題不得為空']); exit; }
// 更新 posts
$stmt = $pdo->prepare('UPDATE posts SET title=?, category_id=?, post_date=?, is_locked=?, is_private=?, allow_teacher_view=? WHERE id=? AND user_id=?');
$stmt->execute([$title, $category_id, $post_date, $is_locked, $is_private, $allow_teacher_view, $post_id, $user_id]);
// 更新標籤
$pdo->prepare('DELETE FROM post_tags WHERE post_id=?')->execute([$post_id]);
foreach ($tags as $tag) {
    $tag = trim($tag);
    if ($tag !== '') {
        $pdo->prepare('INSERT INTO post_tags (post_id, tag) VALUES (?, ?)')->execute([$post_id, $tag]);
    }
}
// 更新 blocks（僅支援文字模塊）
$pdo->prepare('DELETE FROM blocks WHERE post_id=?')->execute([$post_id]);
foreach ($block_types as $i => $type) {
    if ($type === 'text') {
        $content = $block_contents[$i] ?? '';
        $pdo->prepare('INSERT INTO blocks (post_id, block_type, content, block_order) VALUES (?, ?, ?, ?)')->execute([$post_id, 'text', $content, $i+1]);
    }
    // 圖片/檔案模塊可擴充
}
echo json_encode(['success'=>true]); 