<?php
session_start();
require_once __DIR__ . '/db.php';

// 檢查是否為管理員
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// 獲取類型
$type = $_GET['type'] ?? '';

// 根據類型獲取數據
switch ($type) {
    case 'users':
        $stmt = $pdo->query("SELECT u.*, 
            (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as post_count
            FROM users u ORDER BY u.id DESC");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $title = '所有用戶';
        break;
    case 'students':
        $stmt = $pdo->query("SELECT u.*, 
            (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as post_count
            FROM users u WHERE u.role = 'student' ORDER BY u.id DESC");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $title = '學生列表';
        break;
    case 'teachers':
        $stmt = $pdo->query("SELECT u.*, 
            (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as post_count
            FROM users u WHERE u.role = 'teacher' ORDER BY u.id DESC");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $title = '教師列表';
        break;
    case 'admins':
        $stmt = $pdo->query("SELECT u.*, 
            (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as post_count
            FROM users u WHERE u.role = 'admin' ORDER BY u.id DESC");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $title = '管理員列表';
        break;
    case 'active_students':
        $stmt = $pdo->query("SELECT u.username, 
            (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as post_count
            FROM users u 
            WHERE u.role = 'student' 
            ORDER BY post_count DESC 
            LIMIT 10");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $title = '活躍學生列表';
        break;
    case 'posts':
        $stmt = $pdo->query("SELECT p.*, u.username, c.name as category_name 
            FROM posts p 
            LEFT JOIN users u ON p.user_id = u.id 
            LEFT JOIN categories c ON p.category_id = c.id 
            ORDER BY p.id DESC");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $title = '文章列表';
        break;
    case 'categories':
        $stmt = $pdo->query("SELECT c.*, 
            (SELECT COUNT(*) FROM posts WHERE category_id = c.id) as post_count 
            FROM categories c ORDER BY c.id DESC");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $title = '分類列表';
        break;
    case 'comments':
        $stmt = $pdo->query("SELECT * FROM post_comments ORDER BY id DESC");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $title = '評論列表';
        break;
    default:
        header('Location: admin_home.php');
        exit;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - 管理面板</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f4f6fb;
            font-family: 'Poppins', 'Noto Sans TC', sans-serif;
            margin: 0;
        }
        .main-card {
            background: #fff;
            border-radius: 32px;
            box-shadow: 0 8px 32px rgba(60,60,120,0.08);
            padding: 40px 32px;
            margin: 40px auto 60px auto;
            max-width: 1100px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        .header h1 {
            font-size: 2rem;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        .back-button {
            padding: 10px 24px;
            background-color: #bcb8f8;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            transition: background 0.2s;
        }
        .back-button:hover {
            background-color: #a89af5;
        }
        .detail-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 2px 8px rgba(60,60,120,0.08);
            overflow: hidden;
        }
        .detail-table th, .detail-table td {
            padding: 16px 12px;
            text-align: left;
        }
        .detail-table th {
            background-color: #f5f5f5;
            font-weight: 600;
            color: #333;
            font-size: 1.05rem;
        }
        .detail-table tr {
            border-bottom: 1px solid #eee;
        }
        .detail-table tr:last-child {
            border-bottom: none;
        }
        .detail-table tr:hover {
            background-color: #f9f9f9;
        }
        .button {
            padding: 7px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            margin-right: 6px;
            transition: background 0.2s;
        }
        .primary {
            background-color: #bcb8f8;
            color: white;
        }
        .primary:hover {
            background-color: #a89af5;
        }
        .danger {
            background-color: #f44336;
            color: white;
        }
        .danger:hover {
            background-color: #d32f2f;
        }
        .role-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.95em;
            font-weight: 500;
            color: #fff;
            margin-left: 4px;
        }
        .role-student { background: #bcb8f8; color: #222; }
        .role-teacher { background: #bcb8f8; color: #222; }
        .role-admin { background: #bcb8f8; color: #222; }
        .material-icons, svg {
            color: #222;
        }
        @media (max-width: 700px) {
            .main-card { padding: 16px 2px; }
            .header h1 { font-size: 1.2rem; }
            .detail-table th, .detail-table td { padding: 8px 4px; }
        }
    </style>
</head>
<body>
    <div class="main-card">
        <div class="header">
            <h1><?php echo $title; ?></h1>
            <a href="admin_home.php" class="back-button">返回管理面板</a>
        </div>
        <?php if ($type === 'users' || $type === 'students' || $type === 'teachers' || $type === 'admins'): ?>
            <table class="detail-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>用戶名</th>
                        <th>Email</th>
                        <th>身份</th>
                        <th>文章數</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo $user['role']; ?>">
                                    <?php echo $user['role'] === 'student' ? '學生' : ($user['role'] === 'teacher' ? '教師' : '管理員'); ?>
                                </span>
                            </td>
                            <td><?php echo $user['post_count']; ?></td>
                            <td>
                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="button primary">編輯</a>
                                <form method="POST" action="delete_user.php" style="display: inline;">
                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="button danger" onclick="return confirm('確定要刪除此用戶嗎？')">刪除</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($type === 'posts'): ?>
            <table class="detail-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>標題</th>
                        <th>作者</th>
                        <th>分類</th>
                        <th>發布時間</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $post): ?>
                        <tr>
                            <td><?php echo $post['id']; ?></td>
                            <td><?php echo htmlspecialchars($post['title']); ?></td>
                            <td><?php echo htmlspecialchars($post['username']); ?></td>
                            <td><?php echo htmlspecialchars($post['category_name']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($post['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($type === 'categories'): ?>
            <table class="detail-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>分類名稱</th>
                        <th>文章數</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $cat): ?>
                        <tr>
                            <td><?php echo $cat['id']; ?></td>
                            <td><?php echo htmlspecialchars($cat['name']); ?></td>
                            <td><?php echo $cat['post_count']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($type === 'comments'): ?>
            <table class="detail-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>內容</th>
                        <th>評論時間</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $comment): ?>
                        <tr>
                            <td><?php echo $comment['id']; ?></td>
                            <td><?php echo htmlspecialchars($comment['content']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($comment['created_at'])); ?></td>
                            <td>
                                <form method="POST" action="delete_comment.php" style="display: inline;">
                                    <input type="hidden" name="id" value="<?php echo $comment['id']; ?>">
                                    <button type="submit" class="button danger" onclick="return confirm('確定要刪除此評論嗎？')">刪除</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($type === 'active_students'): ?>
            <table class="detail-table">
                <thead>
                    <tr>
                        <th>學生名稱</th>
                        <th>文章數量</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['username']); ?></td>
                            <td><?php echo $student['post_count']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html> 