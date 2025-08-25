<?php
session_start();
require_once '../config/db_connexion.php';

// ... (كود الحماية والتحقق من الصلاحيات) ...

if (isset($_FILES['audio_data']) && isset($_POST['group_id'])) {
    $group_id = $_POST['group_id'];
    $user_id = $_SESSION['user_id'];
    
    $upload_dir = '../uploads/chat_audio/';
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
    
    $file_name = uniqid('audio_') . '.webm';
    $target_file = $upload_dir . $file_name;

    // نقل الملف الصوتي المؤقت إلى المجلد الدائم
    if (move_uploaded_file($_FILES['audio_data']['tmp_name'], $target_file)) {
        // حفظ معلومات الرسالة في قاعدة البيانات
        $stmt = $pdo->prepare(
            "INSERT INTO chat_messages (group_id, user_id, message_type, file_path, file_name) 
             VALUES (?, ?, 'audio', ?, ?)"
        );
        $stmt->execute([$group_id, $user_id, 'uploads/chat_audio/' . $file_name, 'تسجيل صوتي']);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>