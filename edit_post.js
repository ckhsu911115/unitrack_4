// 載入 SortableJS
// <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
document.addEventListener('DOMContentLoaded', function(){
  var blockList = document.getElementById('blockList');
  if(blockList){
    new Sortable(blockList, {
      handle: '.block-grip',
      animation: 180,
      onEnd: function(){
        // 可選：自動儲存順序
      }
    });
  }
  // 刪除模塊
  blockList.addEventListener('click', function(e){
    if(e.target.classList.contains('block-delete-btn')){
      e.target.closest('.edit-block').remove();
    }
  });
  // 新增模塊（範例：新增文字）
  document.getElementById('addTextBlockBtn')?.addEventListener('click', function(){
    var div = document.createElement('div');
    div.className = 'edit-block';
    div.innerHTML = `<span class='block-grip'>☰</span><textarea name='block_content[]' rows='3' style='width:90%'></textarea><div class='block-actions'><button type='button' class='block-delete-btn'>刪除</button></div><input type='hidden' name='block_type[]' value='text'>`;
    blockList.appendChild(div);
  });
});

function closeEditPanel() {
    const editPanel = document.getElementById('editPostPanel');
    if (editPanel) {
        editPanel.style.display = 'none';
    }
}

// 點擊面板外部區域關閉面板
document.addEventListener('click', function(event) {
    const editPanel = document.getElementById('editPostPanel');
    const closeBtn = document.querySelector('.close-btn');
    
    // 如果點擊的不是面板內部元素，也不是關閉按鈕，則關閉面板
    if (editPanel && !editPanel.contains(event.target) && event.target !== closeBtn) {
        editPanel.style.display = 'none';
    }
});

// 監聽分類點擊事件
document.querySelectorAll('#categoryList li').forEach(function(li) {
    li.addEventListener('click', function() {
        const editPanel = document.getElementById('editPostPanel');
        if (editPanel) {
            editPanel.style.display = 'none';
        }
    });
});

// 設置編輯面板的樣式
document.addEventListener('DOMContentLoaded', function() {
    const editPanel = document.getElementById('editPostPanel');
    if (editPanel) {
        editPanel.style.position = 'fixed';
        editPanel.style.top = '0';
        editPanel.style.left = '0';
        editPanel.style.width = '0';
        editPanel.style.height = '0';
        editPanel.style.zIndex = '-1'; // 設置為負值，確保在底層
        editPanel.style.opacity = '0'; // 完全透明
        editPanel.style.visibility = 'hidden'; // 隱藏元素
        editPanel.style.pointerEvents = 'none'; // 禁用所有交互
    }
}); 