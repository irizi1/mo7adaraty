<?php
session_start();
// [تم التصحيح] استخدام مسار مطلق وآمن للوصول لملف الاتصال
require_once '../../config/db_connexion.php';
// ==========================================================
// 1. حماية الملف والتحقق من الصلاحيات
// ==========================================================
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'وصول غير مصرح به.']);
    exit();
}

// ==========================================================
// 2. استقبال البيانات من نموذج التعليق والتحقق منها
// ==========================================================
$user_id = $_SESSION['user_id'];
$content_id = filter_input(INPUT_POST, 'content_id', FILTER_VALIDATE_INT);
$content_type = $_POST['content_type'] ?? '';
$comment_text = trim($_POST['comment_text']);

// التحقق من أن جميع البيانات الضرورية موجودة وصحيحة
if (empty($comment_text) || !$content_id || !in_array($content_type, ['lecture', 'exam', 'post'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'بيانات غير صالحة.']);
    exit();
}

// ==========================================================
// 3. إضافة البيانات إلى قاعدة البيانات
// ==========================================================
try {
    $stmt = $pdo->prepare(
        "INSERT INTO comments (content_type, content_id, user_id, comment_text) 
         VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$content_type, $content_id, $user_id, $comment_text]);
    http_response_code(200);
    exit();

} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء حفظ التعليق.']);
    exit();
}
?>