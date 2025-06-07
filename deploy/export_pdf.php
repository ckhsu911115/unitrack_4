<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';

$post_id = $_GET['post_id'] ?? 0;

// 取得文章內容
$sql = "SELECT p.*, u.username as student_name, c.name as category_name 
        FROM posts p 
        INNER JOIN users u ON p.user_id = u.id 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    die('文章不存在');
}

// 取得文章區塊
$stmt = $pdo->prepare('SELECT * FROM blocks WHERE post_id = ? ORDER BY block_order ASC');
$stmt->execute([$post_id]);
$blocks = $stmt->fetchAll();

// 取得文章標籤
$stmt = $pdo->prepare('SELECT tag FROM post_tags WHERE post_id = ?');
$stmt->execute([$post_id]);
$tags = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 取得老師評論
$stmt = $pdo->prepare('SELECT c.*, u.username as teacher_name 
                      FROM post_comments c 
                      INNER JOIN users u ON c.teacher_id = u.id 
                      WHERE c.post_id = ? 
                      ORDER BY c.created_at ASC');
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll();

// 生成 PDF
require_once 'vendor/autoload.php';
$mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4']);

$html = '
<style>
    body { font-family: "Noto Sans TC", Arial, sans-serif; }
    h1 { color: #ff69b4; }
    .meta { color: #666; margin-bottom: 20px; }
    .content { margin-bottom: 30px; }
    .comments { margin-top: 30px; }
    .comment { margin-bottom: 15px; padding: 10px; background: #f8f9fa; }
    .comment-meta { color: #666; font-size: 0.9em; }
</style>
<h1>' . htmlspecialchars($post['title']) . '</h1>
<div class="meta">
    作者：' . htmlspecialchars($post['student_name']) . '<br>
    分類：' . htmlspecialchars($post['category_name']) . '<br>
    標籤：' . implode(', ', array_map('htmlspecialchars', $tags)) . '<br>
    建立時間：' . $post['created_at'] . '<br>
    更新時間：' . $post['updated_at'] . '
</div>
<div class="content">';

foreach ($blocks as $block) {
    if ($block['block_type'] === 'text') {
        $html .= '<p>' . nl2br(htmlspecialchars($block['content'])) . '</p>';
    } elseif ($block['block_type'] === 'image') {
        $html .= '<img src="' . htmlspecialchars($block['content']) . '" style="max-width:100%;">';
    }
}

$html .= '</div>';

if (!empty($comments)) {
    $html .= '<div class="comments"><h2>老師評論</h2>';
    foreach ($comments as $comment) {
        $html .= '
        <div class="comment">
            <div class="comment-meta">
                ' . htmlspecialchars($comment['teacher_name']) . ' - ' . $comment['created_at'] . '
            </div>
            <div class="comment-content">
                ' . nl2br(htmlspecialchars($comment['comment'])) . '
            </div>
        </div>';
    }
    $html .= '</div>';
}

$mpdf->WriteHTML($html);
$mpdf->Output('文章_' . $post_id . '.pdf', 'D');
?> 