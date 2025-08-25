<?php
session_start();
require_once 'config/db_connexion.php';

// التحقق من الصلاحيات
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 403 Forbidden');
    exit();
}

$user_id = $_SESSION['user_id'];
$message_id = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);
$new_text = trim($_POST['text']);

if ($message_id && !empty($new_text)) {
    try {
        // تحديث الرسالة فقط إذا كان المستخدم هو صاحبها
        $stmt = $pdo->prepare("UPDATE chat_messages SET message_text = ? WHERE message_id = ? AND user_id = ?");
        $stmt->execute([$new_text, $message_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية تعديل هذه الرسالة.']);
        }
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
    }
} else {
    header('HTTP/1.1 400 Bad Request');
}
?>