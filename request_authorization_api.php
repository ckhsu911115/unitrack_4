<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// 檢查登入狀態
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => '未登入或權限不足']);
    exit;
}

require_once 'db.php';

try {
    // 獲取 POST 數據
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $_SESSION['user']['id'];

    // 處理不同的操作
    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'remove':
                // 移除授權
                $stmt = $pdo->prepare('DELETE FROM teacher_authorizations WHERE id = ? AND student_id = ?');
                $stmt->execute([$data['auth_id'], $user_id]);
                echo json_encode(['success' => true, 'message' => '授權已移除']);
                break;

            case 'cancel':
                // 撤回請求
                $stmt = $pdo->prepare('DELETE FROM authorization_requests WHERE id = ? AND student_id = ? AND status = "pending"');
                $stmt->execute([$data['request_id'], $user_id]);
                echo json_encode(['success' => true, 'message' => '請求已撤回']);
                break;

            default:
                echo json_encode(['success' => false, 'message' => '無效的操作']);
        }
        exit;
    }

    // 檢查是否已有授權
    $checkAuthStmt = $pdo->prepare('SELECT id FROM teacher_authorizations WHERE student_id = ? AND email = ?');
    $checkAuthStmt->execute([$user_id, $data['email']]);
    if ($checkAuthStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => '該老師已有授權']);
        exit;
    }

    // 檢查是否已有相同郵箱的待處理請求
    $checkStmt = $pdo->prepare('SELECT id FROM authorization_requests WHERE student_id = ? AND email = ? AND status = "pending"');
    $checkStmt->execute([$user_id, $data['email']]);
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => '您已向該老師發送過請求']);
        exit;
    }

    // 插入新的授權請求
    $stmt = $pdo->prepare('INSERT INTO authorization_requests (student_id, email) VALUES (?, ?)');
    $stmt->execute([$user_id, $data['email']]);

    echo json_encode(['success' => true, 'message' => '授權請求已發送']);
} catch (Exception $e) {
    error_log('Authorization request error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '操作時發生錯誤']);
} 