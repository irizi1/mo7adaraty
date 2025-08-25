<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// الآن نحذف بناءً على group_subject_id
$group_subject_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$group_subject_id) {
    header("Location: index.php?error=معرف الربط غير صحيح");
    exit();
}

try {
    // حذف الربط فقط من جدول group_subjects
    // هذا لا يحذف المادة نفسها، فقط يزيلها من المقرر
    $stmt = $pdo->prepare("DELETE FROM group_subjects WHERE group_subject_id = ?");
    $stmt->execute([$group_subject_id]);

    header("Location: index.php?status=deleted");
    exit();

} catch (PDOException $e) {
    header("Location: index.php?error=حدث خطأ أثناء حذف الربط.");
    exit();
}
?>