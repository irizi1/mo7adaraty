<?php
session_start();
require_once 'config/db_connexion.php';

// التحقق من أن المستخدم مسجل دخوله وأن الطلب صحيح
if (isset($_SESSION['user_id']) && isset($_POST['message']) && isset($_POST['group_id'])) {
    $user_id = $_SESSION['user_id'];
    $message = trim($_POST['message']);
    $group_id = $_POST['group_id'];

    if (!empty($message)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO chat_messages (group_id, user_id, message_text) VALUES (?, ?, ?)");
            $stmt->execute([$group_id, $user_id, $message]);
            echo json_encode(['status' => 'success']);
        } catch (PDOException $e) {
            // يمكنك تسجيل الخطأ هنا إذا أردت
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(['status' => 'error', 'message' => 'Database error.']);
        }
    }
} else {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
}
?>