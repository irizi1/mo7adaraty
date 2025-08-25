<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة والتأكد من أن المستخدم هو أدمن
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php"); 
    exit();
}

// التأكد من أن الطلب هو POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php?error=طلب غير صالح");
    exit();
}

// التحقق من وجود البيانات المطلوبة
if (!isset($_POST['group_id']) || !isset($_POST['division_id']) || !isset($_POST['track_id']) || 
    !isset($_POST['class_id']) || !isset($_POST['group_name'])) {
    header("Location: index.php?error=البيانات المطلوبة غير كاملة");
    exit();
}

// تنظيف البيانات
$group_id = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT);
$division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);
$track_id = filter_input(INPUT_POST, 'track_id', FILTER_VALIDATE_INT);
$class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
$group_name = filter_input(INPUT_POST, 'group_name', FILTER_SANITIZE_STRING);

if ($group_id === false || $group_id <= 0 || 
    $division_id === false || $division_id <= 0 || 
    $track_id === false || $track_id <= 0 || 
    $class_id === false || $class_id <= 0 || 
    empty($group_name)) {
    header("Location: edit.php?id=$group_id&error=بيانات غير صالحة");
    exit();
}

try {
    // التحقق من وجود الفوج
    $stmt = $pdo->prepare("SELECT group_id FROM groups WHERE group_id = ?");
    $stmt->execute([$group_id]);
    if (!$stmt->fetch()) {
        header("Location: index.php?error=الفوج غير موجود");
        exit();
    }

    // التحقق من أن المسار ينتمي إلى الشعبة
    $stmt = $pdo->prepare("SELECT track_id FROM tracks WHERE track_id = ? AND division_id = ?");
    $stmt->execute([$track_id, $division_id]);
    if (!$stmt->fetch()) {
        header("Location: edit.php?id=$group_id&error=المسار غير مرتبط بالشعبة المختارة");
        exit();
    }

    // التحقق من أن الفصل ينتمي إلى المسار
    $stmt = $pdo->prepare("SELECT class_id FROM classes WHERE class_id = ? AND track_id = ?");
    $stmt->execute([$class_id, $track_id]);
    if (!$stmt->fetch()) {
        header("Location: edit.php?id=$group_id&error=الفصل غير مرتبط بالمسار المختار");
        exit();
    }

    // تحديث بيانات الفوج
    $stmt = $pdo->prepare("UPDATE groups SET group_name = ?, class_id = ? WHERE group_id = ?");
    $stmt->execute([$group_name, $class_id, $group_id]);

    header("Location: index.php?status=updated");
    exit();

} catch (PDOException $e) {
    error_log("خطأ في تحديث الفوج: " . $e->getMessage());
    header("Location: edit.php?id=$group_id&error=خطأ في تحديث البيانات");
    exit();
}
?>