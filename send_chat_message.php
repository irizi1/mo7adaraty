<?php
session_start();
require_once 'config/db_connexion.php';

// ==========================================================
// 1. حماية الملف والتحقق من الصلاحيات
// ==========================================================
if (isset($_SESSION['user_id']) && isset($_POST['message']) && isset($_POST['group_id'])) {
    
    // ==========================================================
    // 2. استقبال البيانات والتحقق منها
    // ==========================================================
    $user_id = $_SESSION['user_id'];
    $message = trim($_POST['message']);
    $group_id = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT);

    // التأكد من أن الرسالة ليست فارغة وأن معرّف المجموعة صحيح
    if (!empty($message) && $group_id) {
        try {
            // ==========================================================
            // 3. إضافة الرسالة إلى قاعدة البيانات
            // ==========================================================
            $stmt = $pdo->prepare("INSERT INTO chat_messages (group_id, user_id, message_text) VALUES (?, ?, ?)");
            $stmt->execute([$group_id, $user_id, $message]);
            
            // إرجاع استجابة نجاح
            echo json_encode(['status' => 'success']);
            exit();

        } catch (PDOException $e) {
            // في حال حدوث خطأ في قاعدة البيانات
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(['status' => 'error', 'message' => 'Database error.']);
            exit();
        }
    }
}

// ==========================================================
// 4. معالجة الوصول غير المصرح به
// ==========================================================
header('HTTP/1.1 403 Forbidden');
echo json_encode(['status' => 'error', 'message' => 'Unauthorized access or invalid data.']);
exit();
?>