<?php
session_start();
require_once '../../config/db_connexion.php';

// ==========================================================
// 1. حماية الصفحة والتحقق من الصلاحيات
// ==========================================================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php?error=وصول غير مصرح به");
    exit();
}

// ==========================================================
// 2. استقبال معرّف المقرر والتحقق منه
// ==========================================================
$group_subject_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$group_subject_id) {
    header("Location: index.php?error=معرف المقرر غير صحيح");
    exit();
}

// ==========================================================
// 3. حذف البيانات من قاعدة البيانات
// ==========================================================
try {
    // قبل الحذف، يجب التحقق إذا كان هذا المقرر مرتبطًا بأي محاضرات أو امتحانات
    // هذا يمنع حذف مقرر يحتوي على محتوى
    $stmt_check_lectures = $pdo->prepare("SELECT COUNT(*) FROM lectures WHERE group_subject_id = ?");
    $stmt_check_lectures->execute([$group_subject_id]);
    if ($stmt_check_lectures->fetchColumn() > 0) {
        header("Location: index.php?error=" . urlencode("لا يمكن حذف المقرر لارتباطه بمحاضرات."));
        exit();
    }

    $stmt_check_exams = $pdo->prepare("SELECT COUNT(*) FROM exams WHERE group_subject_id = ?");
    $stmt_check_exams->execute([$group_subject_id]);
    if ($stmt_check_exams->fetchColumn() > 0) {
        header("Location: index.php?error=" . urlencode("لا يمكن حذف المقرر لارتباطه بامتحانات."));
        exit();
    }

    // إذا لم يكن مرتبطًا بأي محتوى، قم بالحذف
    $stmt = $pdo->prepare("DELETE FROM group_subjects WHERE group_subject_id = ?");
    $stmt->execute([$group_subject_id]);

    header("Location: index.php?status=deleted");
    exit();

} catch (PDOException $e) {
    header("Location: index.php?error=" . urlencode("حدث خطأ أثناء حذف المقرر."));
    // للتصحيح: error_log($e->getMessage());
    exit();
}
?>