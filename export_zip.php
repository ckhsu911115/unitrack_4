<?php
session_start();
require_once 'db.php';
// 支援兩種 session 結構
if (
    (isset($_SESSION['user']) && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'student')
) {
    $user_id = $_SESSION['user']['id'];
} elseif (
    (isset($_SESSION['role']) && $_SESSION['role'] === 'student') && isset($_SESSION['user_id'])
) {
    $user_id = $_SESSION['user_id'];
} else {
    header('Location: login.php');
    exit;
}
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($post_id <= 0) exit('無效文章');
// 取得文章
$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ?');
$stmt->execute([$post_id]);
$post = $stmt->fetch();
if (!$post || $post['user_id'] != $user_id) exit('未授權');
// 取得 blocks
$stmt = $pdo->prepare('SELECT * FROM blocks WHERE post_id = ? ORDER BY block_order ASC');
$stmt->execute([$post_id]);
$blocks = $stmt->fetchAll();
// 建立暫存資料夾
$tmpdir = __DIR__.'/temp_export/'.uniqid('exp_');
if (!is_dir($tmpdir)) mkdir($tmpdir, 0777, true);
$txt = "標題：{$post['title']}\n日期：".($post['post_date'] ?? $post['created_at'])."\n\n";
foreach ($blocks as $block) {
    if ($block['block_type'] === 'text') {
        $txt .= $block['content']."\n\n";
    } elseif ($block['block_type'] === 'image' || $block['block_type'] === 'file') {
        $txt .= "[包含檔案: ".$block['content']."]\n";
        $src = __DIR__ . '/' . $block['content'];
        if (file_exists($src)) copy($src, $tmpdir.'/'.basename($block['content']));
    }
}
file_put_contents($tmpdir.'/content.txt', $txt);
// 只產生 tar
$tarname = __DIR__.'/temp_export/unitrack_post_'.$post_id.'_'.uniqid().'.tar';
$phar = new PharData($tarname);
foreach (glob($tmpdir.'/*') as $file) {
    $phar->addFile($file, basename($file));
}
// 輸出下載
header('Content-Type: application/x-tar');
header('Content-Disposition: attachment; filename='.basename($tarname));
header('Content-Length: '.filesize($tarname));
readfile($tarname);
// 清理暫存
foreach (glob($tmpdir.'/*') as $f) unlink($f);
@rmdir($tmpdir);
@unlink($tarname);
exit; 