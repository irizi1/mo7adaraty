<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

$class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
$class_name = trim($_POST['class_name']);
$track_id = filter_input(INPUT_POST, 'track_id', FILTER_VALIDATE_INT);

// التحقق من أن البيانات الضرورية موجودة
if (empty($class_name) || !$track_id || !$class_id) {
    header("Location: edit.php?id=$class_id&error=البيانات غير كاملة.");
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE classes SET class_name = ?, track_id = ? WHERE class_id = ?");
    $stmt->execute([$class_name, $track_id, $class_id]);

    header("Location: index.php?status=updated");
    exit();

} catch (PDOException $e) {
    header("Location: edit.php?id=$class_id&error=حدث خطأ أثناء تحديث الفصل.");
    exit();
}
?>