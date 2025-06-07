<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    echo json_encode(['error'=>'尚未登入']); exit;
}
$user_id = $_SESSION['user']['id'] ?? $_SESSION['user_id'];
// 標籤雲
$tagStmt = $pdo->prepare('SELECT tag, COUNT(*) as cnt FROM post_tags pt INNER JOIN posts p ON pt.post_id = p.id WHERE p.user_id = ? GROUP BY tag ORDER BY cnt DESC');
$tagStmt->execute([$user_id]);
$tags = $tagStmt->fetchAll();
// 關鍵詞雲
$textStmt = $pdo->prepare("SELECT b.content FROM blocks b INNER JOIN posts p ON b.post_id = p.id WHERE p.user_id = ? AND b.block_type = 'text'");
$textStmt->execute([$user_id]);
$textBlocks = $textStmt->fetchAll();
$textAll = '';
foreach ($textBlocks as $tb) { $textAll .= strip_tags($tb['content']) . ' '; }
// 停用詞
$stopwords = array_merge([
    '的','是','我','你','他','她','它','了','和','在','有','也','就','都','而','及','與','著','或','一','不','上','下','到','這','那','我們','你們','他們','她們','其','被','為','於','以','之','並','等','等於','與其','而且','如果','但是','因為','所以','而是','而且','並且','還有','以及','或者','但是','而','呢','嗎','吧','啊','哦','呀','嘛','啦','喔','呃','嗯','哇','哈','嘿','嘻','嘩','嘻哈','嘻嘻','嘻嘻哈哈','嘻哈嘻哈','嘻哈嘻哈嘻哈','嘻哈嘻哈嘻哈嘻哈'
], ['the','is','are','and','a','an','of','in','to','with','for','on','at','by','from','as','that','this','it','be','or','if','so','but','not','can','will','just','has','have','was','were','do','does','did','than','then','which','who','whom','whose','what','when','where','why','how','all','any','each','few','more','most','other','some','such','no','nor','too','very','s','t','d','ll','m','o','re','ve','y'] );
$words = preg_split('/[\s\pP]+/u', $textAll, -1, PREG_SPLIT_NO_EMPTY);
$freq = [];
foreach ($words as $w) {
    $w = trim($w);
    if ($w === '' || mb_strlen($w) < 2 || in_array(mb_strtolower($w), $stopwords)) continue;
    if (!isset($freq[$w])) $freq[$w] = 0;
    $freq[$w]++;
}
arsort($freq);
$wordcloud = [];
foreach ($freq as $w => $c) {
    $wordcloud[] = [ $w, $c ];
    if (count($wordcloud) >= 50) break;
}
echo json_encode([
    'tags' => $tags,
    'keywords' => $wordcloud
], JSON_UNESCAPED_UNICODE); 