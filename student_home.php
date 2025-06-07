<?php
// 設置 session 參數
ini_set('session.cookie_lifetime', 86400); // 24小時
ini_set('session.gc_maxlifetime', 86400); // 24小時
session_set_cookie_params(86400); // 24小時

session_start();

// 檢查登入狀態
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header('Location: login.php');
    exit;
}

// 更新 session 時間戳
$_SESSION['last_activity'] = time();

// 檢查 session 是否過期（30分鐘無活動）
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
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
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .block-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .block-item {
            position: relative;
            border: 1px solid transparent;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 4px;
            transition: all 0.2s;
            max-width: 900px;
            width: 100%;
            margin-left: auto;
            margin-right: auto;
            box-sizing: border-box;
        }
        .block-item:hover {
            border-color: #e5e7eb;
            background: #f9fafb;
        }
        .block-item textarea {
            width: 100%;
            min-height: 100px;
            padding: 8px;
            border: none;
            outline: none;
            resize: vertical;
            font-size: 16px;
            line-height: 1.6;
            background: transparent;
        }
        .block-item textarea:focus {
            background: #f9fafb;
        }
        .block-actions {
            position: absolute;
            right: 8px;
            top: 8px;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .block-item:hover .block-actions {
            opacity: 1;
        }
        .block-actions button {
            padding: 4px 8px;
            font-size: 12px;
            color: #6b7280;
            background: transparent;
            border: 1px solid #e5e7eb;
        }
        .block-actions button:hover {
            background: #f3f4f6;
            color: #374151;
        }
        .primary-btn {
            background: #2563eb;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
        }
        .secondary-btn {
            background: #f3f4f6;
            color: #374151;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
        }
        .block-preview img {
            max-width: 100%;
            border-radius: 4px;
            margin-top: 8px;
        }
        .sortable-ghost {
            opacity: 0.4;
            background: #f3f4f6;
        }
        .editor-toolbar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .toolbar-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background: white;
            color: #374151;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .toolbar-btn:hover {
            background: #f3f4f6;
        }
        /* 文章列表樣式 */
        .post-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .post-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
        }

        .post-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .post-meta {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .post-category {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
        }

        .post-date {
            color: #666;
            font-size: 0.9em;
        }

        .post-actions {
            display: flex;
            gap: 8px;
        }

        .btn-icon {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .btn-icon:hover {
            background: #f5f5f5;
            color: #1976d2;
        }

        .post-title {
            margin: 0;
            padding: 15px;
            font-size: 1.2em;
            color: #333;
        }

        .post-content {
            padding: 0 15px 15px;
            color: #666;
            font-size: 0.95em;
            line-height: 1.5;
        }

        .post-preview-image {
            width: 100%;
            height: 200px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .post-preview-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .post-footer {
            padding: 15px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .post-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .tag {
            background: #f5f5f5;
            color: #666;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
        }

        .post-status {
            display: flex;
            gap: 8px;
        }

        .status-badge {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
        }

        .status-badge.locked {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-badge.private {
            background: #fce4ec;
            color: #c2185b;
        }

        .status-badge.teacher {
            background: #e8f5e9;
            color: #388e3c;
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .empty-state i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 16px;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 20px;
        }

        .btn-primary {
            background: #1976d2;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }

        .btn-primary:hover {
            background: #1565c0;
        }

        /* 授權按鈕樣式 */
        .authorize-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
            margin-bottom: 20px;
        }

        .authorize-btn:hover {
            background: #45a049;
        }

        .authorize-btn i {
            font-size: 16px;
        }

        /* 文章內容區域樣式 */
        .notion-content {
            flex: 1;
            overflow-y: auto;
            background: #fff;
            padding: 0;
        }

        .notion-editor {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 0;
        }

        .notion-page {
            background: #fff;
            min-height: 100vh;
        }

        .notion-page-header {
            margin-bottom: 40px;
            padding: 0 40px;
        }

        .notion-page-title h1 {
            font-size: 40px;
            font-weight: 700;
            color: #37352f;
            margin: 0 0 10px;
            line-height: 1.2;
        }

        .notion-page-meta {
            display: flex;
            gap: 16px;
            color: #787774;
            font-size: 14px;
        }

        .notion-page-content {
            padding: 0 40px;
        }

        .notion-block, .notion-block-content {
            max-width: 900px;
            width: 100%;
            margin-left: auto;
            margin-right: auto;
            box-sizing: border-box;
        }

        .notion-block {
            margin-bottom: 24px;
            position: relative;
        }

        .notion-block:hover {
            background: rgba(55, 53, 47, 0.03);
        }

        .notion-block-content {
            padding: 3px 2px;
            min-height: 24px;
            line-height: 1.5;
            color: #37352f;
        }

        .notion-text {
            font-size: 16px;
            line-height: 1.6;
            color: #37352f;
        }

        .notion-image {
            margin: 8px 0;
            border-radius: 4px;
            overflow: hidden;
        }

        .notion-image img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        .notion-file {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: #f7f6f3;
            border-radius: 4px;
            margin: 8px 0;
        }

        .notion-file i {
            color: #787774;
        }

        .notion-file a {
            color: #37352f;
            text-decoration: none;
        }

        .notion-file a:hover {
            text-decoration: underline;
        }

        .notion-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin: 16px 0;
        }

        .notion-tag {
            background: #f1f1ef;
            color: #37352f;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 14px;
        }

        .notion-status {
            display: flex;
            gap: 8px;
            margin: 16px 0;
        }

        .notion-status-badge {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 14px;
        }

        .notion-status-badge.locked {
            background: #fff3e0;
            color: #f57c00;
        }

        .notion-status-badge.private {
            background: #fce4ec;
            color: #c2185b;
        }

        .notion-status-badge.teacher {
            background: #e8f5e9;
            color: #388e3c;
        }

        .status-row {
            display: flex;
            gap: 32px;
            align-items: center;
        }

        .toolbar-row {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .close-btn {
            position: absolute;
            top: 18px;
            right: 24px;
            background: none;
            border: none;
            font-size: 2rem;
            color: #bbb;
            cursor: pointer;
            z-index: 10;
            transition: color 0.2s;
        }

        .close-btn:hover {
            color: #1976d2;
        }

        /* Block 編輯器重構 */
        .block-item, .rich-text-edit, .block-item textarea {
            max-width: 900px;
            width: 100%;
            margin-left: auto;
            margin-right: auto;
            box-sizing: border-box;
        }

        #homePanel {
            width: 100%;
            padding: 32px;
            position: relative;
            z-index: 100; /* 確保在編輯面板之上 */
            background: #fff;
        }

        #editPostPanel {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            background: #fff;
        }

        .block-item {
            position: relative;
            margin-bottom: 20px;
            padding-left: 40px;
        }
        .block-item.dragging {
            opacity: 0.5;
        }
        .drag-handle:hover {
            color: #ff69b4 !important;
        }
        .block-actions button:hover {
            color: #ff69b4 !important;
        }
    </style>
    <link rel="stylesheet" href="layout.css">
    <link rel="stylesheet" href="post.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head>
<body>
<div class="notion-layout">
    <div class="notion-sidebar">
        <div style="padding: 0 18px 18px 18px;">
            <button onclick="window.location.href='authorization_management.php'" style="display: flex; align-items: center; gap: 8px; background: #ff69b4; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 500; transition: background 0.2s; border: none; cursor: pointer; width: 100%; margin-bottom: 10px;">
                <span style="font-size: 1.2em;">🔐</span>
                <span>授權管理</span>
            </button>
            <button onclick="showCreatePost()" style="display: flex; align-items: center; gap: 8px; background: #2563eb; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 500; transition: background 0.2s; border: none; cursor: pointer; width: 100%;">
                <span style="font-size: 1.2em;">+</span>
                <span>新增文章</span>
            </button>
        </div>
        <h4>分類</h4>
        <ul class="notion-category-list" id="categoryList">
            <li class="home-btn active" data-category="home">🏠 首頁</li>
            <li data-category="all">全部文章</li>
            <?php foreach ($categories as $cat): ?>
                <li data-category="<?= (string)$cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></li>
            <?php endforeach; ?>
            <li data-category="未分類">未分類</li>
        </ul>
    </div>
    <div class="notion-main" style="display:flex;flex-direction:row;min-width:0;">
        <div class="notion-listbar" id="articleListBar" style="width:320px;min-width:200px;max-width:600px;">
            <ul class="notion-article-list" id="articleList">
                <!-- 文章列表由 JS 動態渲染 -->
            </ul>
        </div>
        <!-- 右側內容區：共用新增/編輯表單 -->
        <div id="editPostPanel" style="width:100%;padding:32px;position:relative;">
            <div style="position:absolute;top:18px;right:90px;z-index:11;display:flex;gap:12px;">
                <button id="undoBtn" type="button" style="background:#ffe4f0;border:none;border-radius:6px;padding:6px 16px;font-size:1.1em;cursor:pointer;color:#ff69b4;" disabled="">↶ 返回</button>
                <button id="redoBtn" type="button" style="background:#ffe4f0;border:none;border-radius:6px;padding:6px 16px;font-size:1.1em;cursor:pointer;color:#ff69b4;" disabled="">前進 ↷</button>
                <a id="exportPdfBtn" href="#" target="_blank" style="background:#ff69b4;color:white;border:none;border-radius:6px;padding:6px 16px;font-size:1.1em;text-decoration:none;display:inline-block;pointer-events:none;opacity:0.5;">匯出 PDF</a>
                <a id="exportZipBtn" href="#" target="_blank" style="background:#ff69b4;color:white;border:none;border-radius:6px;padding:6px 16px;font-size:1.1em;text-decoration:none;display:inline-block;pointer-events:none;opacity:0.5;">匯出 TAR</a>
                <button onclick="showComments()" style="background:#ff69b4;color:white;border:none;border-radius:6px;padding:6px 16px;font-size:1.1em;cursor:pointer;">💬 老師評論</button>
            </div>
            <div style="max-width:900px;margin:0 auto;">
                <form id="postForm" action="save_post.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="post_id" id="editPostId">
                    <div class="form-group" style="margin-bottom:40px;">
                        <input type="text" name="title" id="editTitle" placeholder="無標題" required style="font-size:2.5rem;font-weight:bold;border:none;outline:none;width:100%;background:transparent;padding:0;">
                    </div>
                    <div class="form-group" style="display:flex;gap:20px;margin-bottom:30px;">
                        <div style="flex:1;">
                            <label>分類：</label>
                            <select name="category_id" id="editCategory" required style="width:100%;">
                                <option value="">請選擇</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                                <option value="new">+ 新增分類</option>
                            </select>
                        </div>
                        <div style="flex:1;">
                            <label>活動日期：</label>
                            <input type="date" name="post_date" id="editDate" required style="width:100%;">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:30px;">
                        <label>標籤（可多個，以逗號分隔）：</label>
                        <input type="text" name="tags" id="editTags" placeholder="如：AI,心得,專題" style="width:100%;">
                    </div>
                    <div class="form-group" style="margin-bottom:20px;">
                        <div class="status-row">
                            <label><input type="checkbox" name="is_locked" id="editLocked"> 上鎖（僅本人可見）</label>
                            <label><input type="checkbox" name="is_private" id="editPrivate"> 私人（不對外公開）</label>
                            <label><input type="checkbox" name="allow_teacher_view" id="editTeacherView"> 允許老師閱讀</label>
                        </div>
                    </div>
                    <div class="editor-toolbar toolbar-row" style="margin-bottom:20px;">
                        <button type="button" class="toolbar-btn" onclick="addBlock('text');return false;"><span style="font-size:1.2em;">📝</span> 文字</button>
                        <button type="button" class="toolbar-btn" onclick="addBlock('image');return false;"><span style="font-size:1.2em;">🖼️</span> 圖片</button>
                        <button type="button" class="toolbar-btn" onclick="addBlock('file');return false;"><span style="font-size:1.2em;">📎</span> 檔案</button>
                    </div>
                    <ul id="blockList" class="block-list"></ul>
                    <div id="autosave-status" style="margin:10px 0 0 0;color:#1976d2;font-size:0.98em;min-height:22px;"></div>
                </form>
            </div>
        </div>
        <!-- 首頁內容區塊（預設隱藏） -->
        <div id="homePanel" style="display:none;width:100%;padding:32px;">
            <h3 style="color:#ff69b4;">🕒 學習歷程時間軸</h3>
            <link rel="stylesheet" href="timeline.css">
            <div id="timeline-container" class="timeline-scroll">
                <div id="timeline" class="timeline-bar">
                    <?php foreach ($posts as $i => $post): ?>
                    <div class="timeline-node-with-summary">
                        <div class="timeline-node" tabindex="0">
                            <div class="timeline-dot"></div>
                            <div class="timeline-date"><?php echo htmlspecialchars($post['post_date'] ?? $post['created_at']); ?></div>
                        </div>
                        <?php
                        // 取得標籤
                        $stmt2 = $pdo->prepare('SELECT tag FROM post_tags WHERE post_id = ?');
                        $stmt2->execute([$post['id']]);
                        $tags = $stmt2->fetchAll(PDO::FETCH_COLUMN);
                        // 取得第一個文字 block 作為摘要
                        $stmt3 = $pdo->prepare('SELECT content FROM blocks WHERE post_id = ? AND block_type = "text" ORDER BY block_order ASC LIMIT 1');
                        $stmt3->execute([$post['id']]);
                        $block = $stmt3->fetch();
                        $summary = $block ? mb_substr($block['content'], 0, 100) : '';
                        ?>
                        <div class="timeline-summary-card timeline-inline-card" tabindex="0" data-id="<?php echo $post['id']; ?>">
                            <div class="timeline-summary-title"><?php echo htmlspecialchars($post['title']); ?></div>
                            <div class="timeline-summary-date"><?php echo htmlspecialchars($post['post_date'] ?? $post['created_at']); ?></div>
                            <?php if ($tags): ?><div class="timeline-summary-tags"><?php foreach ($tags as $tag): ?><span class="timeline-summary-tag">#<?php echo htmlspecialchars($tag); ?></span><?php endforeach; ?></div><?php endif; ?>
                            <div class="timeline-summary-content"><?php echo htmlspecialchars($summary); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div id="timeline-summary"></div>
            <hr>
            <div class="cloud-flex" style="display:flex;gap:40px;flex-wrap:wrap;">
                <div class="cloud-block" style="flex:1;min-width:260px;">
                    <h3 style="color:#ff69b4;">🎯 標籤文字雲</h3>
                    <div id="tagCloud" class="cloud-area" style="background-color: rgb(255, 255, 255); position: relative; -webkit-tap-highlight-color: rgba(0, 0, 0, 0);">
                        <span style="position: absolute; display: block; font: 16px / 16px 'Noto Sans TC', Arial; left: 402.792px; top: 116.6px; width: 14.416px; height: 16px; white-space: nowrap; transform: rotate(0deg); transform-origin: 50% 40%; color: rgb(255, 105, 180);">AI</span>
                        <span style="position: absolute; display: block; font: 16px / 16px 'Noto Sans TC', Arial; left: 472.771px; top: 116.6px; width: 38.4589px; height: 16px; white-space: nowrap; transform: rotate(0deg); transform-origin: 50% 40%; color: rgb(255, 105, 180);">AAAA</span>
                        <span style="position: absolute; display: block; font: 16px / 16px 'Noto Sans TC', Arial; left: 312px; top: 116.6px; width: 32px; height: 16px; white-space: nowrap; transform: rotate(0deg); transform-origin: 50% 40%; color: rgb(255, 105, 180);">心得</span>
                    </div>
                </div>
                <div class="cloud-block" style="flex:1;min-width:260px;">
                    <h3 style="color:#ff69b4;">📚 文章關鍵詞雲</h3>
                    <div id="keyCloud" class="cloud-area" style="background-color: rgb(255, 255, 255); position: relative; -webkit-tap-highlight-color: rgba(0, 0, 0, 0);">
                        <span style="position: absolute; display: block; font: 16px / 16px 'Noto Sans TC', Arial; left: 399.42px; top: 116.6px; width: 62.1599px; height: 16px; white-space: nowrap; transform: rotate(0deg); transform-origin: 50% 40%; color: rgb(255, 105, 180);">2131232</span>
                        <span style="position: absolute; display: block; font: 16px / 16px 'Noto Sans TC', Arial; left: 312px; top: 75.6px; width: 32px; height: 16px; white-space: nowrap; transform: rotate(0deg); transform-origin: 50% 40%; color: rgb(255, 105, 180);">好的</span>
                        <span style="position: absolute; display: block; font: 16px / 16px 'Noto Sans TC', Arial; left: 426.982px; top: 34.6px; width: 48.0359px; height: 16px; white-space: nowrap; transform: rotate(0deg); transform-origin: 50% 40%; color: rgb(255, 105, 180);">AAAAA</span>
                        <span style="position: absolute; display: block; font: 16px / 16px 'Noto Sans TC', Arial; left: 312px; top: 157.6px; width: 32px; height: 16px; white-space: nowrap; transform: rotate(0deg); transform-origin: 50% 40%; color: rgb(255, 105, 180);">心得</span>
                        <span style="position: absolute; display: block; font: 16px / 16px 'Noto Sans TC', Arial; left: 387px; top: 198.6px; width: 128px; height: 16px; white-space: nowrap; transform: rotate(0deg); transform-origin: 50% 40%; color: rgb(255, 105, 180);">我覺得這一場很棒</span>
                        <span style="position: absolute; display: block; font: 16px / 16px 'Noto Sans TC', Arial; left: 518.559px; top: 75.6px; width: 28.8819px; height: 16px; white-space: nowrap; transform: rotate(0deg); transform-origin: 50% 40%; color: rgb(255, 105, 180);">AAA</span>
                        <span style="position: absolute; display: block; font: 16px / 16px 'Noto Sans TC', Arial; left: 230px; top: 116.6px; width: 32px; height: 16px; white-space: nowrap; transform: rotate(0deg); transform-origin: 50% 40%; color: rgb(255, 105, 180);">你好</span>
                    </div>
                </div>
            </div>
            <div id="cloud-article-list" style="margin-top:30px;"></div>
            <link rel="stylesheet" href="cloud.css">
            <script src="https://cdn.jsdelivr.net/npm/wordcloud@1.1.2/src/wordcloud2.min.js"></script>
            <script src="wordcloud.js"></script>
        </div>
    </div>
</div>

<!-- 新增分類對話框 -->
<div id="newCategoryModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:white;padding:24px;border-radius:8px;width:400px;max-width:90%;">
        <h3 style="margin:0 0 20px 0;">新增分類</h3>
        <input type="text" id="newCategoryName" placeholder="請輸入分類名稱" style="width:100%;padding:8px;margin-bottom:20px;border:1px solid #e5e7eb;border-radius:4px;">
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="closeNewCategoryModal()" class="secondary-btn">取消</button>
            <button onclick="saveNewCategory()" class="primary-btn">儲存</button>
        </div>
    </div>
</div>

<!-- 評論浮動框 -->
<div id="commentsFloat" style="display:none;position:fixed;top:0;right:0;height:100vh;background:white;padding:20px;box-shadow:-2px 0 10px rgba(0,0,0,0.1);width:350px;z-index:1000;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;position:sticky;top:0;background:white;padding-bottom:10px;border-bottom:1px solid #eee;">
        <h3 style="margin:0;color:#ff69b4;">👨‍🏫 老師評論</h3>
        <button onclick="hideComments()" style="background:none;border:none;font-size:1.5em;cursor:pointer;color:#666;">×</button>
    </div>
    <div id="teacherComments">
        <!-- 評論將由 JavaScript 動態填充 -->
    </div>
</div>

<script>
const categories = <?php echo json_encode($categories, JSON_UNESCAPED_UNICODE); ?>;
// 分類點擊
const postsByCategory = <?php echo json_encode($posts_by_category, JSON_UNESCAPED_UNICODE); ?>;
let currentCategory = 'all';
const articleList = document.getElementById('articleList');
const previewPanel = document.getElementById('previewPanel');
const categoryLis = document.querySelectorAll('#categoryList li');
let currentPostId = null;
const homePanel = document.getElementById('homePanel');
const notionMain = document.querySelector('.notion-main');

function renderHomeClouds() {
    fetch('generate_cloud.php')
        .then(r=>r.json())
        .then(data=>{
            if(data.error) {
                console.error('Error loading clouds:', data.error);
                return;
            }
            
            // 渲染標籤雲
            const tagCloud = document.getElementById('tagCloud');
            if(!data.tags || !data.tags.length) {
                tagCloud.innerHTML = "<div style='color:#bbb;text-align:center;margin-top:30px;'>尚無標籤資料</div>";
            } else {
                if(typeof WordCloud !== 'undefined') {
                    WordCloud(tagCloud, {
                        list: data.tags.map(t=>[t.tag, t.cnt]),
                        gridSize: Math.round(16 * tagCloud.offsetWidth / 320),
                        weightFactor: function(size) { return Math.max(16, size*8); },
                        fontFamily: 'Noto Sans TC, Arial',
                        color: '#3a4a8c',
                        backgroundColor: '#fff',
                        rotateRatio: 0.1,
                        minSize: 12,
                        click: function(item) {
                            filterCloudArticles('tag', item[0]);
                        }
                    });
                } else {
                    console.error('WordCloud is not defined');
                }
            }
            
            // 渲染關鍵詞雲
            const keyCloud = document.getElementById('keyCloud');
            if(!data.keywords || !data.keywords.length) {
                keyCloud.innerHTML = "<div style='color:#bbb;text-align:center;margin-top:30px;'>尚無關鍵詞資料</div>";
            } else {
                if(typeof WordCloud !== 'undefined') {
                    WordCloud(keyCloud, {
                        list: data.keywords,
                        gridSize: Math.round(16 * keyCloud.offsetWidth / 320),
                        weightFactor: function(size) { return Math.max(16, size*8); },
                        fontFamily: 'Noto Sans TC, Arial',
                        color: '#007bff',
                        backgroundColor: '#fff',
                        rotateRatio: 0.1,
                        minSize: 12,
                        click: function(item) {
                            filterCloudArticles('keyword', item[0]);
                        }
                    });
                } else {
                    console.error('WordCloud is not defined');
                }
            }
        })
        .catch(err => {
            console.error('Error fetching cloud data:', err);
        });
}

function filterCloudArticles(type, word) {
    let url = type==='tag' ? 'get_posts_by_tag.php?tag=' : 'get_posts_by_tag.php?keyword=';
    fetch(url+encodeURIComponent(word))
        .then(r=>r.text())
        .then(html=>{
            document.getElementById('cloud-article-list').innerHTML =
                `<h4 style='margin-bottom:10px;'>與「${escapeHtml(word)}」相關的文章</h4>` + html;
        })
        .catch(err => {
            console.error('Error fetching articles:', err);
        });
}

function escapeHtml(str) {
    return String(str||'').replace(/[&<>"]+/g, function(m) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m];
    });
}

function renderArticleListByCategory() {
    let key = String(currentCategory);
    let list = postsByCategory[key] || [];
    let html = '';
    if (list.length === 0) {
        html = '<li style="color:#aaa;text-align:center;padding:30px 0;">尚無文章</li>';
    } else {
        list.forEach(function(post){
            let summary = '';
            if(post.content) summary = String(post.content).replace(/<[^>]+>/g,'').replace(/\n/g,' ').slice(0,80);
            // 處理日期格式
            let postDate = post.post_date || post.created_at || '';
            if (postDate === '0000-00-00') {
                postDate = ''; // 如果是無效日期，顯示為空
            }
            html += `<li data-id="${post.id}" style="position:relative;">
                <div style='flex:1;min-width:0;'>
                  <div class="notion-article-title">${escapeHtml(post.title)||'<span style=\'color:#bbb\'>（無標題）</span>'}</div>
                  <div class="notion-article-summary" style="color:#888;font-size:0.97em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(summary)}</div>
                </div>
                <span class="article-date">${escapeHtml(postDate)}</span>
                ${post.is_locked ? '<span class=\'notion-article-lock\' title=\'已上鎖\'>🔒</span>' : ''}
            </li>`;
        });
    }
    const articleList = document.getElementById('articleList');
    if (articleList) {
        articleList.innerHTML = html;
    }
    bindArticleClick();
}

function bindArticleClick() {
    document.querySelectorAll('#articleList li[data-id]').forEach(function(li){
        // 左鍵點擊事件
        li.addEventListener('click', function(e){
            // 如果點擊的是右鍵選單，不觸發左鍵事件
            if (e.target.closest('.context-menu')) return;
            
            document.querySelectorAll('#articleList li').forEach(x=>x.classList.remove('active'));
            this.classList.add('active');
            let id = this.getAttribute('data-id');
            currentPostId = id;
            fetch('get_post_detail.php?post_id='+id)
                .then(r=>r.json())
                .then(data=>{
                    if(data.error) {
                        previewPanel.innerHTML = `<div style='color:#d00;text-align:center;margin-top:80px;font-size:1.1em;'>${data.error}</div>`;
                        return;
                    }
                    // 載入文章資料到表單
                    openEditPost(data);
                });
        });

        // 右鍵選單事件
        li.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            const menu = document.createElement('div');
            menu.style.cssText = `
                position: fixed;
                left: ${e.clientX}px;
                top: ${e.clientY}px;
                background: white;
                border: 1px solid #ddd;
                border-radius: 4px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                z-index: 1000;
            `;
            
            const deleteButton = document.createElement('div');
            deleteButton.style.cssText = `
                padding: 8px 16px;
                cursor: pointer;
                color: #ff69b4;
            `;
            deleteButton.textContent = '刪除文章';
            deleteButton.onmouseover = function() { this.style.background = '#ffe4f0'; };
            deleteButton.onmouseout = function() { this.style.background = 'white'; };
            deleteButton.onclick = function() {
                if (confirm('確定要刪除這篇文章嗎？')) {
                    fetch('delete_post.php?id=' + li.getAttribute('data-id'))
                    .then(response => {
                        if (response.redirected) {
                            window.location.href = response.url;
                        } else {
                            return response.json();
                        }
                    })
                    .then(data => {
                        if (data && data.success) {
                            li.remove();
                            // 如果刪除的是當前編輯的文章，清空預覽
                            if (currentPostId === li.getAttribute('data-id')) {
                                previewPanel.innerHTML = '';
                                document.getElementById('editPostPanel').style.display = 'none';
                            }
                        } else if (!data) {
                            // 重定向已處理
                        } else {
                            alert(data.error || '刪除失敗');
                        }
                    })
                    .catch(error => {
                        console.error('刪除失敗：', error);
                        // 如果刪除成功但返回了 HTML，也視為成功
                        if (error.message.includes('<!DOCTYPE')) {
                            li.remove();
                            if (currentPostId === li.getAttribute('data-id')) {
                                previewPanel.innerHTML = '';
                                document.getElementById('editPostPanel').style.display = 'none';
                            }
                        } else {
                            alert('刪除失敗：' + error);
                        }
                    });
                }
                menu.remove();
            };
            
            menu.appendChild(deleteButton);
            menu.className = 'context-menu';
            
            // 移除其他已存在的選單
            document.querySelectorAll('.context-menu').forEach(m => m.remove());
            
            document.body.appendChild(menu);
            
            // 點擊其他地方時關閉選單
            const closeMenu = function(e) {
                if (!menu.contains(e.target)) {
                    menu.remove();
                    document.removeEventListener('click', closeMenu);
                }
            };
            document.addEventListener('click', closeMenu);
        });
    });
}

// 圖片放大 modal
function showImageModal(src) {
    let modal = document.createElement('div');
    modal.style = 'position:fixed;z-index:9999;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;';
    modal.innerHTML = `<img src='${src}' style='max-width:90vw;max-height:90vh;border-radius:10px;box-shadow:0 2px 16px #0008;'><span style='position:absolute;top:30px;right:40px;font-size:2.2em;color:#fff;cursor:pointer;'>&times;</span>`;
    modal.addEventListener('click', function(e){
        if(e.target===modal || e.target.tagName==='SPAN') document.body.removeChild(modal);
    });
    document.body.appendChild(modal);
}

// 首次載入
bindArticleClick();

// 響應式調整
window.addEventListener('resize', function(){
    if (window.innerWidth < 900) {
        document.body.classList.add('mobile');
    } else {
        document.body.classList.remove('mobile');
    }
});

// 頁面載入時直接渲染全部文章
window.addEventListener('DOMContentLoaded', function(){
    // 獲取當前分類
    const activeCategory = document.querySelector('.notion-category-list li.active');
    if (activeCategory) {
        currentCategory = activeCategory.getAttribute('data-category');
    } else {
        currentCategory = 'home';
        // 如果沒有活動分類，設置首頁為活動狀態
        const homeBtn = document.querySelector('.notion-category-list .home-btn');
        if (homeBtn) {
            homeBtn.classList.add('active');
        }
    }

    // 根據當前分類顯示相應內容
    if(currentCategory === 'home') {
        notionMain.querySelector('#articleListBar').style.display = 'none';
        if (previewPanel) previewPanel.style.display = 'none';
        if (homePanel) homePanel.style.display = '';
        renderHomeClouds();
        // 隱藏編輯面板
        const editPanel = document.getElementById('editPostPanel');
        if (editPanel) {
            editPanel.style.display = 'none';
        }
    } else {
        notionMain.querySelector('#articleListBar').style.display = '';
        if (previewPanel) previewPanel.style.display = '';
        if (homePanel) homePanel.style.display = 'none';
        renderArticleListByCategory();
        if (previewPanel) previewPanel.innerHTML = '';
        // 顯示編輯面板
        const editPanel = document.getElementById('editPostPanel');
        if (editPanel) {
            editPanel.style.display = '';
        }
    }
});

function showCreatePost() {
    // 隱藏其他面板
    var listbar = document.querySelector('.notion-listbar');
    if (listbar) listbar.style.display = 'none';
    var preview = document.getElementById('previewPanel');
    if (preview) preview.style.display = 'none';
    var home = document.getElementById('homePanel');
    if (home) home.style.display = 'none';
    
    // 顯示編輯面板
    var editPanel = document.getElementById('editPostPanel');
    if (editPanel) {
        editPanel.style.display = '';
        // 清空表單
        document.getElementById('editPostId').value = '';
        document.getElementById('editTitle').value = '';
        document.getElementById('editDate').value = '';
        document.getElementById('editCategory').value = '';
        document.getElementById('editTags').value = '';
        document.getElementById('editLocked').checked = false;
        document.getElementById('editPrivate').checked = false;
        document.getElementById('editTeacherView').checked = false;
        document.getElementById('blockList').innerHTML = '';
    }
}

function hideCreatePost() {
    document.querySelector('.notion-listbar').style.display = '';
    document.getElementById('previewPanel').style.display = '';
    document.getElementById('createPostPanel').style.display = 'none';
}

let blockCount = 0;
function renderBlock(block, idx) {
    if (!block || !block.type) return null;
    const li = document.createElement('li');
    li.className = 'block-item';
    li.setAttribute('data-type', block.type);
    li.setAttribute('data-order', idx);
    li.draggable = true; // 使元素可拖動
    let html = '';
    
    if (block.type === 'text') {
        html += `<div class="text-toolbar" style="margin-bottom:4px;display:flex;gap:6px;">
            <button type="button" class="text-bold-btn" title="粗體"><b>B</b></button>
            <button type="button" class="text-italic-btn" title="斜體"><i>I</i></button>
            <button type="button" class="text-size-btn" data-size="big" title="大字">A+</button>
            <button type="button" class="text-size-btn" data-size="small" title="小字">A-</button>
        </div>
        <div class="rich-text-edit" contenteditable="true" style="min-height:1.5em;resize:vertical;padding:8px;font-size:1em;">${block.content || ''}</div>
        <textarea name="block_content[]" style="display:none;">${block.content || ''}</textarea>`;
    } else if (block.type === 'image') {
        const imgUrl = block.url || block.content || '';
        html += `<input type="file" name="block_image[]" accept="image/*" style="display:none;" id="image-${idx}" onchange="previewImage(this)">
            <div class="block-preview">${imgUrl ? `<img src="${imgUrl}" style="max-width:100%;">` : ''}</div>`;
        if (!imgUrl) {
            html += `<button type="button" class="image-upload-btn" onclick="document.getElementById('image-${idx}').click()" style="width:100%;padding:20px;background:transparent;cursor:pointer;color:#6b7280;">
                <span style="font-size:1.5em;">🖼️</span><br>點擊或拖放圖片到這裡
            </button>`;
        }
    } else if (block.type === 'file') {
        const fileUrl = block.url || block.content || '';
        html += `<input type="file" name="block_file[]" style="display:none;" id="file-${idx}">
            <div class="block-preview">${fileUrl ? `<a href="${fileUrl}" target="_blank">已上傳檔案</a>` : ''}</div>`;
        if (!fileUrl) {
            html += `<button type="button" class="file-upload-btn" onclick="document.getElementById('file-${idx}').click()" style="width:100%;padding:20px;background:transparent;cursor:pointer;color:#6b7280;">
                <span style="font-size:1.5em;">📎</span><br>點擊或拖放檔案到這裡
            </button>`;
        }
    }
    html += `<input type="hidden" name="block_type[]" value="${block.type}">`;
    li.innerHTML = html;
    
    // 綁定拖動事件
    li.addEventListener('dragstart', function(e) {
        e.dataTransfer.setData('text/plain', idx);
        this.classList.add('dragging');
    });
    
    li.addEventListener('dragend', function() {
        this.classList.remove('dragging');
    });

    // 綁定右鍵選單事件
    li.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        const menu = document.createElement('div');
        menu.style.cssText = `
            position: fixed;
            left: ${e.clientX}px;
            top: ${e.clientY}px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 1000;
        `;
        
        const removeButton = document.createElement('div');
        removeButton.style.cssText = `
            padding: 8px 16px;
            cursor: pointer;
            color: #ff69b4;
        `;
        removeButton.textContent = '移除模塊';
        removeButton.onmouseover = function() { this.style.background = '#ffe4f0'; };
        removeButton.onmouseout = function() { this.style.background = 'white'; };
        removeButton.onclick = function() {
            li.remove();
            menu.remove();
            if (typeof triggerAutosave === 'function') {
                triggerAutosave();
            }
        };
        
        menu.appendChild(removeButton);
        menu.className = 'context-menu';
        
        // 移除其他已存在的選單
        document.querySelectorAll('.context-menu').forEach(m => m.remove());
        
        document.body.appendChild(menu);
        
        // 點擊其他地方時關閉選單
        const closeMenu = function(e) {
            if (!menu.contains(e.target)) {
                menu.remove();
                document.removeEventListener('click', closeMenu);
            }
        };
        document.addEventListener('click', closeMenu);
    });
    
    // 綁定其他事件
    if (block.type === 'text') {
        const editor = li.querySelector('.rich-text-edit');
        const textarea = li.querySelector('textarea');
        const toolbar = li.querySelector('.text-toolbar');
        editor.addEventListener('input', function() { textarea.value = this.innerHTML; });
        toolbar.querySelector('.text-bold-btn').addEventListener('click', function() { document.execCommand('bold', false, null); editor.focus(); });
        toolbar.querySelector('.text-italic-btn').addEventListener('click', function() { document.execCommand('italic', false, null); editor.focus(); });
        toolbar.querySelectorAll('.text-size-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.execCommand('fontSize', false, this.dataset.size === 'big' ? '4' : '2');
                editor.focus();
            });
        });
    }
    return li;
}

