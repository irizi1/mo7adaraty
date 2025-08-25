<?php
session_start();
require_once '../../config/db_connexion.php';

// ==========================================================
// 1. التحقق من صلاحيات الأدمن
// ==========================================================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php?error=ليس لديك صلاحية الوصول لهذه الصفحة");
    exit();
}

// ==========================================================
// 2. التحقق من وجود معرف المجموعة
// ==========================================================
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=معرف المجموعة غير صالح");
    exit();
}

$group_id = (int)$_GET['id'];

// ==========================================================
// 3. حذف المجموعة من قاعدة البيانات
// ==========================================================
try {
    // التحقق من وجود المجموعة
    $stmt = $pdo->prepare("SELECT group_id FROM groups WHERE group_id = ?");
    $stmt->execute([$group_id]);
    if ($stmt->rowCount() === 0) {
        header("Location: index.php?error=المجموعة غير موجودة");
        exit();
    }

    // حذف المجموعة (الجداول المرتبطة ستُحذف تلقائيًا بسبب ON DELETE CASCADE)
    $stmt = $pdo->prepare("DELETE FROM groups WHERE group_id = ?");
    $stmt->execute([$group_id]);

    // إعادة التوجيه مع رسالة نجاح
    header("Location: index.php?success=تم حذف المجموعة بنجاح");
    exit();
} catch (PDOException $e) {
    // تسجيل الخطأ في ملف error_log
    error_log("خطأ في حذف المجموعة (group_id: $group_id): " . $e->getMessage());
    // إعادة التوجيه مع رسالة خطأ
    header("Location: index.php?error=فشل في حذف المجموعة، يرجى المحاولة لاحقًا");
    exit();
}
?>