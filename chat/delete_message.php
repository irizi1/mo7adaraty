<?php
session_start();
require_once '../config/db_connexion.php';

// التحقق من أن المستخدم مسجل دخوله وأن الطلب صحيح
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 403 Forbidden');
    exit();
}

$user_id = $_SESSION['user_id'];
$message_id = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);

if ($message_id) {
    try {
        // حذف الرسالة فقط إذا كان المستخدم الحالي هو صاحبها
        $stmt = $pdo->prepare("DELETE FROM chat_messages WHERE message_id = ? AND user_id = ?");
        $stmt->execute([$message_id, $user_id]);

        // التحقق إذا تم حذف أي صف
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            // لم يتم الحذف، ربما لأن المستخدم ليس صاحب الرسالة
            echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية حذف هذه الرسالة.']);
        }
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
    }
} else {
    header('HTTP/1.1 400 Bad Request');
}
?>