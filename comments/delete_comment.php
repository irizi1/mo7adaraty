<?php
session_start();
require_once '../config/db_connexion.php';

if (!isset($_SESSION['user_id'])) { exit(); }

$comment_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($comment_id) {
    // التحقق من أن المستخدم هو صاحب التعليق
    $stmt = $pdo->prepare("DELETE FROM comments WHERE comment_id = ? AND user_id = ?");
    $stmt->execute([$comment_id, $_SESSION['user_id']]);
}
// إعادة التوجيه للصفحة السابقة
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();
?>