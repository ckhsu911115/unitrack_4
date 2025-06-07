<?php
session_start();
require_once 'db.php';

$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($post_id <= 0) {
    echo '❌ 無效的文章ID。<br><a href="javascript:history.back()">返回上一頁</a>';
    exit;
}

// 取得文章
$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ?');
$stmt->execute([$post_id]);
$post = $stmt->fetch();
if (!$post) {
    echo '❌ 找不到該文章。<br><a href="javascript:history.back()">返回上一頁</a>';
    exit;
}

// 權限判斷
$can_view = false;
$reason = '';
if ($role === 'student' && $post['user_id'] == $user_id) {
    $can_view = true;
} elseif ($role === 'teacher') {
    if ($post['is_locked']) {
        $reason = '🔒 這篇文章已上鎖，無法查看。';
    } elseif (!$post['allow_teacher_view']) {
        $reason = '🚫 您未被授權觀看這篇內容。';
    } elseif ($post['is_private'] && !$post['is_locked']) {
        $can_view = true;
    } elseif (!$post['is_private']) {
        $can_view = true;
    } else {
        $reason = '🚫 您未被授權觀看這篇內容。';
    }
} else {
    // 未登入用戶只能查看公開且未上鎖的文章
    if (!$post['is_private'] && !$post['is_locked']) {
        $can_view = true;
    } else {
        $reason = '🔒 這篇文章需要登入才能查看。';
    }
}

// 取得 blocks
$blocks = [];
if ($can_view) {
    $stmt = $pdo->prepare('SELECT * FROM blocks WHERE post_id = ? ORDER BY block_order ASC');
    $stmt->execute([$post_id]);
    $blocks = $stmt->fetchAll();
}

// 取得留言
$comments = [];
if ($can_view) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM post_comments WHERE post_id = ? ORDER BY created_at DESC');
        $stmt->execute([$post_id]);
        $comments = $stmt->fetchAll();
    } catch (PDOException $e) {
        // 如果表不存在，創建表
        if ($e->getCode() == '42S02') {
            $pdo->exec('CREATE TABLE post_comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                post_id INT NOT NULL,
                teacher_name VARCHAR(255) NOT NULL,
                comment TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )');
            $comments = [];
        }
    }
}

// 留言送出處理
if ($can_view && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment']) && trim($_POST['comment']) !== '') {
    $comment = trim($_POST['comment']);
    $teacher_name = isset($_POST['teacher_name']) ? trim($_POST['teacher_name']) : '匿名教師';
    
    try {
        // 先檢查表是否存在，如果存在則刪除重建
        $pdo->exec('DROP TABLE IF EXISTS post_comments');
        $pdo->exec('CREATE TABLE post_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            teacher_name VARCHAR(255) NOT NULL,
            comment TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
        
        // 插入評論
        $stmt = $pdo->prepare('INSERT INTO post_comments (post_id, teacher_name, comment) VALUES (?, ?, ?)');
        $stmt->execute([$post_id, $teacher_name, $comment]);
        
        // 重新導向以避免重複提交
        header('Location: view_post.php?id='.$post_id);
        exit;
    } catch (PDOException $e) {
        echo "評論儲存失敗：" . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文章內容 - <?php echo htmlspecialchars($post['title']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Noto Sans TC', sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f3ff;
            color: #4a4a4a;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(149, 128, 255, 0.1);
            border: 1px solid #e8e0ff;
        }
        .post-title {
            font-size: 2em;
            color: #6b4eaf;
            margin-bottom: 15px;
        }
        .post-meta {
            color: #8a6dff;
            margin-bottom: 30px;
            font-size: 0.9em;
        }
        .post-content {
            line-height: 1.8;
            margin-bottom: 40px;
        }
        .post-content pre {
            white-space: pre-wrap;
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e8e0ff;
            margin: 10px 0;
        }
        .post-content img {
            max-width: 100%;
            border-radius: 8px;
            margin: 10px 0;
        }
        .comment-section {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e8e0ff;
        }
        .comment-form {
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e8e0ff;
        }
        .comment-input {
            width: 100%;
            min-height: 100px;
            padding: 15px;
            border: 1px solid #e8e0ff;
            border-radius: 8px;
            margin-bottom: 10px;
            font-family: inherit;
            font-size: 1em;
            background: white;
            resize: vertical;
        }
        .comment-input:focus {
            outline: none;
            border-color: #8a6dff;
            box-shadow: 0 0 0 3px rgba(138, 109, 255, 0.1);
        }
        .comment-submit {
            background-color: #8a6dff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .comment-submit:hover {
            background-color: #6b4eaf;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(149, 128, 255, 0.2);
        }
        .comment-list {
            margin-top: 30px;
        }
        .comment-item {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #e8e0ff;
        }
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #8a6dff;
            font-size: 0.9em;
        }
        .comment-content {
            line-height: 1.6;
            color: #4a4a4a;
        }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            color: #8a6dff;
            text-decoration: none;
            font-weight: 500;
        }
        .back-btn:hover {
            color: #6b4eaf;
        }
        .section-title {
            color: #6b4eaf;
            font-size: 1.5em;
            margin-bottom: 20px;
        }
        .highlight {
            background-color: rgba(249, 250, 251, 0.424);
            padding: 2px 4px;
            border-radius: 4px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($can_view): ?>
            <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
            <div class="post-meta">
                發布日期：<?php echo htmlspecialchars($post['post_date'] ?? $post['created_at']); ?>
            </div>
            
            <div class="post-content">
                <?php foreach ($blocks as $block): ?>
                    <?php if ($block['block_type'] === 'text'): ?>
                        <?php 
                        // 移除 HTML 標籤並保留換行
                        $content = strip_tags($block['content']);
                        echo '<div class="highlight">' . nl2br(htmlspecialchars($content)) . '</div>';
                        ?>
                    <?php elseif ($block['block_type'] === 'image'): ?>
                        <img src="<?php echo htmlspecialchars($block['content']); ?>" alt="文章圖片">
                    <?php elseif ($block['block_type'] === 'file'): ?>
                        <a href="<?php echo htmlspecialchars($block['content']); ?>" download>下載檔案</a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="comment-section">
                <h2 class="section-title">教師評論</h2>
                
                <form class="comment-form" method="post" action="view_post.php?id=<?php echo $post_id; ?>">
                    <div style="margin-bottom: 15px;">
                        <input type="text" name="teacher_name" class="comment-input" style="height: auto; min-height: 0;" placeholder="請輸入您的名字..." required>
                    </div>
                    <textarea name="comment" class="comment-input" placeholder="請輸入您的評論..." required></textarea>
                    <button type="submit" class="comment-submit">發表評論</button>
                </form>

                <div class="comment-list">
                    <?php if (count($comments) === 0): ?>
                        <p style="color: #8a6dff;">尚無教師評論</p>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment-item">
                                <div class="comment-header">
                                    <span><?php echo htmlspecialchars($comment['teacher_name']); ?></span>
                                    <span><?php echo $comment['created_at']; ?></span>
                                </div>
                                <div class="comment-content">
                                    <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <p><?php echo $reason; ?></p>
        <?php endif; ?>
        
        <a href="javascript:history.back()" class="back-btn">← 返回上一頁</a>
    </div>
</body>
</html> 