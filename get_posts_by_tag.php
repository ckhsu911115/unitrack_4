<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    exit('尚未登入');
}
$user_id = $_SESSION['user']['id'] ?? $_SESSION['user_id'];
$tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

if ($tag !== '') {
    // 依標籤查詢
    $sql = "SELECT p.id, p.title, p.created_at, p.post_date, b.content
            FROM posts p
            INNER JOIN post_tags pt ON p.id = pt.post_id
            LEFT JOIN blocks b ON p.id = b.post_id AND b.block_order = 1
            WHERE p.user_id = ? AND pt.tag = ?
            ORDER BY p.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $tag]);
    $posts = $stmt->fetchAll();
} elseif ($keyword !== '') {
    // 依關鍵詞查詢（僅搜尋 text block）
    $sql = "SELECT DISTINCT p.id, p.title, p.created_at, p.post_date, b.content
            FROM posts p
            INNER JOIN blocks b ON p.id = b.post_id
            WHERE p.user_id = ? AND b.block_type = 'text' AND b.content LIKE ?
            ORDER BY p.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, '%'.$keyword.'%']);
    $posts = $stmt->fetchAll();
} else {
    // 預設全部
    $sql = "SELECT p.id, p.title, p.created_at, p.post_date, b.content
            FROM posts p
            LEFT JOIN blocks b ON p.id = b.post_id AND b.block_order = 1
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $posts = $stmt->fetchAll();
}
if (count($posts) === 0) {
    echo '<p>尚無相關紀錄</p>';
    exit;
}
foreach ($posts as $post) {
    echo '<div class="timeline-block">';
    echo '<div class="timeline-title">'.htmlspecialchars($post['title']).' <a href="view_post.php?id='.$post['id'].'">檢視</a></div>';
    echo '<div class="timeline-date">'.htmlspecialchars($post['post_date'] ?? $post['created_at']).'</div>';
    echo '<div class="timeline-content">'.nl2br(htmlspecialchars(mb_substr($post['content'], 0, 80))).'</div>';
    echo '</div>';
} 