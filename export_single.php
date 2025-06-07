<?php
session_start();
require_once 'db.php';
require_once 'pdf_generator.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') die('No permission');
$user_id = $_SESSION['user']['id'] ?? $_SESSION['user_id'];
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($post_id<=0) die('Invalid');
$stmt = $pdo->prepare('SELECT * FROM posts WHERE id=? AND user_id=?');
$stmt->execute([$post_id, $user_id]);
$post = $stmt->fetch();
if (!$post) die('Not found');
// 取得分類
$cat = '';
if ($post['category_id']) {
    $catStmt = $pdo->prepare('SELECT name FROM categories WHERE id=?');
    $catStmt->execute([$post['category_id']]);
    $cat = $catStmt->fetchColumn();
}
// 取得標籤
$stmt2 = $pdo->prepare('SELECT tag FROM post_tags WHERE post_id=?');
$stmt2->execute([$post_id]);
$tags = $stmt2->fetchAll(PDO::FETCH_COLUMN);
// 取得內容
$stmt3 = $pdo->prepare('SELECT * FROM blocks WHERE post_id=? ORDER BY block_order ASC');
$stmt3->execute([$post_id]);
$blocks = $stmt3->fetchAll();
$blockArr = [];
foreach ($blocks as $block) {
    if ($block['block_type'] === 'text') {
        $blockArr[] = [ 'type'=>'text', 'content'=>$block['content'] ];
    } elseif ($block['block_type'] === 'image') {
        $blockArr[] = [ 'type'=>'image', 'url'=>$block['content'] ];
    }
}
$date = $post['post_date'] ?? $post['created_at'];
$tmp = tempnam(sys_get_temp_dir(), 'pdf');
generate_pdf($post['title'], $cat, $tags, $blockArr, $date, $tmp);
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="'.iconv('UTF-8','BIG5',$post['title']).'.pdf"');
readfile($tmp);
unlink($tmp); 