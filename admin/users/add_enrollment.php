<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$offering_id = filter_input(INPUT_POST, 'offering_id', FILTER_VALIDATE_INT);

if (!$user_id || !$offering_id) {
    $_SESSION['user_update_msg'] = "خطأ: البيانات غير كاملة.";
    header("Location: edit.php?id=" . $user_id);
    exit();
}

try {
    // التحقق من أن الطالب غير مسجل بالفعل في هذا المقرر
    $stmt_check = $pdo->prepare("SELECT enrollment_id FROM student_enrollments WHERE user_id = ? AND offering_id = ?");
    $stmt_check->execute([$user_id, $offering_id]);

    if ($stmt_check->fetch()) {
        $_SESSION['user_update_msg'] = "هذا الطالب مسجل بالفعل في المقرر المحدد.";
    } else {
        // إضافة التسجيل الجديد
        $stmt_insert = $pdo->prepare("INSERT INTO student_enrollments (user_id, offering_id) VALUES (?, ?)");
        $stmt_insert->execute([$user_id, $offering_id]);
        $_SESSION['user_update_msg'] = "تمت إضافة التسجيل بنجاح.";
    }

} catch (PDOException $e) {
    $_SESSION['user_update_msg'] = "حدث خطأ في قاعدة البيانات.";
}

header("Location: edit.php?id=" . $user_id);
exit();
?>