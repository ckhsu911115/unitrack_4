<?php
session_start();

// 檢查登入狀態
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header('Location: login.php');
    exit;
}

require_once 'db.php';
$user_id = $_SESSION['user']['id'];

// 取得授權列表
$authStmt = $pdo->prepare('SELECT * FROM teacher_authorizations WHERE student_id = ? ORDER BY created_at DESC');
$authStmt->execute([$user_id]);
$authorizations = $authStmt->fetchAll();

// 取得授權請求歷史
$requestStmt = $pdo->prepare('SELECT * FROM authorization_requests WHERE student_id = ? ORDER BY created_at DESC');
$requestStmt->execute([$user_id]);
$requests = $requestStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>授權管理</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            margin: 0;
            font-family: 'Noto Sans TC', Arial, sans-serif;
            background: #f7f8fa;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            color: #ff69b4;
        }
        .back-btn {
            background: #ff69b4;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
        }
        .back-btn:hover {
            background: #ff4da6;
        }
        .section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .section h2 {
            margin: 0 0 20px 0;
            color: #ff69b4;
            font-size: 1.5em;
        }
        .auth-list, .request-list {
            display: grid;
            gap: 15px;
        }
        .auth-item, .request-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .auth-info, .request-info {
            flex: 1;
        }
        .auth-email {
            font-weight: 500;
            color: #ff69b4;
            margin-bottom: 5px;
        }
        .auth-date {
            color: #666;
            font-size: 0.9em;
        }
        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .remove-btn:hover {
            background: #c82333;
        }
        .request-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: 500;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        .request-form {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .request-form input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
        }
        .request-form button {
            background: #ff69b4;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }
        .request-form button:hover {
            background: #ff4da6;
        }
        .request-form button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>授權管理</h1>
            <a href="student_home.php" class="back-btn">返回首頁</a>
        </div>

        <div class="section">
            <h2>當前授權</h2>
            <div class="auth-list">
                <?php if (empty($authorizations)): ?>
                    <div class="auth-item">
                        <div class="auth-info">
                            <div class="auth-email">尚無授權記錄</div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($authorizations as $auth): ?>
                        <div class="auth-item">
                            <div class="auth-info">
                                <div class="auth-email"><?php echo htmlspecialchars($auth['email']); ?></div>
                                <div class="auth-date">授權時間：<?php echo date('Y-m-d H:i', strtotime($auth['created_at'])); ?></div>
                            </div>
                            <button class="remove-btn" onclick="removeAuthorization(<?php echo $auth['id']; ?>)">移除授權</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="section">
            <h2>授權請求</h2>
            <div class="request-form">
                <input type="email" id="emailInput" placeholder="請輸入老師的電子郵件" required>
                <button onclick="requestAuthorization()" id="requestBtn">發送請求</button>
            </div>
            <div class="request-list">
                <?php if (empty($requests)): ?>
                    <div class="request-item">
                        <div class="request-info">
                            <div class="auth-email">尚無請求記錄</div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                        <div class="request-item">
                            <div class="request-info">
                                <div class="auth-email"><?php echo htmlspecialchars($request['email']); ?></div>
                                <div class="auth-date">請求時間：<?php echo date('Y-m-d H:i', strtotime($request['created_at'])); ?></div>
                            </div>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <div class="request-status status-<?php echo $request['status']; ?>">
                                    <?php
                                    switch($request['status']) {
                                        case 'pending':
                                            echo '等待審核';
                                            break;
                                        case 'approved':
                                            echo '已通過';
                                            break;
                                        case 'rejected':
                                            echo '已拒絕';
                                            break;
                                    }
                                    ?>
                                </div>
                                <?php if ($request['status'] === 'pending'): ?>
                                    <button class="remove-btn" onclick="cancelRequest(<?php echo $request['id']; ?>)">撤回請求</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function requestAuthorization() {
            const email = document.getElementById('emailInput').value;
            if (!email) {
                alert('請輸入電子郵件');
                return;
            }

            const btn = document.getElementById('requestBtn');
            btn.disabled = true;
            btn.textContent = '發送中...';

            fetch('request_authorization_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: email
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('請求已發送');
                    location.reload();
                } else {
                    alert(data.message || '發送請求失敗');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('發送請求時發生錯誤');
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = '發送請求';
            });
        }

        function removeAuthorization(authId) {
            if (!confirm('確定要移除這個授權嗎？')) {
                return;
            }

            fetch('request_authorization_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'remove',
                    auth_id: authId
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('授權已移除');
                    location.reload();
                } else {
                    alert(data.message || '移除授權失敗');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('移除授權時發生錯誤');
            });
        }

        function cancelRequest(requestId) {
            if (!confirm('確定要撤回這個請求嗎？')) {
                return;
            }

            fetch('request_authorization_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'cancel',
                    request_id: requestId
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('請求已撤回');
                    location.reload();
                } else {
                    alert(data.message || '撤回請求失敗');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('撤回請求時發生錯誤');
            });
        }
    </script>
</body>
</html> 