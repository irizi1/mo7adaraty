<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

define('ADMIN_ADVANCED_CLASSES_IDS', [5, 6]);

$division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);
$class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
$group_id = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT);
$track_id = filter_input(INPUT_POST, 'track_id', FILTER_VALIDATE_INT);

if (!$division_id || !$class_id || !$group_id) {
    header("Location: index.php?error=البيانات الأساسية غير كاملة.");
    exit();
}

// إذا كان الفصل متقدماً، يجب اختيار مسار
if (in_array($class_id, ADMIN_ADVANCED_CLASSES_IDS) && !$track_id) {
    header("Location: index.php?error=الرجاء اختيار المسار للفصول المتقدمة.");
    exit();
}
// إذا لم يكن الفصل متقدماً, يجب أن يكون المسار فارغاً
if (!in_array($class_id, ADMIN_ADVANCED_CLASSES_IDS)) {
    $track_id = null;
}


try {
    // التحقق من عدم وجود نفس المقرر بالفعل
    $sql_check = "SELECT offering_id FROM course_offerings WHERE division_id = ? AND class_id = ? AND group_id = ? AND (track_id = ? OR (track_id IS NULL AND ? IS NULL))";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$division_id, $class_id, $group_id, $track_id, $track_id]);

    if ($stmt_check->fetch()) {
        header("Location: index.php?error=هذا المقرر تم تكوينه بالفعل.");
        exit();
    }

    // إضافة المقرر الجديد
    $stmt = $pdo->prepare("INSERT INTO course_offerings (division_id, class_id, group_id, track_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$division_id, $class_id, $group_id, $track_id]);

    header("Location: index.php?status=added");
    exit();
} catch (PDOException $e) {
    header("Location: index.php?error=حدث خطأ في قاعدة البيانات: " . $e->getMessage());
    exit();
}
?>