<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.html');
    exit;
}
require_once 'db.php';

$stmt = $pdo->query("SELECT * FROM users ORDER BY id ASC");
$users = $stmt->fetchAll();

$deleted = isset($_GET['deleted']) ? true : false;
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>帳號管理</title>
    <link rel="stylesheet" href="layout.css">
    <style>
        body { padding: 20px; }
        .admin-panel { max-width: 1000px; margin: 0 auto; }
        .admin-button { 
            display: inline-block;
            padding: 10px 20px;
            margin: 10px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }
        .admin-button:hover {
            background: #1d4ed8;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #f8fafc;
            font-weight: 600;
        }
        tr:hover {
            background: #f8fafc;
        }
        .action-link {
            color: #2563eb;
            text-decoration: none;
            margin-right: 10px;
        }
        .action-link:hover {
            text-decoration: underline;
        }
        .delete-link {
            color: #dc2626;
        }
        .success-message {
            color: #059669;
            background: #ecfdf5;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="admin-panel">
        <h2>帳號管理</h2>
        <a href="admin_home.php" class="admin-button">回管理員首頁</a>
        
        <?php if ($deleted): ?>
            <div class="success-message">刪除成功！</div>
        <?php endif; ?>

        <table>
            <tr>
                <th>帳號</th>
                <th>Email</th>
                <th>角色</th>
                <th>操作</th>
            </tr>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo htmlspecialchars($user['role']); ?></td>
                <td>
                    <?php if ($user['role'] === 'student'): ?>
                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="action-link">編輯</a>
                        <a href="delete_user.php?id=<?php echo $user['id']; ?>" 
                           onclick="return confirm('確定要刪除這個學生帳號嗎？');" 
                           class="action-link delete-link">刪除</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html> 