<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$report_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$report_id) {
    header("Location: index.php?error=معرف غير صحيح");
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM reports WHERE report_id = ?");
    $stmt->execute([$report_id]);
    header("Location: index.php?status=deleted");
    exit();
} catch (PDOException $e) {
    header("Location: index.php?error=حدث خطأ أثناء حذف البلاغ.");
    exit();
}
?>