function addBlock(type) {
    const blockList = document.getElementById('blockList');
    const idx = blockList.children.length;
    const block = {type: type, content: ''};
    const li = renderBlock(block, idx);
    if (li) blockList.appendChild(li);
}

function loadBlocks(blocks) {
    const blockList = document.getElementById('blockList');
    blockList.innerHTML = '';
    // 過濾掉內容完全重複的 block（type+content+url）
    const seen = new Set();
    (blocks || []).forEach((block, idx) => {
        if (block && block.type) {
            const key = block.type + '|' + (block.content || '') + '|' + (block.url || '');
            if (seen.has(key)) return;
            seen.add(key);
            const li = renderBlock(block, idx);
            if (li) blockList.appendChild(li);
        }
    });
}

// 匯出 PDF/ZIP 按鈕動態設置連結
function updateExportLinks(postId) {
    const pdfBtn = document.getElementById('exportPdfBtn');
    const zipBtn = document.getElementById('exportZipBtn');
    pdfBtn.href = 'export_pdf.php?id=' + postId;
    zipBtn.href = 'export_zip.php?id=' + postId;
    pdfBtn.style.pointerEvents = 'auto';
    pdfBtn.style.opacity = '1';
    zipBtn.style.pointerEvents = 'auto';
    zipBtn.style.opacity = '1';
    zipBtn.textContent = '匯出 TAR';
}

