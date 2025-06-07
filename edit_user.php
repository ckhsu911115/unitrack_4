<?php
session_start();
require_once 'db.php';

// 檢查是否為管理員
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// 獲取用戶ID
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($user_id <= 0) {
    header('Location: admin_home.php');
    exit;
}

// 獲取用戶信息
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: admin_home.php');
    exit;
}

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = trim($_POST['password']);

    // 驗證輸入
    $errors = [];
    if (empty($username)) {
        $errors[] = '用戶名不能為空';
    }
    if (empty($email)) {
        $errors[] = 'Email不能為空';
    }
    if (!in_array($role, ['student', 'teacher', 'admin'])) {
        $errors[] = '無效的角色';
    }

    // 檢查用戶名是否已存在（排除當前用戶）
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
    $stmt->execute([$username, $user_id]);
    if ($stmt->fetch()) {
        $errors[] = '用戶名已存在';
    }

    if (empty($errors)) {
        try {
            if (!empty($password)) {
                // 更新包括密碼
                $stmt = $pdo->prepare('UPDATE users SET username = ?, email = ?, role = ?, password = ? WHERE id = ?');
                $stmt->execute([$username, $email, $role, $password, $user_id]);
            } else {
                // 不更新密碼
                $stmt = $pdo->prepare('UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?');
                $stmt->execute([$username, $email, $role, $user_id]);
            }
            header('Location: show_details.php?type=users');
            exit;
        } catch (PDOException $e) {
            $errors[] = '更新失敗：' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>編輯用戶 - 管理面板</title>
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .error {
            color: #dc3545;
            margin-bottom: 15px;
        }
        .button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .primary {
            background-color: #bcb8f8;
            color: white;
        }
        .primary:hover {
            background-color: #a89af5;
        }
        .secondary {
            background-color: #6c757d;
            color: white;
            margin-left: 10px;
        }
        .button:hover {
            opacity: 0.9;
        }
        .back-button {
            background-color: #bcb8f8;
            color: white;
        }
        .back-button:hover {
            background-color: #a89af5;
        }
        .role-badge {
            background: #bcb8f8;
            color: #222;
        }
        .material-icons, svg {
            color: #222;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>編輯用戶</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">用戶名</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="role">角色</label>
                <select id="role" name="role" required>
                    <option value="student" <?php echo $user['role'] === 'student' ? 'selected' : ''; ?>>學生</option>
                    <option value="teacher" <?php echo $user['role'] === 'teacher' ? 'selected' : ''; ?>>教師</option>
                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>管理員</option>
                </select>
            </div>

            <div class="form-group">
                <label for="password">新密碼（留空表示不修改）</label>
                <input type="password" id="password" name="password">
            </div>

            <button type="submit" class="button primary">保存更改</button>
            <a href="show_details.php?type=users" class="button secondary">返回</a>
        </form>
    </div>
</body>
</html> 