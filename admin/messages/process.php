<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة والتأكد من أن المستخدم هو أدمن
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

// استقبال البيانات من نموذج الرد
$message_id = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);
$student_user_id = filter_input(INPUT_POST, 'student_user_id', FILTER_VALIDATE_INT);
$admin_reply = trim($_POST['admin_reply']);

// التحقق من صحة البيانات
if (!$message_id || !$student_user_id || empty($admin_reply)) {
    $_SESSION['reply_status'] = "خطأ: البيانات غير كاملة.";
    header("Location: index.php");
    exit();
}

try {
    // استخدام معاملة آمنة (Transaction) لضمان تنفيذ العمليتين معاً
    $pdo->beginTransaction();

    // 1. تحديث الرسالة الأصلية بالرد وتغيير حالتها
    $stmt_update = $pdo->prepare(
        "UPDATE admin_messages 
         SET admin_reply = ?, status = 'replied', replied_at = CURRENT_TIMESTAMP 
         WHERE message_id = ? AND status = 'pending_reply'"
    );
    $stmt_update->execute([$admin_reply, $message_id]);

    // 2. إرسال إشعار جديد للطالب بالرد
    $notification_message = "لقد تلقيت رداً من الإدارة على رسالتك.";
    $stmt_notify = $pdo->prepare(
        "INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)"
    );
    // الرابط يوجه الطالب إلى صفحة التواصل ليرى الرد
    $stmt_notify->execute([$student_user_id, $notification_message, 'student/contact/index.php']);

    // تأكيد العمليات
    $pdo->commit();

    $_SESSION['reply_status'] = "تم إرسال الرد بنجاح.";

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Admin reply error: " . $e->getMessage());
    $_SESSION['reply_status'] = "حدث خطأ أثناء إرسال الرد.";
}

header("Location: index.php");
exit();
?>