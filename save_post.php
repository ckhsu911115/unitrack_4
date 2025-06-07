<?php
// 設置 session 生命週期
ini_set('session.gc_maxlifetime', 86400); // 24 小時
ini_set('session.cookie_lifetime', 86400);
session_set_cookie_params(86400);

session_start();

// 檢查登入狀態
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['success'=>false,'error'=>'尚未登入']); exit;
    }
    header('Location: login.php');
    exit;
}

require_once 'db.php';
$user_id = $_SESSION['user']['id'];

// 更新 session 活動時間
$_SESSION['last_activity'] = time();

// 檢查 session 是否過期（30分鐘無活動）
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['success'=>false,'error'=>'Session 過期']); exit;
    }
    header('Location: login.php');
    exit;
}

// 文章屬性
$title = isset($_POST['title']) ? $_POST['title'] : '';
$post_date = isset($_POST['post_date']) ? $_POST['post_date'] : '';
$is_locked = isset($_POST['is_locked']) ? 1 : 0;
$is_private = isset($_POST['is_private']) ? 1 : 0;
$allow_teacher_view = isset($_POST['allow_teacher_view']) ? 1 : 0;
$category_id = $_POST['category_id'] ?? null;
if ($category_id === '' || $category_id === 'new' || !is_numeric($category_id)) {
    $category_id = null;
}
$post_id = $_POST['post_id'] ?? null;

// if (!$title || !$post_date) {
//     if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
//         echo json_encode(['success'=>false,'error'=>'標題與日期必填']); exit;
//     }
//     header('Location: create_post.php?error=1');
//     exit;
// }

try {
    if ($post_id) {
        // 編輯文章
        $stmt = $pdo->prepare('UPDATE posts SET category_id=?, title=?, post_date=?, is_locked=?, is_private=?, allow_teacher_view=? WHERE id=? AND user_id=?');
        $stmt->execute([$category_id, $title, $post_date, $is_locked, $is_private, $allow_teacher_view, $post_id, $user_id]);
        // 刪除舊標籤與 blocks
        $pdo->prepare('DELETE FROM post_tags WHERE post_id=?')->execute([$post_id]);
        $pdo->prepare('DELETE FROM blocks WHERE post_id=?')->execute([$post_id]);
    } else {
        // 新增文章
        $stmt = $pdo->prepare('INSERT INTO posts (user_id, category_id, title, created_at, post_date, is_locked, is_private, allow_teacher_view) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)');
        $stmt->execute([$user_id, $category_id, $title, $post_date, $is_locked, $is_private, $allow_teacher_view]);
        $post_id = $pdo->lastInsertId();
    }

    // 儲存標籤
    $tags = isset($_POST['tags']) ? $_POST['tags'] : '';
    if ($tags) {
        $tagArr = array_filter(array_map('trim', explode(',', $tags)));
        foreach ($tagArr as $tag) {
            $stmt = $pdo->prepare('INSERT INTO post_tags (post_id, tag) VALUES (?, ?)');
            $stmt->execute([$post_id, $tag]);
        }
    }

    // blocks
    $block_types = $_POST['block_type'] ?? [];
    $block_contents = $_POST['block_content'] ?? [];
    $block_sort_orders = $_POST['block_sort_order'] ?? [];
    $img_idx = 0;
    $file_idx = 0;
    foreach ($block_types as $i => $type) {
        $content = '';
        $sort_order = isset($block_sort_orders[$i]) ? intval($block_sort_orders[$i]) : $i;
        if (!$type) continue; // type 為空直接略過
        if ($type === 'text') {
            $content = $block_contents[$i] ?? '';
            if (trim($content) === '') continue; // 文字內容空白不存
        } elseif ($type === 'image') {
            if (isset($_FILES['block_image']['name'][$img_idx]) && $_FILES['block_image']['error'][$img_idx] === 0) {
                $origName = $_FILES['block_image']['name'][$img_idx];
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $img_idx++;
                    continue;
                }
                $filename = 'uploads/' . date('Ymd_His') . '_' . uniqid() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '', $origName);
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0777, true);
                }
                if (move_uploaded_file($_FILES['block_image']['tmp_name'][$img_idx], $filename)) {
                    $content = $filename;
                }
            }
            if (!$content) { $img_idx++; continue; } // 沒有圖片不存
            $img_idx++;
        } elseif ($type === 'file') {
            if (isset($_FILES['block_file']['name'][$file_idx]) && $_FILES['block_file']['error'][$file_idx] === 0) {
                $origName = $_FILES['block_file']['name'][$file_idx];
                $filename = 'uploads/' . date('Ymd_His') . '_' . uniqid() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '', $origName);
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0777, true);
                }
                if (move_uploaded_file($_FILES['block_file']['tmp_name'][$file_idx], $filename)) {
                    $content = $filename;
                }
            }
            if (!$content) { $file_idx++; continue; } // 沒有檔案不存
            $file_idx++;
        }
        if ($content !== '') {  // 只有當內容不為空時才插入
            $stmt = $pdo->prepare('INSERT INTO blocks (post_id, content, block_type, block_order) VALUES (?, ?, ?, ?)');
            $stmt->execute([$post_id, $content, $type, $sort_order]);
        }
    }

    if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['success'=>true,'post_id'=>$post_id]); exit;
    }
    header('Location: student_home.php');
    exit;
} catch(Exception $e) {
    if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]); exit;
    }
    exit($e->getMessage());
} 