// --- 修正 openEditPost 錯誤 ---
if (typeof window.openEditPost !== 'function') {
    window.openEditPost = function(post) {
        // 預設：將 post 資料載入表單
        if (!post) return;
        document.getElementById('editPostId').value = post.id || '';
        document.getElementById('editTitle').value = post.title || '';
        
        // 處理日期格式
        let postDate = post.post_date || '';
        if (postDate === '0000-00-00' || !postDate) {
            postDate = new Date().toISOString().split('T')[0]; // 使用當前日期
        }
        document.getElementById('editDate').value = postDate;
        
        document.getElementById('editCategory').value = post.category_id || '';
        document.getElementById('editTags').value = (post.tags||[]).join(',');
        document.getElementById('editLocked').checked = !!post.is_locked;
        document.getElementById('editPrivate').checked = !!post.is_private;
        document.getElementById('editTeacherView').checked = !!post.allow_teacher_view;
        loadBlocks(post.blocks||[]);
        
        if(post.id) updateExportLinks(post.id);
    };
}
const origOpenEditPost = window.openEditPost;
window.openEditPost = function(post) {
    origOpenEditPost(post);
    if(post.id) updateExportLinks(post.id);
};

// --- 修正 closeEditPanel 未定義 ---
function closeEditPanel() {
    document.getElementById('editPostPanel').style.display = 'none';
    // 可選：顯示其他主面板
    if(document.getElementById('homePanel')) document.getElementById('homePanel').style.display = '';
    if(document.getElementById('previewPanel')) document.getElementById('previewPanel').style.display = '';
    if(document.querySelector('.notion-listbar')) document.querySelector('.notion-listbar').style.display = '';
}

