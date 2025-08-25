<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// استقبال البيانات
$subject = trim($_POST['message_subject']);
$content = trim($_POST['message_content']);

if (empty($subject) || empty($content)) {
    $_SESSION['contact_msg'] = ['type' => 'error', 'message' => 'الرجاء ملء جميع الحقول.'];
    header("Location: index.php");
    exit();
}

try {
    // إدراج الرسالة في قاعدة البيانات
    $stmt = $pdo->prepare(
        "INSERT INTO admin_messages (student_user_id, message_subject, message_content) VALUES (?, ?, ?)"
    );
    $stmt->execute([$user_id, $subject, $content]);

    $_SESSION['contact_msg'] = ['type' => 'success', 'message' => 'تم إرسال رسالتك بنجاح.'];

} catch (PDOException $e) {
    error_log("Student contact error: " . $e->getMessage());
    $_SESSION['contact_msg'] = ['type' => 'error', 'message' => 'حدث خطأ أثناء إرسال الرسالة.'];
}

header("Location: index.php");
exit();
?>