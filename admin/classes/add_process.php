<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

$class_name = trim($_POST['class_name']);
$track_id = filter_input(INPUT_POST, 'track_id', FILTER_VALIDATE_INT);

// التحقق من أن البيانات الضرورية موجودة
if (empty($class_name) || !$track_id) {
    header("Location: index.php?error=البيانات غير كاملة. يرجى اختيار الشعبة والمسار.");
    exit();
}
    
try {
    $pdo->beginTransaction();
    
    // إضافة الفصل الجديد
    $stmt = $pdo->prepare("INSERT INTO classes (class_name, track_id) VALUES (?, ?)");
    $stmt->execute([$class_name, $track_id]);
    $new_class_id = $pdo->lastInsertId();
    
    // إنشاء مجموعة دردشة تلقائياً لهذا الفصل
    $chat_group_name = "مجموعة دردشة لـ " . $class_name;
    $stmt_chat = $pdo->prepare("INSERT INTO chat_groups (class_id, group_name) VALUES (?, ?)");
    $stmt_chat->execute([$new_class_id, $chat_group_name]);
    
    $pdo->commit();
    header("Location: index.php?status=added");
    exit();

} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: index.php?error=db_error");
    exit();
}
?>