// === 自動儲存功能 ===
(function(){
    const form = document.getElementById('postForm');
    const statusDiv = document.getElementById('autosave-status');
    let autosaveTimer = null;
    let lastData = '';
    let saving = false;
    function getFormData() {
        const fd = new FormData(form);
        document.querySelectorAll('#blockList .block-item').forEach((li, idx) => {
            const type = li.getAttribute('data-type');
            fd.append('block_type[]', type);
            if (type === 'text') {
                const editor = li.querySelector('.rich-text-edit');
                const content = editor ? editor.innerHTML : '';
                fd.append('block_content[]', content);
            } else {
                // 圖片/檔案也要佔位
                fd.append('block_content[]', '');
            }
            fd.append('block_sort_order[]', idx);
        });
        return fd;
    }
    function formToString() {
        // 只比對主要欄位和 block 內容
        let arr = [];
        arr.push(form.title.value||'');
        arr.push(form.post_date.value||'');
        arr.push(form.category_id.value||'');
        arr.push(form.tags.value||'');
        arr.push(form.is_locked.checked?'1':'0');
        arr.push(form.is_private.checked?'1':'0');
        arr.push(form.allow_teacher_view.checked?'1':'0');
        document.querySelectorAll('#blockList .block-item').forEach(li => {
            if (li.getAttribute('data-type') === 'text') {
                arr.push(li.querySelector('textarea').value);
            }
        });
        return arr.join('\x00');
    }
    function triggerAutosave() {
        if (saving) return;
        const nowData = formToString();
        if (nowData === lastData) return;
        if (autosaveTimer) clearTimeout(autosaveTimer);
        autosaveTimer = setTimeout(doAutosave, 3000); // 3秒後自動儲存
        statusDiv.textContent = '自動儲存中...';
    }
    function doAutosave() {
        saving = true;
        const fd = getFormData();
        fetch('save_post.php', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r=>r.json())
        .then(res=>{
            if(res.success) {
                statusDiv.textContent = '已自動儲存';
                if(form.post_id) form.post_id.value = res.post_id;
                lastData = formToString();
                if(typeof renderArticleListByCategory === 'function') renderArticleListByCategory();
            } else {
                statusDiv.textContent = '儲存失敗：'+(res.error||'未知錯誤');
            }
        })
        .catch(()=>{
            statusDiv.textContent = '儲存失敗（網路錯誤）';
        })
        .finally(()=>{
            saving = false;
        });
    }
    // 監聽所有欄位
    ['title','post_date','category_id','tags','is_locked','is_private','allow_teacher_view'].forEach(id=>{
        const el = form[id] || document.getElementById('edit'+id.charAt(0).toUpperCase()+id.slice(1));
        if(el) el.addEventListener('input', triggerAutosave);
        if(el && el.type==='checkbox') el.addEventListener('change', triggerAutosave);
    });
    // 監聽 block 內容
    document.getElementById('blockList').addEventListener('input', function(e){
        if(e.target.tagName==='TEXTAREA' || e.target.classList.contains('rich-text-edit')) triggerAutosave();
    });
    // 新增：監聽圖片/檔案 input 變動
    document.getElementById('blockList').addEventListener('change', function(e){
        if(e.target.type==='file') triggerAutosave();
    });
    // 新增/移除 block 也觸發
    const origAddBlock = window.addBlock;
    window.addBlock = function() {
        origAddBlock.apply(this, arguments);
        triggerAutosave();
    };
    window.removeBlock = function(element) {
        if (element) {
            element.remove();
            if (typeof triggerAutosave === 'function') {
                triggerAutosave();
            }
        }
    };
    // 禁用原 submit
    form.onsubmit = function(e){ e.preventDefault(); return false; };
})();

