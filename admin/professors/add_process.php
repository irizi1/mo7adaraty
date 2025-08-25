<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

// [تم التحديث هنا] استقبال البيانات بناءً على الهيكل الجديد
$offering_subject_id = filter_input(INPUT_POST, 'offering_subject_id', FILTER_VALIDATE_INT);
$professor_name = trim(filter_input(INPUT_POST, 'professor_name', FILTER_SANITIZE_STRING));

if (!$offering_subject_id || empty($professor_name)) {
    header("Location: index.php?error=البيانات غير كاملة.");
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. التحقق من وجود الأستاذ، وإن لم يكن موجوداً يتم إضافته
    $stmt_find_prof = $pdo->prepare("SELECT professor_id FROM professors WHERE professor_name = ?");
    $stmt_find_prof->execute([$professor_name]);
    $prof = $stmt_find_prof->fetch();

    if ($prof) {
        $professor_id = $prof['professor_id'];
    } else {
        $stmt_insert_prof = $pdo->prepare("INSERT INTO professors (professor_name) VALUES (?)");
        $stmt_insert_prof->execute([$professor_name]);
        $professor_id = $pdo->lastInsertId();
    }

    // 2. تحديث جدول `offering_subjects` لتعيين الأستاذ للمادة في المقرر المحدد
    $stmt_update = $pdo->prepare("UPDATE offering_subjects SET professor_id = ? WHERE offering_subject_id = ?");
    $stmt_update->execute([$professor_id, $offering_subject_id]);

    $pdo->commit();

    header("Location: index.php?status=added");
    exit();

} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: index.php?error=حدث خطأ أثناء تعيين الأستاذ.");
    exit();
}
?>