// 拖曳分隔線調整中間欄寬度
const resizer = document.getElementById('resizer');
const listbar = document.getElementById('articleListBar');
const preview = document.getElementById('previewPanel');
const collapseBtn = document.getElementById('collapseBtn');
let startX, startWidth, dragging = false, collapsed = false;

resizer.addEventListener('mousedown', function(e) {
  dragging = true;
  startX = e.clientX;
  startWidth = listbar.offsetWidth;
  resizer.classList.add('active');
  document.body.style.cursor = 'ew-resize';
  document.addEventListener('mousemove', onDrag);
  document.addEventListener('mouseup', stopDrag);
});

function onDrag(e) {
  if (!dragging) return;
  let dx = e.clientX - startX;
  let newWidth = Math.max(200, Math.min(600, startWidth + dx));
  listbar.style.width = newWidth + 'px';
}
function stopDrag() {
  dragging = false;
  resizer.classList.remove('active');
  document.body.style.cursor = '';
  document.removeEventListener('mousemove', onDrag);
  document.removeEventListener('mouseup', stopDrag);
}

// 預設顯示展開圖示
collapseBtn.innerHTML = `<svg width='28' height='28' viewBox='0 0 28 28'><circle cx='14' cy='14' r='13' fill='#e3eaff' stroke='#b4c6fc' stroke-width='2'/><polyline points='18,8 10,14 18,20' fill='none' stroke='#3a4a8c' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg>`;
collapseBtn.addEventListener('click', function(e) {
  e.stopPropagation();
  if (!collapsed) {
    listbar.style.display = 'none';
    collapsed = true;
    collapseBtn.innerHTML = `<svg width='28' height='28' viewBox='0 0 28 28'><circle cx='14' cy='14' r='13' fill='#e3eaff' stroke='#b4c6fc' stroke-width='2'/><polyline points='10,8 18,14 10,20' fill='none' stroke='#3a4a8c' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg>`;
  } else {
    listbar.style.display = '';
    listbar.style.width = '320px';
    collapsed = false;
    collapseBtn.innerHTML = `<svg width='28' height='28' viewBox='0 0 28 28'><circle cx='14' cy='14' r='13' fill='#e3eaff' stroke='#b4c6fc' stroke-width='2'/><polyline points='18,8 10,14 18,20' fill='none' stroke='#3a4a8c' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg>`;
  }
}); 