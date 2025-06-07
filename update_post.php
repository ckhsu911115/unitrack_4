<?php
require_once 'db.php';

$category_id = $_POST['category_id'] ?? null;
$title = $_POST['title'] ?? null;
$post_date = $_POST['post_date'] ?? null;
$is_locked = $_POST['is_locked'] ?? null;
$is_private = $_POST['is_private'] ?? null;
$allow_teacher_view = $_POST['allow_teacher_view'] ?? null;
$post_id = $_POST['post_id'] ?? null;
$user_id = $_POST['user_id'] ?? null;

$stmt = $pdo->prepare('UPDATE posts SET category_id=?, title=?, post_date=?, is_locked=?, is_private=?, allow_teacher_view=? WHERE id=? AND user_id=?');
$stmt->execute([$category_id, $title, $post_date, $is_locked, $is_private, $allow_teacher_view, $post_id, $user_id]); 