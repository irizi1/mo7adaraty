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
// 2. استقبال البيانات من النموذج والتحقق منها
// ==========================================================
$group_id = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT);
$subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
$professor_id = filter_input(INPUT_POST, 'professor_id', FILTER_VALIDATE_INT);

// التحقق من أن جميع الحقول المطلوبة تم ملؤها بشكل صحيح
if (!$group_id || !$subject_id || !$professor_id) {
    header("Location: index.php?error=البيانات غير كاملة، المرجو اختيار المادة والأستاذ.");
    exit();
}

// ==========================================================
// 3. إضافة البيانات إلى قاعدة البيانات
// ==========================================================
try {
    // إعداد استعلام الإضافة للجدول الوسيط
    $stmt = $pdo->prepare("INSERT INTO group_subjects (group_id, subject_id, professor_id) VALUES (?, ?, ?)");
    
    // تنفيذ الاستعلام
    $stmt->execute([$group_id, $subject_id, $professor_id]);

    // إعادة التوجيه مع رسالة نجاح
    header("Location: index.php?status=added");
    exit();

} catch (PDOException $e) {
    // في حال حدوث خطأ (مثل محاولة إضافة نفس المادة لنفس الفوج مرتين)
    if ($e->errorInfo[1] == 1062) { // 1062 هو رمز خطأ "Duplicate entry"
        header("Location: index.php?error=" . urlencode("هذه المادة مسندة بالفعل لهذا الفوج."));
    } else {
        header("Location: index.php?error=" . urlencode("حدث خطأ أثناء إضافة المقرر."));
    }
    exit();
}
?>