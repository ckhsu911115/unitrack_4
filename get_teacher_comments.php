<?php
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_GET['post_id'])) {
    echo json_encode(['error' => 'Missing post_id parameter']);
    exit;
}

$post_id = intval($_GET['post_id']);

try {
    $stmt = $pdo->prepare("
        SELECT pc.*, u.username as teacher_name 
        FROM post_comments pc 
        JOIN users u ON pc.teacher_id = u.id 
        WHERE pc.post_id = ? 
        ORDER BY pc.created_at DESC
    ");
    $stmt->execute([$post_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'comments' => $comments]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 