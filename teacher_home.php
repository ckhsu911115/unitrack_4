<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}
require_once 'db.php';

// 取得篩選條件
$search_student = isset($_GET['search_student']) ? trim($_GET['search_student']) : '';

// 修改 SQL 查詢以支持學生篩選
$sql = "SELECT p.id, p.title, p.created_at, p.post_date, p.user_id, u.username,
        (SELECT b.content FROM blocks b WHERE b.post_id = p.id AND b.block_type = 'text' ORDER BY b.block_order ASC LIMIT 1) as preview
        FROM posts p
        INNER JOIN users u ON p.user_id = u.id
        WHERE p.is_locked = 0 AND p.allow_teacher_view = 1";

$params = [];
if (!empty($search_student)) {
    $sql .= " AND u.username LIKE ?";
    $params[] = "%$search_student%";
}

$sql .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniTrack 教師平台</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Noto Sans TC', sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f3ff;
            color: #4a4a4a;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(149, 128, 255, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #e8e0ff;
        }
        .header h1 {
            margin: 0;
            color: #6b4eaf;
            font-size: 1.8em;
        }
        .nav-buttons {
            display: flex;
            gap: 15px;
        }
        .nav-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .auth-btn {
            background-color: #8a6dff;
            color: white;
        }
        .logout-btn {
            background-color: #ff6b6b;
            color: white;
        }
        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(149, 128, 255, 0.2);
        }
        .content-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(149, 128, 255, 0.1);
            border: 1px solid #e8e0ff;
        }
        .section-title {
            font-size: 1.5em;
            color: #6b4eaf;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #8a6dff;
            display: inline-block;
        }
        .post-block {
            border: 1px solid #e8e0ff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            background: #faf8ff;
        }
        .post-block:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(149, 128, 255, 0.15);
            border-color: #8a6dff;
        }
        .post-title {
            font-weight: bold;
            font-size: 1.2em;
            color: #6b4eaf;
            margin-bottom: 10px;
        }
        .post-meta {
            color: #8a6dff;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        .post-preview {
            color: #4a4a4a;
            margin: 15px 0;
            line-height: 1.6;
        }
        .view-btn {
            background-color: #8a6dff;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .view-btn:hover {
            background-color: #6b4eaf;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(149, 128, 255, 0.2);
        }
        .no-posts {
            text-align: center;
            color: #8a6dff;
            padding: 30px;
            background: #faf8ff;
            border-radius: 10px;
            margin: 20px 0;
            border: 1px solid #e8e0ff;
        }
        .search-section {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(149, 128, 255, 0.1);
            margin-bottom: 20px;
            border: 1px solid #e8e0ff;
        }
        .search-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .search-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #e8e0ff;
            border-radius: 8px;
            font-size: 1em;
            color: #4a4a4a;
            background: #faf8ff;
            transition: all 0.3s ease;
        }
        .search-input:focus {
            outline: none;
            border-color: #8a6dff;
            box-shadow: 0 0 0 3px rgba(138, 109, 255, 0.1);
        }
        .search-btn {
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
        .search-btn:hover {
            background-color: #6b4eaf;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(149, 128, 255, 0.2);
        }
        .clear-btn {
            background-color: #f5f3ff;
            color: #8a6dff;
            padding: 10px 20px;
            border: 1px solid #e8e0ff;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .clear-btn:hover {
            background-color: #e8e0ff;
            transform: translateY(-2px);
        }
        .search-result {
            color: #8a6dff;
            margin-top: 10px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>歡迎使用 UniTrack 教師平台</h1>
            <div class="nav-buttons">
                <a href="teacher_authorization.php" class="nav-btn auth-btn">授權管理</a>
                <a href="logout.php" class="nav-btn logout-btn">登出</a>
            </div>
        </div>

        <div class="search-section">
            <form class="search-form" method="GET">
                <input type="text" 
                       name="search_student" 
                       class="search-input" 
                       placeholder="輸入學生姓名搜尋..." 
                       value="<?php echo htmlspecialchars($search_student); ?>">
                <button type="submit" class="search-btn">搜尋</button>
                <?php if (!empty($search_student)): ?>
                    <a href="teacher_home.php" class="clear-btn">清除篩選</a>
                <?php endif; ?>
            </form>
            <?php if (!empty($search_student)): ?>
                <div class="search-result">
                    搜尋結果：<?php echo count($posts); ?> 篇文章
                </div>
            <?php endif; ?>
        </div>

        <div class="content-section">
            <h2 class="section-title">學生授權文章清單</h2>
            <?php if (count($posts) === 0): ?>
                <div class="no-posts">
                    <p><?php echo !empty($search_student) ? '找不到符合搜尋條件的文章。' : '目前沒有學生授權可瀏覽的文章。'; ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post-block">
                        <div class="post-title"><?php echo htmlspecialchars($post['title']); ?></div>
                        <div class="post-meta">
                            學生：<?php echo htmlspecialchars($post['username']); ?> ｜ 
                            發布日期：<?php echo htmlspecialchars($post['post_date'] ?? $post['created_at']); ?>
                        </div>
                        <div class="post-preview">
                            <?php echo nl2br(htmlspecialchars(mb_substr($post['preview'], 0, 150))); ?>...
                        </div>
                        <a href="view_post.php?id=<?php echo $post['id']; ?>" class="view-btn">查看完整內容</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 