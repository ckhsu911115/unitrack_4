document.addEventListener('DOMContentLoaded', function(){
    // 載入雲資料
    fetch('generate_cloud.php')
        .then(r=>r.json())
        .then(data=>{
            if(data.error) return;
            if(!data.tags || !data.tags.length){
                document.getElementById('tagCloud').innerHTML = "<div style='color:#bbb;text-align:center;margin-top:30px;'>尚無標籤資料</div>";
            }else{
                renderCloud('tagCloud', data.tags.map(t=>[t.tag, t.cnt]), 'tag');
            }
            if(!data.keywords || !data.keywords.length){
                document.getElementById('keyCloud').innerHTML = "<div style='color:#bbb;text-align:center;margin-top:30px;'>尚無關鍵詞資料</div>";
            }else{
                renderCloud('keyCloud', data.keywords, 'keyword');
            }
        });
});
function renderCloud(domId, list, type) {
    if(typeof WordCloud==='undefined') return;
    let el = document.getElementById(domId);
    if(!el) return;
    WordCloud(el, {
        list: list,
        gridSize: Math.round(16 * el.offsetWidth / 320),
        weightFactor: function(size) { return Math.max(16, size*8); },
        fontFamily: 'Noto Sans TC, Arial',
        color: function() { return type==='tag' ? '#3a4a8c' : '#007bff'; },
        backgroundColor: '#fff',
        rotateRatio: 0.1,
        minSize: 12,
        click: function(item) {
            filterCloudArticles(type, item[0]);
        }
    });
}
function filterCloudArticles(type, word) {
    let url = type==='tag' ? 'get_posts_by_tag.php?tag=' : 'get_posts_by_tag.php?keyword=';
    fetch(url+encodeURIComponent(word))
        .then(r=>r.text())
        .then(html=>{
            document.getElementById('cloud-article-list').innerHTML =
                `<h4 style='margin-bottom:10px;'>與「${escapeHtml(word)}」相關的文章</h4>` + html;
        });
}
function escapeHtml(str) {
    return String(str||'').replace(/[&<>"]+/g, function(m) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m];
    });
} 