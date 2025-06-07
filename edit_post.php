<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header('Location: index.html');
    exit;
}
$user_id = $_SESSION['user']['id'];
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($post_id <= 0) exit('無效文章');
// 取得文章
$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ? AND user_id = ?');
$stmt->execute([$post_id, $user_id]);
$post = $stmt->fetch();
if (!$post) exit('無權限或文章不存在');
// 取得 blocks
$stmt = $pdo->prepare('SELECT * FROM blocks WHERE post_id = ? ORDER BY block_order ASC');
$stmt->execute([$post_id]);
$blocks = $stmt->fetchAll();
// 取得分類
$catStmt = $pdo->prepare('SELECT id, name FROM categories WHERE user_id = ?');
$catStmt->execute([$user_id]);
$categories = $catStmt->fetchAll();
// 取得文章分類
$category_id = $post['category_id'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>編輯學習紀錄</title>
    <link rel="stylesheet" href="post.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="edit_post.js"></script>
    <style>
        .block-list { margin: 0; padding: 0; list-style: none; }
        .block-item { border: 1px solid #ccc; padding: 10px; margin-bottom: 8px; background: #fafafa; cursor: move; }
        .block-actions { margin-top: 5px; }
        .block-preview { margin-top: 5px; }
    </style>
</head>
<body>
    <h2>編輯學習紀錄</h2>
    <form id="postForm" action="update_post.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
        <div class="edit-form-row">
            <label>分類：</label>
            <select name="category_id" required>
                <option value="">請選擇</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $cat['id']==$category_id?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select><br>
            <label>標籤（可多個，以逗號分隔）：</label>
            <input type="text" name="tags" value="<?= htmlspecialchars(implode(',', $tags)) ?>"><br>
            <label>標題：</label>
            <input type="text" name="title" class="edit-title-input" value="<?=htmlspecialchars($post['title'])?>" required><br><br>
            <label>活動日期：</label>
            <input type="date" name="post_date" value="<?php echo htmlspecialchars($post['post_date']); ?>" required><br><br>
            <label><input type="checkbox" name="is_locked" value="1" <?php if($post['is_locked']) echo 'checked'; ?>> 上鎖（僅本人可見）</label><br>
            <label><input type="checkbox" name="is_private" value="1" <?php if($post['is_private']) echo 'checked'; ?>> 私人（不對外公開）</label><br>
            <label><input type="checkbox" name="allow_teacher_view" value="1" <?php if($post['allow_teacher_view']) echo 'checked'; ?>> 允許老師閱讀</label><br><br>
        </div>
        <h3>內容區塊</h3>
        <div id="blockList">
        <?php foreach ($blocks as $i => $block): ?>
            <div class="edit-block">
                <span class="block-grip">☰</span>
                <?php if ($block['block_type'] === 'text'): ?>
                    <textarea name="block_content[]" rows="3" style="width:90%" required><?php echo htmlspecialchars($block['content']); ?></textarea>
                    <input type="hidden" name="block_type[]" value="text">
                <?php elseif ($block['block_type'] === 'image'): ?>
                    <img src="<?php echo htmlspecialchars($block['content']); ?>" style="max-width:180px;max-height:120px;">
                    <input type="file" name="block_image[<?php echo $i; ?>]">
                    <input type="hidden" name="block_type[]" value="image">
                    <input type="hidden" name="block_image_old[]" value="<?php echo htmlspecialchars($block['content']); ?>">
                <?php elseif ($block['block_type'] === 'file'): ?>
                    <a href="<?php echo htmlspecialchars($block['content']); ?>" download>下載附件</a>
                    <input type="file" name="block_file[<?php echo $i; ?>]">
                    <input type="hidden" name="block_type[]" value="file">
                    <input type="hidden" name="block_file_old[]" value="<?php echo htmlspecialchars($block['content']); ?>">
                <?php endif; ?>
                <div class="block-actions">
                    <button type="button" class="block-delete-btn">刪除</button>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <button type="button" id="addTextBlockBtn">新增文字區塊</button>
        <button type="button" onclick="addBlock('image')">新增圖片模塊</button>
        <button type="button" onclick="addBlock('file')">新增檔案模塊</button>
        <div class="edit-toolbar">
            <button type="submit">儲存</button>
            <button type="button" onclick="window.open('export_single.php?id=<?=$post['id']?>')">匯出 PDF</button>
            <button type="button" onclick="window.open('export_zip.php?id=<?=$post['id']?>')">匯出 TAR</button>
        </div>
        <a href="student_home.php">取消</a>
    </form>
    <script>
    let blockCount = <?php echo count($blocks); ?>;
    function addBlock(type, placeholder = '') {
        const ul = document.getElementById('blockList');
        const li = document.createElement('li');
        li.className = 'block-item';
        li.setAttribute('data-type', type);
        li.setAttribute('data-order', blockCount);
        let html = '';
        if (type === 'text') {
            html += '<b>文字模塊</b><br><textarea name="block_content[]" rows="3" required placeholder="'+(placeholder||'')+'"></textarea>';
        } else if (type === 'image') {
            html += '<b>圖片模塊</b><br><input type="file" name="block_image['+blockCount+']" accept="image/*"><div class="block-preview"></div>';
        } else if (type === 'file') {
            html += '<b>檔案模塊</b><br><input type="file" name="block_file['+blockCount+']"><div class="block-preview"></div>';
        }
        html += '<input type="hidden" name="block_type[]" value="' + type + '">';
        html += '<div class="block-actions"><button type="button" onclick="removeBlock(this)">移除</button></div>';
        li.innerHTML = html;
        ul.appendChild(li);
        blockCount++;
    }
    function removeBlock(btn) {
        btn.closest('li').remove();
    }
    // 拖曳排序
    new Sortable(document.getElementById('blockList'), {
        animation: 150,
        handle: '.block-item',
        ghostClass: 'sortable-ghost',
        onEnd: function () {
            updateBlockOrder();
        }
    });
    function updateBlockOrder() {
        const items = document.querySelectorAll('#blockList .block-item');
        items.forEach(function(li, idx) {
            li.setAttribute('data-order', idx);
        });
    }
    document.getElementById('postForm').onsubmit = function() {
        const items = document.querySelectorAll('#blockList .block-item');
        document.querySelectorAll('input[name="block_sort_order[]"]').forEach(e => e.remove());
        let imgCount = 0, fileCount = 0;
        for (let i = 0; i < items.length; i++) {
            items[i].querySelector('input[name="block_type[]"]').value = items[i].getAttribute('data-type');
            let input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'block_sort_order[]';
            input.value = i;
            items[i].appendChild(input);
            let type = items[i].getAttribute('data-type');
            if (type === 'image') {
                let fileInput = items[i].querySelector('input[type="file"]');
                if (fileInput) fileInput.name = 'block_image[' + imgCount + ']';
                imgCount++;
            } else if (type === 'file') {
                let fileInput = items[i].querySelector('input[type="file"]');
                if (fileInput) fileInput.name = 'block_file[' + fileCount + ']';
                fileCount++;
            }
        }
        return true;
    };
    </script>
</body>
</html> 