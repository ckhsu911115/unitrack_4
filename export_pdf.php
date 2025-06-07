<?php
session_start();
require_once 'db.php';
// 支援兩種 session 結構
if (
    (isset($_SESSION['user']) && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'student')
) {
    $user_id = $_SESSION['user']['id'];
} elseif (
    (isset($_SESSION['role']) && $_SESSION['role'] === 'student') && isset($_SESSION['user_id'])
) {
    $user_id = $_SESSION['user_id'];
} else {
    header('Location: login.php');
    exit;
}
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($post_id <= 0) exit('無效文章');
// 取得文章
$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ?');
$stmt->execute([$post_id]);
$post = $stmt->fetch();
if (!$post || $post['user_id'] != $user_id) exit('未授權');
// 取得 blocks
$stmt = $pdo->prepare('SELECT * FROM blocks WHERE post_id = ? ORDER BY block_order ASC');
$stmt->execute([$post_id]);
$blocks = $stmt->fetchAll();
// 匯出 PDF (tFPDF)
require_once(__DIR__.'/tfpdf/tfpdf.php');
$pdf = new tFPDF();
$pdf->AddPage();
$pdf->AddFont('NotoSansTC', '', 'NotoSansTC-Regular.ttf', true);
$pdf->SetFont('NotoSansTC', '', 16);
$pdf->Cell(0,10,'學習紀錄',0,1,'C');
$pdf->SetFont('NotoSansTC', '', 12);
$pdf->Cell(0,10,'標題：'.strip_tags($post['title']),0,1);
$pdf->SetFont('NotoSansTC', '', 11);
$pdf->Cell(0,8,'日期：'.($post['post_date'] ?? $post['created_at']),0,1);
$pdf->Ln(2);
foreach ($blocks as $block) {
    if ($block['block_type'] === 'text') {
        $text = strip_tags($block['content']);
        $pdf->MultiCell(0,8,$text);
        $pdf->Ln(2);
    } elseif ($block['block_type'] === 'image') {
        $imgPath = __DIR__ . '/' . $block['content'];
        if (file_exists($imgPath)) {
            $pdf->Ln(2);
            $pdf->Image($imgPath, null, null, 100);
            $pdf->Ln(2);
        } else {
            $pdf->Cell(0,8,'[圖片檔案遺失] '.$block['content'],0,1);
        }
    } elseif ($block['block_type'] === 'file') {
        $pdf->Cell(0,8,'[附件檔案] '.$block['content'],0,1);
    }
}
$pdf->Output('D', 'unitrack_post_'.$post_id.'.pdf');
exit; 