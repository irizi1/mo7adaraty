<?php
session_start();
require_once '../../config/db_connexion.php';

// ==========================================================
// 1. حماية الصفحة والتحقق من الصلاحيات
// ==========================================================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php?error=وصول غير مصرح به");
    exit();
}

// ==========================================================
// 2. استقبال البيانات من نموذج التعديل والتحقق منها
// ==========================================================
$subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
$subject_name = trim($_POST['subject_name']);

// التحقق من أن البيانات الضرورية موجودة
if (empty($subject_name) || !$subject_id) {
    header("Location: index.php?error=البيانات غير كاملة.");
    exit();
}

// ==========================================================
// 3. تحديث البيانات في قاعدة البيانات
// ==========================================================
try {
    // إعداد استعلام التحديث
    $stmt = $pdo->prepare("UPDATE subjects SET subject_name = ? WHERE subject_id = ?");
    
    // تنفيذ الاستعلام
    $stmt->execute([$subject_name, $subject_id]);

    // إعادة التوجيه إلى صفحة إدارة المواد مع رسالة نجاح
    header("Location: index.php?status=updated");
    exit();

} catch (PDOException $e) {
    // في حال حدوث خطأ في قاعدة البيانات
    header("Location: index.php?error=حدث خطأ أثناء تحديث المادة.");
    // للتصحيح: error_log($e->getMessage());
    exit();
}
?>