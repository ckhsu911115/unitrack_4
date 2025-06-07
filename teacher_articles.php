<?php
session_start();
require_once 'db.php';

// 檢查登入狀態
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}

// 檢查是否有學生ID參數
if (!isset($_GET['student_id'])) {
    header('Location: teacher_authorization.php');
    exit;
}

$teacher_email = $_SESSION['user']['email'];
$student_id = $_GET['student_id'];

// 檢查是否有授權
$stmt = $pdo->prepare('
    SELECT ta.*, u.name as student_name 
    FROM teacher_authorizations ta
    JOIN users u ON ta.student_id = u.id
    WHERE ta.email = ? AND ta.student_id = ?
');
$stmt->execute([$teacher_email, $student_id]);
$authorization = $stmt->fetch();

if (!$authorization) {
    header('Location: teacher_authorization.php');
    exit;
}

// 獲取學生的文章列表
$stmt = $pdo->prepare('
    SELECT a.*, c.name as category_name
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.user_id = ?
    ORDER BY a.created_at DESC
');
$stmt->execute([$student_id]);
$articles = $stmt->fetchAll();

// 調試信息
error_log('Teacher email: ' . $teacher_email);
error_log('Student ID: ' . $student_id);
error_log('Session data: ' . print_r($_SESSION, true));
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($authorization['student_name']); ?> 的文章列表 - UniTrack</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .back-btn {
            padding: 8px 15px;
            background-color: #666;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .back-btn:hover {
            background-color: #555;
        }
        .article-list {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .article-item {
            border: 1px solid #eee;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 10px;
        }
        .article-title {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        .article-meta {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        .article-content {
            color: #444;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        .article-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #666;
            font-size: 0.9em;
        }
        .category-tag {
            background-color: #e0e0e0;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
        }
        .no-articles {
            text-align: center;
            color: #666;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo htmlspecialchars($authorization['student_name']); ?> 的文章列表</h1>
            <a href="teacher_authorization.php" class="back-btn">返回授權管理</a>
        </div>

        <div class="article-list">
            <?php if (empty($articles)): ?>
                <div class="no-articles">該學生目前還沒有發布任何文章</div>
            <?php else: ?>
                <?php foreach ($articles as $article): ?>
                    <div class="article-item">
                        <div class="article-title"><?php echo htmlspecialchars($article['title']); ?></div>
                        <div class="article-meta">
                            <span class="category-tag"><?php echo htmlspecialchars($article['category_name'] ?? '未分類'); ?></span>
                        </div>
                        <div class="article-content">
                            <?php echo nl2br(htmlspecialchars($article['content'])); ?>
                        </div>
                        <div class="article-footer">
                            <div>發布時間：<?php echo date('Y-m-d H:i', strtotime($article['created_at'])); ?></div>
                            <?php if ($article['updated_at']): ?>
                                <div>最後更新：<?php echo date('Y-m-d H:i', strtotime($article['updated_at'])); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 