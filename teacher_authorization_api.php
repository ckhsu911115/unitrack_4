<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// 檢查登入狀態
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    echo json_encode(['success' => false, 'message' => '未登入或權限不足']);
    exit;
}

require_once 'db.php';

try {
    // 獲取 POST 數據
    $data = json_decode(file_get_contents('php://input'), true);
    $teacher_email = $_SESSION['user']['email'];

    // 處理不同的操作
    switch ($data['action']) {
        case 'approve':
            // 開始事務
            $pdo->beginTransaction();

            try {
                // 獲取請求信息
                $stmt = $pdo->prepare('SELECT * FROM authorization_requests WHERE id = ? AND email = ? AND status = "pending"');
                $stmt->execute([$data['request_id'], $teacher_email]);
                $request = $stmt->fetch();

                if (!$request) {
                    throw new Exception('找不到有效的授權請求');
                }

                // 更新請求狀態
                $updateStmt = $pdo->prepare('UPDATE authorization_requests SET status = "approved" WHERE id = ?');
                $updateStmt->execute([$data['request_id']]);

                // 添加授權記錄
                $insertStmt = $pdo->prepare('INSERT INTO teacher_authorizations (student_id, email) VALUES (?, ?)');
                $insertStmt->execute([$request['student_id'], $teacher_email]);

                // 提交事務
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => '授權請求已通過']);
            } catch (Exception $e) {
                // 回滾事務
                $pdo->rollBack();
                throw $e;
            }
            break;

        case 'reject':
            // 更新請求狀態
            $stmt = $pdo->prepare('UPDATE authorization_requests SET status = "rejected" WHERE id = ? AND email = ? AND status = "pending"');
            $stmt->execute([$data['request_id'], $teacher_email]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => '授權請求已拒絕']);
            } else {
                echo json_encode(['success' => false, 'message' => '找不到有效的授權請求']);
            }
            break;

        case 'remove':
            // 移除授權
            $stmt = $pdo->prepare('DELETE FROM teacher_authorizations WHERE id = ? AND email = ?');
            $stmt->execute([$data['auth_id'], $teacher_email]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => '授權已移除']);
            } else {
                echo json_encode(['success' => false, 'message' => '找不到有效的授權記錄']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => '無效的操作']);
    }
} catch (Exception $e) {
    error_log('Teacher authorization error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '操作時發生錯誤']);
} 