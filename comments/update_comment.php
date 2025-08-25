<?php
session_start();
require_once '../config/db_connexion.php';

// حماية الصفحة والتحقق من الصلاحيات
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 403 Forbidden');
    exit();
}

$user_id = $_SESSION['user_id'];
$comment_id = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
$comment_text = trim($_POST['comment_text']);

// التحقق من أن البيانات المطلوبة موجودة
if (!$comment_id || empty($comment_text)) {
    header('HTTP/1.1 400 Bad Request');
    exit();
}

try {
    // تحديث التعليق فقط إذا كان المستخدم الحالي هو صاحب التعليق
    $stmt = $pdo->prepare("UPDATE comments SET comment_text = ?, updated_at = NOW() WHERE comment_id = ? AND user_id = ?");
    $stmt->execute([$comment_text, $comment_id, $user_id]);

    // التحقق إذا تم تحديث أي صف
    if ($stmt->rowCount() > 0) {
        http_response_code(200); // OK
    } else {
        // لم يتم تحديث أي شيء، ربما لأن المستخدم ليس صاحب التعليق
        header('HTTP/1.1 403 Forbidden');
    }

} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
}
?>