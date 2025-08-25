<?php
session_start();
require_once 'config/db_connexion.php';

// ==========================================================
// 1. حماية الملف والتحقق من الصلاحيات
// ==========================================================

// التأكد من أن المستخدم طالب مسجل دخوله وأنه أرسل البيانات عبر POST
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);

if (!$class_id) {
    header("Location: explore_classes.php?error=طلب غير صحيح");
    exit();
}

// ==========================================================
// 2. معالجة عملية التسجيل
// ==========================================================

try {
    // أ. التحقق من أن الطالب ليس مسجلاً بالفعل في هذا الفصل لتجنب التكرار
    $stmt_check = $pdo->prepare("
        SELECT COUNT(*) 
        FROM student_enrollments se
        JOIN `groups` g ON se.group_id = g.group_id
        WHERE se.user_id = ? AND g.class_id = ?
    ");
    $stmt_check->execute([$user_id, $class_id]);
    if ($stmt_check->fetchColumn() > 0) {
        header("Location: explore_classes.php?error=أنت مسجل بالفعل في هذا الفصل.");
        exit();
    }

    // ب. إيجاد أول فوج متاح في هذا الفصل لتسجيل الطالب فيه
    $stmt_group = $pdo->prepare("SELECT group_id FROM `groups` WHERE class_id = ? LIMIT 1");
    $stmt_group->execute([$class_id]);
    $group = $stmt_group->fetch();

    if (!$group) {
        header("Location: explore_classes.php?error=لا توجد أفواج متاحة في هذا الفصل حالياً.");
        exit();
    }
    $group_id_to_enroll = $group['group_id'];

    // ج. تسجيل الطالب في الفوج
    $stmt_enroll = $pdo->prepare("INSERT INTO student_enrollments (user_id, group_id) VALUES (?, ?)");
    $stmt_enroll->execute([$user_id, $group_id_to_enroll]);

    // د. إعادة التوجيه مع رسالة نجاح
    header("Location: student/profil.php?status=enrolled_successfully");
    exit();

} catch (PDOException $e) {
    // في حال حدوث خطأ
    header("Location: explore_classes.php?error=حدث خطأ أثناء عملية التسجيل.");
    exit();
}
?>