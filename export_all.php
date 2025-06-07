<?php
session_start();
require_once 'db.php';
require_once 'zip_creator.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') die('No permission');
$user_id = $_SESSION['user']['id'] ?? $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT * FROM posts WHERE user_id=?');
$stmt->execute([$user_id]);
$posts = $stmt->fetchAll();
$folders = [];
$tmp_root = sys_get_temp_dir().'/unitrack_export_'.uniqid();
@mkdir($tmp_root);
foreach ($posts as $post) {
    $title = preg_replace('/[\\/:*?"<>|]/', '_', $post['title']);
    $folder = $tmp_root . '/' . $title;
    @mkdir($folder);
    // 取得分類
    $cat = '';
    if ($post['category_id']) {
        $catStmt = $pdo->prepare('SELECT name FROM categories WHERE id=?');
        $catStmt->execute([$post['category_id']]);
        $cat = $catStmt->fetchColumn();
    }
    // 取得標籤
    $stmt2 = $pdo->prepare('SELECT tag FROM post_tags WHERE post_id=?');
    $stmt2->execute([$post['id']]);
    $tags = $stmt2->fetchAll(PDO::FETCH_COLUMN);
    // 取得內容
    $stmt3 = $pdo->prepare('SELECT * FROM blocks WHERE post_id=? ORDER BY block_order ASC');
    $stmt3->execute([$post['id']]);
    $blocks = $stmt3->fetchAll();
    $content = "";
    foreach ($blocks as $block) {
        if ($block['block_type'] === 'text') {
            $content .= $block['content']."\n\n";
        } elseif ($block['block_type'] === 'image') {
            $content .= "[圖片: ".basename($block['content'])."]\n";
        } elseif ($block['block_type'] === 'file') {
            $content .= "[檔案: ".basename($block['content'])."]\n";
        }
    }
    if (trim($content) === '') $content = '(本篇僅含圖片或檔案)';
    $date = $post['post_date'] ?? $post['created_at'];
    // 產生 txt
    $txt = "標題：{$post['title']}\n分類：{$cat}\n標籤：".implode(',', $tags)."\n日期：{$date}\n\n內容：\n{$content}";
    file_put_contents($folder . "/{$title}.txt", $txt);
    // 複製圖片/檔案
    foreach ($blocks as $block) {
        if ($block['block_type'] === 'image' || $block['block_type'] === 'file') {
            $src = $block['content'];
            $basename = basename($src);
            $src_path = __DIR__ . "/" . ltrim($src, '/');
            if (file_exists($src_path)) {
                copy($src_path, $folder . "/" . $basename);
            }
        }
    }
    $folders[$folder] = $title;
}
$tmp_zip = tempnam(sys_get_temp_dir(), 'zip');
create_zip($folders, $tmp_zip);
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="unitrack_all_'.date('Ymd_His').'.zip"');
readfile($tmp_zip);
// 清理暫存
foreach(array_keys($folders) as $f) {
    array_map('unlink', glob("$f/*"));
    @rmdir($f);
}
@unlink($tmp_zip);
@rmdir($tmp_root); 