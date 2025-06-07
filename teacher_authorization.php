<?php
session_start();
require_once 'db.php';

// 檢查登入狀態
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}

// 調試信息
error_log('Session data in teacher_authorization.php: ' . print_r($_SESSION, true));

$teacher_email = $_SESSION['user']['email'];

// 獲取待處理的授權請求
$stmt = $pdo->prepare('
    SELECT ar.*, u.name as student_name 
    FROM authorization_requests ar
    JOIN users u ON ar.student_id = u.id
    WHERE ar.email = ? AND ar.status = "pending"
    ORDER BY ar.created_at DESC
');
$stmt->execute([$teacher_email]);
$pending_requests = $stmt->fetchAll();

// 獲取已授權的學生列表
$stmt = $pdo->prepare('
    SELECT ta.*, u.name as student_name 
    FROM teacher_authorizations ta
    JOIN users u ON ta.student_id = u.id
    WHERE ta.email = ?
    ORDER BY ta.created_at DESC
');
$stmt->execute([$teacher_email]);
$authorized_students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授權管理 - UniTrack</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: #f5f6fa;
            font-family: 'Noto Sans TC', sans-serif;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        .header h1 {
            color: #2c3e50;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .header p {
            color: #7f8c8d;
            font-size: 1.1em;
        }
        .section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .section:hover {
            transform: translateY(-5px);
        }
        .section-title {
            font-size: 1.8em;
            margin-bottom: 25px;
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
            display: inline-block;
        }
        .request-item, .auth-item {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            transition: all 0.3s ease;
        }
        .request-item:hover, .auth-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #3498db;
        }
        .request-info, .auth-info {
            flex: 1;
        }
        .student-name {
            font-weight: bold;
            margin-bottom: 8px;
            color: #2c3e50;
            font-size: 1.2em;
        }
        .request-time {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        .auth-actions {
            display: flex;
            gap: 12px;
        }
        .approve-btn, .reject-btn, .remove-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 500;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .approve-btn {
            background-color: #2ecc71;
            color: white;
        }
        .reject-btn {
            background-color: #e74c3c;
            color: white;
        }
        .remove-btn {
            background-color: #f39c12;
            color: white;
        }
        .approve-btn:hover { 
            background-color: #27ae60;
            transform: translateY(-2px);
        }
        .reject-btn:hover { 
            background-color: #c0392b;
            transform: translateY(-2px);
        }
        .remove-btn:hover { 
            background-color: #d35400;
            transform: translateY(-2px);
        }
        .no-data {
            text-align: center;
            color: #7f8c8d;
            padding: 30px;
            font-size: 1.1em;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 20px 0;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 500;
            margin-left: 10px;
        }
        .status-pending {
            background-color: #f1c40f;
            color: #fff;
        }
        .status-approved {
            background-color: #2ecc71;
            color: #fff;
        }
        .status-rejected {
            background-color: #e74c3c;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>授權管理</h1>
            <p>管理學生的授權請求和已授權的學生列表</p>
        </div>

        <div class="section">
            <h2 class="section-title">待處理的授權請求</h2>
            <?php if (empty($pending_requests)): ?>
                <div class="no-data">目前沒有待處理的授權請求</div>
            <?php else: ?>
                <?php foreach ($pending_requests as $request): ?>
                    <div class="request-item">
                        <div class="request-info">
                            <div class="student-name">
                                <?php echo htmlspecialchars($request['student_name']); ?>
                                <span class="status-badge status-pending">待處理</span>
                            </div>
                            <div class="request-time">請求時間：<?php echo date('Y-m-d H:i', strtotime($request['created_at'])); ?></div>
                        </div>
                        <div class="auth-actions">
                            <button onclick="approveRequest(<?php echo $request['id']; ?>)" class="approve-btn">通過</button>
                            <button onclick="rejectRequest(<?php echo $request['id']; ?>)" class="reject-btn">拒絕</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2 class="section-title">已授權的學生</h2>
            <?php if (empty($authorized_students)): ?>
                <div class="no-data">目前沒有已授權的學生</div>
            <?php else: ?>
                <?php foreach ($authorized_students as $auth): ?>
                    <div class="auth-item">
                        <div class="auth-info">
                            <div class="student-name">
                                <?php echo htmlspecialchars($auth['student_name']); ?>
                                <span class="status-badge status-approved">已授權</span>
                            </div>
                            <div class="request-time">授權時間：<?php echo date('Y-m-d H:i', strtotime($auth['created_at'])); ?></div>
                        </div>
                        <div class="auth-actions">
                            <button onclick="removeAuth(<?php echo $auth['id']; ?>)" class="remove-btn">移除授權</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function approveRequest(requestId) {
        if (!confirm('確定要通過這個授權請求嗎？')) {
            return;
        }

        fetch('teacher_authorization_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'approve',
                request_id: requestId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('錯誤：' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('操作時發生錯誤');
        });
    }

    function rejectRequest(requestId) {
        if (!confirm('確定要拒絕這個授權請求嗎？')) {
            return;
        }

        fetch('teacher_authorization_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'reject',
                request_id: requestId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('錯誤：' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('操作時發生錯誤');
        });
    }

    function removeAuth(authId) {
        if (!confirm('確定要移除這個授權嗎？')) {
            return;
        }

        fetch('teacher_authorization_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'remove',
                auth_id: authId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('錯誤：' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('操作時發生錯誤');
        });
    }
    </script>
</body>
</html> 