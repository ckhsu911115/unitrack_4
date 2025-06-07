document.addEventListener('DOMContentLoaded', function(){
    // 單篇 PDF 匯出
    document.body.addEventListener('click', function(e){
        if(e.target.matches('.export-pdf-btn')){
            const postId = e.target.getAttribute('data-id');
            window.open('export_single.php?id='+postId, '_blank');
        }
        if(e.target.matches('.export-zip-btn')){
            const postId = e.target.getAttribute('data-id');
            window.open('export_zip.php?id='+postId, '_blank');
        }
    });
    // 全部 ZIP 匯出
    const allBtn = document.getElementById('export-all-btn');
    if(allBtn){
        allBtn.addEventListener('click', function(){
            window.open('export_all.php', '_blank');
        });
    }
});
function showToast(msg) {
    let t = document.createElement('div');
    t.textContent = msg;
    t.style = 'position:fixed;top:30px;right:30px;background:#2563eb;color:#fff;padding:12px 24px;border-radius:8px;z-index:9999;font-size:1.1em;box-shadow:0 2px 8px #0002;';
    document.body.appendChild(t);
    setTimeout(()=>{document.body.removeChild(t);}, 2500);
} 