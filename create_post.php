<?php
session_start();
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';
$user_id = $_SESSION['user']['id'];
// 取得分類
$catStmt = $pdo->prepare('SELECT id, name FROM categories WHERE user_id = ?');
$catStmt->execute([$user_id]);
$categories = $catStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>新增學習紀錄</title>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        .block-list { margin: 0; padding: 0; list-style: none; }
        .block-item { border: 1px solid #ccc; padding: 10px; margin-bottom: 8px; background: #fafafa; cursor: move; }
        .block-actions { margin-top: 5px; }
        .block-preview { margin-top: 5px; }
    </style>
</head>
<body>
    <h2>新增學習紀錄</h2>
    <form id="postForm" action="save_post.php" method="post" enctype="multipart/form-data">
        <label>分類：</label>
        <select name="category_id" required>
            <option value="">請選擇</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select><br><br>
        <label>標題：</label>
        <input type="text" name="title" required><br><br>
        <label>模板選擇：</label>
        <select id="templateSelect" onchange="applyTemplate()">
            <option value="">請選擇一個模板（可選）</option>
            <option value="note">📘 課堂筆記</option>
            <option value="activity">🎤 參加活動</option>
            <option value="project">🧪 專題成果</option>
        </select><br><br>
        <label>活動日期：</label>
        <input type="date" name="post_date" required><br><br>
        <label><input type="checkbox" name="is_locked" value="1"> 上鎖（僅本人可見）</label><br>
        <label><input type="checkbox" name="is_private" value="1"> 私人（不對外公開）</label><br>
        <label><input type="checkbox" name="allow_teacher_view" value="1"> 允許老師閱讀</label><br><br>
        <label>標籤（可多個，以逗號分隔）：</label>
        <input type="text" name="tags" placeholder="如：AI,心得,專題"><br><br>
        <h3>內容區塊</h3>
        <ul id="blockList" class="block-list"></ul>
        <button type="button" onclick="addBlock('text')">新增文字模塊</button>
        <button type="button" onclick="addBlock('image')">新增圖片模塊</button>
        <button type="button" onclick="addBlock('file')">新增檔案模塊</button>
        <br><br>
        <button type="submit">送出</button>
        <a href="student_home.php">取消</a>
    </form>
    <script>
    let blockCount = 0;
    function applyTemplate() {
        const ul = document.getElementById('blockList');
        ul.innerHTML = '';
        blockCount = 0;
        const val = document.getElementById('templateSelect').value;
        if (val === 'note') {
            addBlock('text', '請輸入課堂摘要');
            addBlock('text', '請輸入自我反思');
        } else if (val === 'activity') {
            addBlock('text', '活動名稱與時間');
            addBlock('image');
            addBlock('text', '參加心得');
        } else if (val === 'project') {
            addBlock('text', '專題背景說明');
            addBlock('image', '展示圖片');
            addBlock('file', '附上簡報或報告 PDF');
        }
    }
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
            html += '<b>圖片模塊</b><br><input type="file" name="block_image[]" accept="image/*" required onchange="previewImage(this)"><div class="block-preview"></div>';
            if (placeholder) html += '<div style="color:#888;font-size:0.95em;">'+placeholder+'</div>';
        } else if (type === 'file') {
            html += '<b>檔案模塊</b><br><input type="file" name="block_file[]" required>';
            if (placeholder) html += '<div style="color:#888;font-size:0.95em;">'+placeholder+'</div>';
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
    function previewImage(input) {
        const preview = input.parentNode.querySelector('.block-preview');
        preview.innerHTML = '';
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = '<img src="' + e.target.result + '" style="max-width:200px;max-height:120px;">';
            };
            reader.readAsDataURL(input.files[0]);
        }
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
    // 表單送出時依照排序補齊 block_type 與 sort_order
    document.getElementById('postForm').onsubmit = function() {
        const items = document.querySelectorAll('#blockList .block-item');
        // 先移除所有舊的 sort_order input
        document.querySelectorAll('input[name="block_sort_order[]"]').forEach(e => e.remove());
        let imgCount = 0, fileCount = 0;
        for (let i = 0; i < items.length; i++) {
            const type = items[i].getAttribute('data-type');
            items[i].querySelector('input[name="block_type[]"]').value = type;
            // 新增 sort_order input
            let input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'block_sort_order[]';
            input.value = i;
            items[i].appendChild(input);
            // 重新命名 file input
            if (type === 'image') {
                let fileInput = items[i].querySelector('input[type="file"]');
                if (fileInput) {
                    fileInput.name = 'block_image[' + imgCount + ']';
                    imgCount++;
                }
            } else if (type === 'file') {
                let fileInput = items[i].querySelector('input[type="file"]');
                if (fileInput) {
                    fileInput.name = 'block_file[' + fileCount + ']';
                    fileCount++;
                }
            }
        }
        return true;
    };
    </script>
</body>
</html> 