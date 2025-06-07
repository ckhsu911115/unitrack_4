<?php
// 設置 session 參數
ini_set('session.cookie_lifetime', 86400); // 24小時
ini_set('session.gc_maxlifetime', 86400); // 24小時
session_set_cookie_params(86400); // 24小時

// 關閉錯誤顯示，但記錄錯誤
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 設置錯誤處理函數
function handleError($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno] $errstr on line $errline in file $errfile");
    return true;
}
set_error_handler('handleError');

session_start();

// 檢查登入狀態
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit;
}

// 更新 session 時間戳
$_SESSION['last_activity'] = time();

// 檢查 session 是否過期（30分鐘無活動）
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    echo json_encode(['success' => false, 'message' => '登入已過期，請重新登入']);
    exit;
}

// 檢查數據庫配置文件
if (!file_exists('db.php')) {
    error_log("db.php not found");
    echo json_encode(['success' => false, 'message' => '系統配置錯誤']);
    exit;
}

require_once 'db.php';

// 檢查數據庫連接
if (!isset($pdo)) {
    error_log("Database connection not established");
    echo json_encode(['success' => false, 'message' => '數據庫連接錯誤']);
    exit;
}

// 確保輸出是 JSON
header('Content-Type: application/json');

try {
    // 檢查是否為 POST 請求
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('無效的請求方法');
    }

    // 獲取分類名稱
    $name = trim($_POST['name'] ?? '');

    // 驗證分類名稱
    if (empty($name)) {
        throw new Exception('分類名稱不能為空');
    }

    // 檢查 categories 表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'categories'");
    if ($stmt->rowCount() === 0) {
        // 如果表不存在，創建它
        $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_category_per_user (name, user_id)
        )");
    }

    // 檢查分類名稱是否已存在
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND user_id = ?");
    $stmt->execute([$name, $_SESSION['user']['id']]);
    if ($stmt->fetch()) {
        throw new Exception('此分類名稱已存在');
    }

    // 新增分類
    $stmt = $pdo->prepare("INSERT INTO categories (name, user_id) VALUES (?, ?)");
    if (!$stmt->execute([$name, $_SESSION['user']['id']])) {
        throw new Exception('新增分類失敗');
    }
    
    // 獲取新增的分類 ID
    $category_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'category_id' => $category_id,
        'message' => '分類新增成功'
    ]);

} catch (Exception $e) {
    error_log("Error in add_category.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Database error in add_category.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '資料庫操作失敗，請稍後再試'
    ]);
} 