<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// [تم التحديث هنا] استقبال معرف ربط المادة بالمقرر
$offering_subject_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$offering_subject_id) {
    header("Location: index.php?error=معرف غير صحيح");
    exit();
}

try {
    // إلغاء تعيين الأستاذ بجعل professor_id = NULL
    // هذا لا يحذف الأستاذ من النظام، بل يحرر المادة فقط
    $stmt = $pdo->prepare("UPDATE offering_subjects SET professor_id = NULL WHERE offering_subject_id = ?");
    $stmt->execute([$offering_subject_id]);

    header("Location: index.php?status=deleted");
    exit();

} catch (PDOException $e) {
    header("Location: index.php?error=حدث خطأ أثناء إلغاء التعيين.");
    exit();
}
?>