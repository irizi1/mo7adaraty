<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$offering_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$offering_id) {
    header("Location: index.php?error=معرف غير صحيح");
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM course_offerings WHERE offering_id = ?");
    $stmt->execute([$offering_id]);
    header("Location: index.php?status=deleted");
    exit();
} catch (PDOException $e) {
    header("Location: index.php?error=حدث خطأ أثناء الحذف.");
    exit();
}
?>