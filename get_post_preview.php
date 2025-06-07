<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') exit('尚未登入');
$user_id = $_SESSION['user']['id'] ?? $_SESSION['user_id'];
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($post_id <= 0) exit('無效文章');
// 取得文章
$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ? AND user_id = ?');
$stmt->execute([$post_id, $user_id]);
$post = $stmt->fetch();
if (!$post) exit('無此文章');
// 取得第一個 block 摘要
$stmt = $pdo->prepare('SELECT content FROM blocks WHERE post_id = ? AND block_type = "text" ORDER BY block_order ASC LIMIT 1');
$stmt->execute([$post_id]);
$block = $stmt->fetch();
$summary = $block ? mb_substr($block['content'], 0, 80) : '';
// 取得標籤
$stmt = $pdo->prepare('SELECT tag FROM post_tags WHERE post_id = ?');
$stmt->execute([$post_id]);
$tags = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<div>
    <h4 style="margin:0 0 8px 0;">📝 <?php echo htmlspecialchars($post['title']); ?></h4>
    <div style="color:#888;font-size:0.95em;">日期：<?php echo htmlspecialchars($post['post_date'] ?? $post['created_at']); ?></div>
    <div style="margin:8px 0;">摘要：<?php echo nl2br(htmlspecialchars($summary)); ?></div>
    <?php if ($tags): ?>
        <div style="margin:6px 0;">標籤：
            <?php foreach ($tags as $tag): ?>
                <span style="background:#e3eaff;color:#3a4a8c;padding:2px 8px;border-radius:10px;font-size:0.95em;margin-right:4px;">#<?php echo htmlspecialchars($tag); ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <a href="view_post.php?id=<?php echo $post_id; ?>" target="_blank">🔗 查看完整內容</a>
</div> 