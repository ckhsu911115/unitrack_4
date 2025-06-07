<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header('Location: login.php');
    exit;
}
require_once 'db.php';
$user_id = $_SESSION['user']['id'];

// 取得分類
$catStmt = $pdo->prepare('SELECT id, name FROM categories WHERE user_id = ?');
$catStmt->execute([$user_id]);
$categories = $catStmt->fetchAll();

// 取得全部文章
$sql = "SELECT p.*, 
        (SELECT b.content FROM blocks b WHERE b.post_id = p.id AND b.block_type = 'text' ORDER BY b.block_order ASC LIMIT 1) as preview_content 
        FROM posts p 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$posts = $stmt->fetchAll();

// 依分類分組文章
$posts_by_category = [];
foreach ($posts as $post) {
    $cat_id = (string)($post['category_id'] ?? '未分類');
    $posts_by_category[$cat_id][] = $post;
}
$posts_by_category['all'] = $posts;

// 取得所有文字模塊內容，做詞頻分析
$textStmt = $pdo->prepare("SELECT b.content FROM blocks b INNER JOIN posts p ON b.post_id = p.id WHERE p.user_id = ? AND b.block_type = 'text'");
$textStmt->execute([$user_id]);
$textBlocks = $textStmt->fetchAll();
$textAll = '';
foreach ($textBlocks as $tb) { $textAll .= $tb['content'] . ' '; }
// 簡單分詞與停用詞排除
$stopwords = ['的','是','我','你','他','她','它','了','和','在','有','也','就','都','而','及','與','著','或','一','不','上','下','到','這','那','我們','你們','他們','她們','其','被','為','於','以','之','並','等','等於','與其','而且','如果','但是','因為','所以','而是','而且','並且','還有','以及','或者','但是','而','呢','嗎','吧','啊','哦','呀','嘛','啦','喔','呃','嗯','哇','哈','嘿','嘻','嘩','嘻哈','嘻嘻','嘻嘻哈哈','嘻哈嘻哈','嘻哈嘻哈嘻哈','嘻哈嘻哈嘻哈嘻哈'];
$words = preg_split('/[\s\pP]+/u', $textAll, -1, PREG_SPLIT_NO_EMPTY);
$freq = [];
foreach ($words as $w) {
    $w = trim($w);
    if ($w === '' || mb_strlen($w) < 2 || in_array($w, $stopwords)) continue;
    if (!isset($freq[$w])) $freq[$w] = 0;
    $freq[$w]++;
}
arsort($freq);
$wordcloud = [];
foreach ($freq as $w => $c) {
    $wordcloud[] = [ $w, $c ];
    if (count($wordcloud) >= 50) break;
}

// 取得所有文章的標籤、分類、狀態
foreach ($posts as &$post) {
    $stmt2 = $pdo->prepare('SELECT tag FROM post_tags WHERE post_id = ?');
    $stmt2->execute([$post['id']]);
    $post['tags'] = $stmt2->fetchAll(PDO::FETCH_COLUMN);
    $cat = array_filter($categories, function($c) use ($post) { return $c['id'] == $post['category_id']; });
    $post['category_name'] = $cat ? array_values($cat)[0]['name'] : '未分類';
}
unset($post);