// === 編輯歷史 Undo/Redo 功能 ===
(function(){
    const form = document.getElementById('postForm');
    const blockList = document.getElementById('blockList');
    const undoBtn = document.getElementById('undoBtn');
    const redoBtn = document.getElementById('redoBtn');
    let history = [];
    let future = [];
    let isRestoring = false;
    function snapshot() {
        // 只記錄主要欄位和所有文字 block
        const data = {
            title: form.title.value,
            post_date: form.post_date.value,
            category_id: form.category_id.value,
            tags: form.tags.value,
            is_locked: form.is_locked.checked,
            is_private: form.is_private.checked,
            allow_teacher_view: form.allow_teacher_view.checked,
            blocks: Array.from(blockList.querySelectorAll('.block-item')).map(li => {
                return {
                    type: li.getAttribute('data-type'),
                    value: li.getAttribute('data-type')==='text' ? li.querySelector('textarea').value : ''
                };
            })
        };
        return JSON.stringify(data);
    }
    function restore(dataStr) {
        isRestoring = true;
        try {
            const data = JSON.parse(dataStr);
            form.title.value = data.title;
            form.post_date.value = data.post_date;
            form.category_id.value = data.category_id;
            form.tags.value = data.tags;
            form.is_locked.checked = data.is_locked;
            form.is_private.checked = data.is_private;
            form.allow_teacher_view.checked = data.allow_teacher_view;
            // blocks
            blockList.innerHTML = '';
            data.blocks.forEach(b => {
                addBlock(b.type);
                if(b.type==='text') {
                    blockList.querySelector('.block-item:last-child textarea').value = b.value;
                }
            });
        } catch(e) {}
        isRestoring = false;
    }
    function pushHistory() {
        if(isRestoring) return;
        history.push(snapshot());
        if(history.length>100) history.shift();
        future.length = 0;
        updateBtn();
    }
    function updateBtn() {
        undoBtn.disabled = history.length<=1;
        redoBtn.disabled = future.length===0;
    }
    // 監聽所有欄位
    ['title','post_date','category_id','tags','is_locked','is_private','allow_teacher_view'].forEach(id=>{
        const el = form[id] || document.getElementById('edit'+id.charAt(0).toUpperCase()+id.slice(1));
        if(el) el.addEventListener('input', pushHistory);
        if(el && el.type==='checkbox') el.addEventListener('change', pushHistory);
    });
    // 監聽 block 內容
    blockList.addEventListener('input', function(e){
        if(e.target.tagName==='TEXTAREA' || e.target.classList.contains('rich-text-edit')) pushHistory();
    });
    // 新增/移除 block 也觸發
    const origAddBlock = window.addBlock;
    window.addBlock = function() {
        origAddBlock.apply(this, arguments);
        pushHistory();
    };
    window.removeBlock = function(element) {
        if (element) {
            element.remove();
            pushHistory();
        }
    };
    // Undo/Redo 按鈕
    undoBtn.onclick = function(){
        if(history.length>1){
            future.push(history.pop());
            restore(history[history.length-1]);
            updateBtn();
        }
    };
    redoBtn.onclick = function(){
        if(future.length>0){
            const data = future.pop();
            history.push(data);
            restore(data);
            updateBtn();
        }
    };
    // 初始化
    history = [snapshot()];
    updateBtn();
})();

