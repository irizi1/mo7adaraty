<?php
session_start();
require_once '../../config/db_connexion.php';

// ==========================================================
// 1. حماية الصفحة والتحقق من الصلاحيات
// ==========================================================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// ==========================================================
// 2. التحقق من معرّف المستخدم المطلوب حذفه
// ==========================================================
$user_id_to_delete = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$user_id_to_delete) {
    header("Location: index.php?error=معرف غير صحيح");
    exit();
}

// ==========================================================
// 3. منع الأدمن من حذف حسابه الخاص
// ==========================================================
if ($user_id_to_delete == $_SESSION['user_id']) {
    header("Location: index.php?error=لا يمكنك حذف حسابك الخاص.");
    exit();
}

// ==========================================================
// 4. حذف المستخدم وجميع بياناته المرتبطة (باستخدام Transaction)
// ==========================================================
try {
    // بدء عملية آمنة تضمن تنفيذ جميع الخطوات أو لا شيء
    $pdo->beginTransaction();
    
    // أ. حذف المحتوى الذي قام المستخدم برفعه (المحاضرات والامتحانات)
    $pdo->prepare("DELETE FROM lectures WHERE uploader_user_id = ?")->execute([$user_id_to_delete]);
    $pdo->prepare("DELETE FROM exams WHERE uploader_user_id = ?")->execute([$user_id_to_delete]);
    
    // ب. حذف تفاعلات المستخدم (التعليقات، الإعجابات)
    $pdo->prepare("DELETE FROM comments WHERE user_id = ?")->execute([$user_id_to_delete]);
    $pdo->prepare("DELETE FROM content_reactions WHERE user_id = ?")->execute([$user_id_to_delete]);
    
    // ج. حذف بياناته الأخرى (تسجيلاته، رسائل الدردشة، البلاغات، الإشعارات)
    $pdo->prepare("DELETE FROM student_enrollments WHERE user_id = ?")->execute([$user_id_to_delete]);
    $pdo->prepare("DELETE FROM chat_messages WHERE user_id = ?")->execute([$user_id_to_delete]);
    $pdo->prepare("DELETE FROM reports WHERE reporter_user_id = ?")->execute([$user_id_to_delete]);
    $pdo->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$user_id_to_delete]);

    // د. أخيراً، حذف المستخدم نفسه من جدول 'users'
    $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$user_id_to_delete]);

    // تأكيد جميع عمليات الحذف
    $pdo->commit();

    header("Location: index.php?status=deleted_successfully");
    exit();
    
} catch (PDOException $e) {
    // في حال حدوث أي خطأ، يتم التراجع عن جميع عمليات الحذف
    $pdo->rollBack();
    header("Location: index.php?error=حدث خطأ أثناء حذف المستخدم.");
    // للتصحيح: error_log("User delete failed: " . $e->getMessage());
    exit();
}
?>