<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

$subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
$subject_name = trim($_POST['subject_name']);

if (empty($subject_name) || !$subject_id) {
    header("Location: edit.php?id=$subject_id&error=البيانات غير كاملة.");
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE subjects SET subject_name = ? WHERE subject_id = ?");
    $stmt->execute([$subject_name, $subject_id]);
    header("Location: index.php?status=updated");
    exit();

} catch (PDOException $e) {
    header("Location: edit.php?id=$subject_id&error=حدث خطأ أثناء تحديث المادة.");
    exit();
}
?>