$deleted = isset($_GET['deleted']) ? true : false;
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>學生首頁</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { margin:0; font-family: 'Noto Sans TC', Arial, sans-serif; background:#fff; }
        .notion-layout { 
            display:flex; 
            height:100vh; 
            min-height:600px; 
            position: relative;
        }
        .notion-sidebar { 
            width:210px; 
            background:#fff; 
            border-right:1px solid #e3e6ef; 
            padding:18px 0 0 0; 
            overflow-y:auto; 
            position: relative;
            z-index: 10; /* 提高 z-index */
        }
        .notion-sidebar h4 { margin:0 0 10px 24px; font-size:1.1em; color:#ff69b4; }
        .notion-category-list { list-style:none; padding:0 0 0 12px; margin:0; }
        .notion-category-list li { padding:7px 18px; cursor:pointer; border-radius:8px; margin-bottom:2px; transition:background 0.15s; }
        .notion-category-list li.active, .notion-category-list li:hover { background:#ffe4f0; color:#ff69b4; }
        .notion-main { 
            flex:1; 
            display:flex; 
            flex-direction:column; 
            min-width:0; 
            position: relative;
            z-index: 10; /* 提高 z-index */
            background: #fff;
        }
        .notion-listbar { 
            height:100%; 
            max-height:calc(100vh - 0px); 
            overflow-y:auto; 
            background:#fff0f5; 
            border-right:1px solid #ffe4f0; 
            width:320px; 
            min-width:180px; 
            max-width:600px; 
        .notion-main { flex:1; display:flex; flex-direction:column; min-width:0; }
        .notion-listbar {
            height:100%;
            max-height:calc(100vh - 0px);
            overflow-y:auto;
            background:#fff0f5;
            border-right:1px solid #ffe4f0;
            width:320px;
            min-width:180px;
            max-width:600px;
            resize: horizontal;
            overflow:auto;
        }
        .notion-article-list {
            width: 100%;
        }
        .notion-article-list li {
            width: 100%;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 48px;
            min-height: 48px;
            max-height: 48px;
            overflow: hidden;
            border-bottom: 1.5px solid #ffe4f0;
            margin: 0 -48px;  /* 增加左側延伸長度 */
            padding: 0 48px;  /* 相應增加內邊距 */
        }
        .notion-article-list li:hover {
            background-color: #ffe4f0;
        }
        .notion-article-list li.active {
            background-color: #ffe4f0;
        }
        .notion-article-list .article-title {
            flex: 1 1 0;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .notion-article-list .article-meta {
            font-size: 12px;
            color: #666;
            display: flex;
            gap: 8px;
        }
        .notion-article-list .article-category {
            color: #ff69b4;
        }
        .notion-article-list .article-date {
            display: none;  /* 隱藏日期 */
        }
        .notion-article-list .notion-article-lock {
            flex-shrink: 0;
            margin-left: 8px;
        }
        .notion-preview { flex:1; padding:32px 32px 32px 32px; overflow-y:auto; background:#fff; min-width:0; }
        @media (max-width:900px) {
            .notion-layout { flex-direction:column; }
            .notion-sidebar, .notion-listbar { width:100%; min-width:0; border-right:none; border-bottom:1px solid #e3e6ef; }
            .notion-main { flex-direction:column; }
            .notion-preview { padding:18px; }
        }
        .form-group {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="notion-layout">
        <div class="notion-sidebar">
            <h4>分類</h4>
            <ul class="notion-category-list">
                <li data-category="home" class="active">首頁</li>
                <li data-category="all">全部文章</li>
                <?php foreach ($categories as $cat): ?>
                <li data-category="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="notion-main">
            <div id="articleListBar" class="notion-listbar">
                <ul class="notion-article-list">
                    <?php foreach ($posts as $post): ?>
                    <li data-id="<?php echo $post['id']; ?>" data-category="<?php echo $post['category_id']; ?>">
                        <span class="article-title"><?php echo htmlspecialchars($post['title']); ?></span>
                        <div class="article-meta">
                            <span class="article-category"><?php echo htmlspecialchars($post['category_name']); ?></span>
                            <?php if ($post['is_locked']): ?>
                            <span class="notion-article-lock">🔒</span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div id="previewPanel" class="notion-preview">
                <div style="color:#aaa;text-align:center;margin-top:80px;">請點選左側文章以預覽內容</div>
            </div>
        </div>
    </div>
    <script>
        // 顯示評論
        function showComments() {
            const currentPostId = document.getElementById('editPostId').value;
            if (!currentPostId) {
                alert('請先選擇一篇文章');
                return;
            }
            
            document.getElementById('commentsFloat').style.display = 'block';
            loadTeacherComments(currentPostId);
        }

        // 隱藏評論
        function hideComments() {
            document.getElementById('commentsFloat').style.display = 'none';
        }

        // 載入老師評論
        function loadTeacherComments(postId) {
            if (!postId) {
                console.error('No post ID provided');
                return;
            }
            
            console.log('Loading comments for post ID:', postId); // 調試信息
            
            fetch(`get_teacher_comments.php?post_id=${postId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Received comments data:', data); // 調試信息
                    
                    const commentsContainer = document.getElementById('teacherComments');
                    if (!data.comments || data.comments.length === 0) {
                        commentsContainer.innerHTML = '<div style="color:#666;text-align:center;padding:20px;">尚無老師評論</div>';
                        return;
                    }
                    
                    commentsContainer.innerHTML = data.comments.map(comment => `
                        <div style="margin-bottom:15px;padding:12px;background:#f8f9fa;border-radius:6px;">
                            <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                                <span style="color:#ff69b4;font-weight:500;">${escapeHtml(comment.teacher_name)}</span>
                                <span style="color:#666;font-size:0.9em;">${new Date(comment.created_at).toLocaleString('zh-TW')}</span>
                            </div>
                            <div style="color:#333;">${escapeHtml(comment.comment)}</div>
                            <div style="margin-top:8px;font-size:0.9em;color:#666;">
                                ID: ${comment.id} | 文章ID: ${comment.post_id}
                            </div>
                        </div>
                    `).join('');
                })
                .catch(error => {
                    console.error('Error loading teacher comments:', error);
                    document.getElementById('teacherComments').innerHTML = 
                        '<div style="color:#dc3545;text-align:center;padding:20px;">載入評論時發生錯誤</div>';
                });
        }

        // 點擊外部關閉評論框
        window.onclick = function(event) {
            const commentsFloat = document.getElementById('commentsFloat');
            if (event.target === commentsFloat) {
                hideComments();
            }
        }
    </script>
</body>
</html> 