<?php
// 啟用錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 設置響應頭
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Accept');

session_start();

// 檢查登入狀態
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '未登入或權限不足']);
    exit;
}

require_once 'db.php';

try {
    // 獲取 POST 數據
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // 調試信息
    error_log('Received data: ' . print_r($data, true));
    
    $user_id = $data['user_id'] ?? null;

    if (!$user_id) {
        throw new Exception('缺少必要參數');
    }

    // 檢查是否已經有未處理的授權請求
    $checkStmt = $pdo->prepare('SELECT id FROM authorization_requests WHERE student_id = ? AND status = "pending"');
    $checkStmt->execute([$user_id]);
    
    if ($checkStmt->rowCount() > 0) {
        throw new Exception('您已經有一個待處理的授權請求');
    }

    // 創建新的授權請求
    $stmt = $pdo->prepare('INSERT INTO authorization_requests (student_id, status, created_at) VALUES (?, "pending", NOW())');
    $stmt->execute([$user_id]);

    echo json_encode([
        'success' => true, 
        'message' => '授權請求已成功發送',
        'request_id' => $pdo->lastInsertId()
    ]);

} catch (Exception $e) {
    error_log('Error in request_authorization.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'error_details' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log('Database error in request_authorization.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => '數據庫錯誤',
        'error_details' => $e->getMessage()
    ]);
}
?> 