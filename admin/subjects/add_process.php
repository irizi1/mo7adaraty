<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

$offering_id = filter_input(INPUT_POST, 'offering_id', FILTER_VALIDATE_INT);
$subject_name = trim(filter_input(INPUT_POST, 'subject_name', FILTER_SANITIZE_STRING));

if (!$offering_id || empty($subject_name)) {
    header("Location: index.php?error=البيانات غير كاملة.");
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. التحقق من وجود المادة، وإن لم تكن موجودة يتم إنشاؤها
    $stmt_find = $pdo->prepare("SELECT subject_id FROM subjects WHERE subject_name = ?");
    $stmt_find->execute([$subject_name]);
    $subject = $stmt_find->fetch();

    if ($subject) {
        $subject_id = $subject['subject_id'];
    } else {
        $stmt_insert = $pdo->prepare("INSERT INTO subjects (subject_name) VALUES (?)");
        $stmt_insert->execute([$subject_name]);
        $subject_id = $pdo->lastInsertId();
    }

    // 2. التحقق من أن هذا الربط (بين المقرر والمادة) غير موجود بالفعل
    $stmt_check_link = $pdo->prepare("SELECT offering_subject_id FROM offering_subjects WHERE offering_id = ? AND subject_id = ?");
    $stmt_check_link->execute([$offering_id, $subject_id]);
    if ($stmt_check_link->fetch()) {
        throw new Exception("هذه المادة مرتبطة بالفعل بهذا المقرر.");
    }

    // 3. ربط المادة بالمقرر
    $stmt_link = $pdo->prepare("INSERT INTO offering_subjects (offering_id, subject_id) VALUES (?, ?)");
    $stmt_link->execute([$offering_id, $subject_id]);

    $pdo->commit();
    header("Location: index.php?status=added");
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: index.php?error=" . urlencode($e->getMessage()));
    exit();
}
?>