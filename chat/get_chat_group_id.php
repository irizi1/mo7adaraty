<?php
session_start();
require_once '../config/db_connexion.php';

// ==========================================================
// 1. استقبال البيانات والتحقق منها
// ==========================================================
$class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);

if ($class_id) {
    try {
        // ==========================================================
        // 2. جلب معرّف مجموعة الدردشة من قاعدة البيانات
        // ==========================================================
        $stmt = $pdo->prepare("SELECT group_id FROM chat_groups WHERE class_id = ?");
        $stmt->execute([$class_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // ==========================================================
        // 3. إرجاع النتيجة بصيغة JSON
        // ==========================================================
        if ($result) {
            // تم العثور على المجموعة
            echo json_encode(['group_id' => $result['group_id']]);
        } else {
            // لم يتم العثور على مجموعة دردشة لهذا الفصل
            echo json_encode(['error' => 'Chat group not found for this class.']);
        }
    } catch (PDOException $e) {
        // في حال حدوث خطأ في قاعدة البيانات
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Database error.']);
    }
} else {
    // إذا لم يتم إرسال class_id
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid request.']);
}
?>