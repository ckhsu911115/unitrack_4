document.addEventListener('DOMContentLoaded', function(){
    const timeline = document.getElementById('timeline');
    const container = document.getElementById('timeline-container');
    const nodes = timeline.querySelectorAll('.timeline-node');
    const summary = document.getElementById('timeline-summary');
    let activeNode = null;
    // 橫向滑動
    let isDown = false, startX, scrollLeft;
    container.addEventListener('mousedown', function(e){
        isDown = true;
        startX = e.pageX - container.offsetLeft;
        scrollLeft = container.scrollLeft;
        container.style.cursor = 'grabbing';
    });
    container.addEventListener('mouseleave', function(){ isDown = false; container.style.cursor = ''; });
    container.addEventListener('mouseup', function(){ isDown = false; container.style.cursor = ''; });
    container.addEventListener('mousemove', function(e){
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - container.offsetLeft;
        container.scrollLeft = scrollLeft - (x - startX);
    });
    // 滾輪橫向
    container.addEventListener('wheel', function(e){
        if (e.shiftKey || Math.abs(e.deltaX) > Math.abs(e.deltaY)) {
            container.scrollLeft += e.deltaY + e.deltaX;
            e.preventDefault();
        }
    }, {passive:false});
    // 節點互動
    nodes.forEach(function(node){
        node.addEventListener('mouseenter', function(){ node.classList.add('hover'); });
        node.addEventListener('mouseleave', function(){ node.classList.remove('hover'); });
        node.addEventListener('click', function(){
            nodes.forEach(n=>n.classList.remove('active'));
            node.classList.add('active');
            activeNode = node;
            let id = node.getAttribute('data-id');
            fetch('get_post_summary.php?id='+id)
                .then(r=>r.json())
                .then(data=>{
                    if(data.error) {
                        summary.innerHTML = `<div style='color:#d00;text-align:center;font-size:1.1em;'>${data.error}</div>`;
                        return;
                    }
                    let html = `<div class='timeline-summary-card' tabindex='0' data-id='${id}'>`;
                    html += `<div class='timeline-summary-title'>${escapeHtml(data.title)}</div>`;
                    html += `<div class='timeline-summary-date'>${escapeHtml(data.post_date)}</div>`;
                    if(data.tags && data.tags.length) {
                        html += `<div class='timeline-summary-tags'>`;
                        data.tags.forEach(function(tag){
                            html += `<span class='timeline-summary-tag'>#${escapeHtml(tag)}</span>`;
                        });
                        html += `</div>`;
                    }
                    html += `<div class='timeline-summary-content'>${escapeHtml(data.summary)}</div>`;
                    html += `<a class='timeline-summary-link' href='view_post.php?id=${id}' target='_blank'>閱讀全文 &rarr;</a>`;
                    html += `</div>`;
                    summary.innerHTML = html;
                    // 點擊卡片跳轉
                    const card = summary.querySelector('.timeline-summary-card');
                    card.addEventListener('click', function(e){
                        // 如果點擊的是連結，不處理
                        if (e.target.tagName === 'A') return;
                        window.open('view_post.php?id='+id, '_blank');
                    });
                    // 添加鍵盤支援
                    card.addEventListener('keydown', function(e){
                        if (e.key === 'Enter' || e.key === ' ') {
                            window.open('view_post.php?id='+id, '_blank');
                        }
                    });
                });
        });
        // 鍵盤可達
        node.addEventListener('keydown', function(e){
            if (e.key === 'Enter' || e.key === ' ') node.click();
        });
    });
    // 處理內聯卡片的點擊
    document.querySelectorAll('.timeline-inline-card').forEach(function(card) {
        card.addEventListener('click', function(e) {
            // 如果點擊的是連結，不處理
            if (e.target.tagName === 'A') return;
            const id = this.getAttribute('data-id');
            window.open('view_post.php?id='+id, '_blank');
        });
        // 添加鍵盤支援
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                const id = this.getAttribute('data-id');
                window.open('view_post.php?id='+id, '_blank');
            }
        });
    });
    // 預設選中最新
    if(nodes.length) nodes[nodes.length-1].click();
});
function escapeHtml(str) {
    return String(str||'').replace(/[&<>"']/g, function(m) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[m];
    });
} 