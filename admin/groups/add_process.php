<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

define('ADMIN_ADVANCED_CLASSES_IDS', [5, 6]);

$group_name = trim($_POST['group_name']);
$class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
$track_id = filter_input(INPUT_POST, 'track_id', FILTER_VALIDATE_INT);
$division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT); // مهم لربط الفصول العامة

if (empty($group_name) || !$class_id || !$division_id) {
    header("Location: index.php?error=البيانات غير كاملة، يرجى ملء جميع الحقول.");
    exit();
}

$final_track_id = $track_id;

// تحديد المسار الذي سيتم ربط الفوج به
if (!in_array($class_id, ADMIN_ADVANCED_CLASSES_IDS)) {
    // إذا كان الفصل عامًا (1-4)، ابحث عن مسار افتراضي داخل الشعبة المختارة
    $stmt_track = $pdo->prepare("SELECT track_id FROM tracks WHERE division_id = ? LIMIT 1");
    $stmt_track->execute([$division_id]);
    $default_track_id = $stmt_track->fetchColumn();
    
    if (!$default_track_id) {
        header("Location: index.php?error=لا توجد مسارات في هذه الشعبة لإسناد الفوج إليها.");
        exit();
    }
    $final_track_id = $default_track_id;
} elseif (!$track_id) {
    // إذا كان الفصل متقدمًا، فالمسار إجباري
    header("Location: index.php?error=الرجاء اختيار المسار للفصول المتقدمة.");
    exit();
}

try {
    // التحقق من عدم وجود فوج بنفس الاسم في نفس السياق
    $stmt_check = $pdo->prepare("SELECT group_id FROM `groups` WHERE group_name = ? AND class_id = ? AND track_id = ?");
    $stmt_check->execute([$group_name, $class_id, $final_track_id]);
    if ($stmt_check->fetch()) {
        header("Location: index.php?error=يوجد فوج بنفس الاسم في هذا السياق المحدد.");
        exit();
    }

    // إضافة الفوج الجديد مع ربطه بالمسار الصحيح
    $stmt = $pdo->prepare("INSERT INTO `groups` (group_name, class_id, track_id) VALUES (?, ?, ?)");
    $stmt->execute([$group_name, $class_id, $final_track_id]);

    header("Location: index.php?status=added");
    exit();
} catch (PDOException $e) {
    header("Location: index.php?error=حدث خطأ أثناء إضافة الفوج.");
    exit();
}
?>