// 1. 加入 previewImage 函數（如未存在）
function previewImage(input) {
    const preview = input.parentNode.querySelector('.block-preview');
    preview.innerHTML = '';
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" style="max-width:100%;max-height:200px;">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// 2. 優化表單送出時 block_image 命名
// 在表單送出前，重新命名所有圖片/檔案 input
const postForm = document.getElementById('postForm');
if (postForm) {
    postForm.onsubmit = function() {
        const items = document.querySelectorAll('#blockList .block-item');
        document.querySelectorAll('input[name="block_sort_order[]"]').forEach(e => e.remove());
        let imgCount = 0, fileCount = 0;
        for (let i = 0; i < items.length; i++) {
            items[i].querySelector('input[name="block_type[]"]').value = items[i].getAttribute('data-type');
            let input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'block_sort_order[]';
            input.value = i;
            items[i].appendChild(input);
            let type = items[i].getAttribute('data-type');
            if (type === 'image') {
                let fileInput = items[i].querySelector('input[type="file"]');
                if (fileInput) fileInput.name = 'block_image[' + imgCount + ']';
                imgCount++;
            } else if (type === 'file') {
                let fileInput = items[i].querySelector('input[type="file"]');
                if (fileInput) fileInput.name = 'block_file[' + fileCount + ']';
                fileCount++;
            }
        }
        return true;
    };
}

// 修改預覽面板的處理
function updatePreviewPanel(content) {
    const previewPanel = document.getElementById('previewPanel');
    if (previewPanel) {
        previewPanel.innerHTML = content || '<div style="color:#aaa;text-align:center;margin-top:80px;">請點選左側文章以預覽內容</div>';
    }
}

// 修改分類點擊處理
categoryLis.forEach(function(li){
    li.addEventListener('click', function(){
        categoryLis.forEach(x=>x.classList.remove('active'));
        this.classList.add('active');
        currentCategory = this.getAttribute('data-category');
        const editPanel = document.getElementById('editPostPanel');
        
        if(currentCategory === 'home') {
            const articleListBar = notionMain.querySelector('#articleListBar');
            if (articleListBar) articleListBar.style.display = 'none';
            if (previewPanel) previewPanel.style.display = 'none';
            if (homePanel) homePanel.style.display = '';
            renderHomeClouds();
            if (editPanel) editPanel.style.display = 'none';
        } else {
            const articleListBar = notionMain.querySelector('#articleListBar');
            if (articleListBar) articleListBar.style.display = '';
            if (previewPanel) previewPanel.style.display = '';
            if (homePanel) homePanel.style.display = 'none';
            renderArticleListByCategory();
            updatePreviewPanel();
            if (editPanel) editPanel.style.display = '';
        }
    });
});

// 添加拖動排序相關的樣式和事件處理
document.addEventListener('DOMContentLoaded', function() {
    const blockList = document.getElementById('blockList');
    if (blockList) {
        blockList.addEventListener('dragover', function(e) {
            e.preventDefault();
            const draggingItem = document.querySelector('.dragging');
            if (!draggingItem) return;
            
            const siblings = [...this.querySelectorAll('.block-item:not(.dragging)')];
            const nextSibling = siblings.find(sibling => {
                const box = sibling.getBoundingClientRect();
                const offset = e.clientY - box.top - box.height / 2;
                return offset < 0;
            });
            
            this.insertBefore(draggingItem, nextSibling);
        });
        
        blockList.addEventListener('drop', function(e) {
            e.preventDefault();
            // 觸發自動保存
            if (typeof triggerAutosave === 'function') {
                triggerAutosave();
            }
        });
    }
});

// 新增分類相關函數
function showNewCategoryModal() {
    document.getElementById('newCategoryModal').style.display = 'block';
    document.getElementById('newCategoryName').value = '';
    document.getElementById('newCategoryName').focus();
}

function closeNewCategoryModal() {
    document.getElementById('newCategoryModal').style.display = 'none';
}

function saveNewCategory() {
    const name = document.getElementById('newCategoryName').value.trim();
    if (!name) {
        alert('請輸入分類名稱');
        return;
    }
    
    fetch('save_category.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'name=' + encodeURIComponent(name)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 添加新分類到下拉選單
            const select = document.getElementById('editCategory');
            const option = document.createElement('option');
            option.value = data.category_id;
            option.textContent = name;
            select.insertBefore(option, select.lastElementChild);
            // 關閉視窗
            closeNewCategoryModal();
            // 觸發自動保存
            if (typeof triggerAutosave === 'function') {
                triggerAutosave();
            }
        } else {
            alert(data.error || '儲存失敗');
        }
    })
    .catch(error => {
        alert('儲存失敗：' + error);
    });
}

// 修改分類選擇的處理
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('editCategory');
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            if (this.value === 'new') {
                showNewCategoryModal();
                this.value = ''; // 重置選擇
            }
        });
    }
});

// 授權請求函數
function requestAuthorization() {
    if (confirm('確定要請求教師授權嗎？')) {
        // 添加調試信息
        console.log('發送授權請求...');
        
        fetch('request_authorization.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                user_id: <?php echo $user_id; ?>
            })
        })
        .then(response => {
            console.log('收到響應:', response);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('處理數據:', data);
            if (data.success) {
                alert('授權請求已發送！');
            } else {
                alert('發送請求失敗：' + data.message);
            }
        })
        .catch(error => {
            console.error('錯誤詳情:', error);
            alert('發送請求時發生錯誤，請檢查控制台獲取詳細信息');
        });
    }
}

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