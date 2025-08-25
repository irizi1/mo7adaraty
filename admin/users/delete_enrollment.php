<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة والتأكد من أن المستخدم هو أدمن
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

// استقبال البيانات والتحقق منها
$enrollment_id = filter_input(INPUT_POST, 'enrollment_id', FILTER_VALIDATE_INT);
$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT); // للعودة إلى نفس الصفحة

if (!$enrollment_id || !$user_id) {
    $_SESSION['user_update_msg'] = "خطأ: البيانات غير كاملة.";
    header("Location: index.php"); // العودة للقائمة الرئيسية إذا كان هناك خطأ كبير
    exit();
}

try {
    // حذف التسجيل
    $stmt = $pdo->prepare("DELETE FROM student_enrollments WHERE enrollment_id = ?");
    $stmt->execute([$enrollment_id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['user_update_msg'] = "تم حذف التسجيل بنجاح.";
    } else {
        $_SESSION['user_update_msg'] = "لم يتم العثور على التسجيل أو تم حذفه بالفعل.";
    }

} catch (PDOException $e) {
    error_log("Admin delete enrollment error: " . $e->getMessage());
    $_SESSION['user_update_msg'] = "حدث خطأ في قاعدة البيانات.";
}

// إعادة التوجيه إلى صفحة التعديل
header("Location: edit.php?id=" . $user_id);
exit();
?>