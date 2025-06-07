<?php
require_once(__DIR__.'/tfpdf/tfpdf.php');
class PDFGenerator extends tFPDF {
    function generate($title, $category, $tags, $blocks, $date) {
        $this->AddPage();
        $this->AddFont('MSJH','','msjh.ttf',true);
        $this->SetFont('MSJH','',18);
        $this->Cell(0,14,'📝 '.$title,0,1);
        $this->SetFont('MSJH','',12);
        $this->Cell(0,10,'分類：'.$category,0,1);
        $this->Cell(0,10,'標籤：'.implode(', ', $tags),0,1);
        $this->Cell(0,10,'日期：'.$date,0,1);
        $this->Ln(4);
        $this->SetFont('MSJH','',12);
        foreach ($blocks as $block) {
            if ($block['type'] === 'text') {
                $this->MultiCell(0,8,$block['content']);
                $this->Ln(2);
            } elseif ($block['type'] === 'image' && !empty($block['url'])) {
                $imgPath = __DIR__ . '/' . ltrim($block['url'], '/');
                if (file_exists($imgPath)) {
                    $w = 160; // 最大寬度 mm
                    $this->Image($imgPath, null, null, $w);
                    $this->Ln(6);
                }
            }
        }
    }
}
function generate_pdf($title, $category, $tags, $blocks, $date, $filepath) {
    $pdf = new PDFGenerator();
    $pdf->generate($title, $category, $tags, $blocks, $date);
    $pdf->Output('F', $filepath);
} 