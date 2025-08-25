<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php"); exit();
}

$division_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$division_id) { header("Location: index.php"); exit(); }

try {
    // تحقق أولاً إذا كانت الشعبة مرتبطة بمسارات
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM tracks WHERE division_id = ?");
    $stmt_check->execute([$division_id]);
    if ($stmt_check->fetchColumn() > 0) {
        header("Location: index.php?error=لا يمكن حذف الشعبة لارتباطها بمسارات.");
        exit();
    }

    // إذا لم تكن مرتبطة، قم بالحذف
    $stmt = $pdo->prepare("DELETE FROM divisions WHERE division_id = ?");
    $stmt->execute([$division_id]);
    header("Location: index.php?status=deleted");
    exit();
} catch (PDOException $e) {
    header("Location: index.php?error=حدث خطأ ما.");
    exit();
}
?>