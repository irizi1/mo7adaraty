<?php
session_start();
require_once '../../config/db_connexion.php';

// ==========================================================
// 1. حماية الصفحة والتحقق من الصلاحيات
// ==========================================================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php?error=وصول غير مصرح به");
    exit();
}

// ==========================================================
// 2. استقبال معرّف المحاضرة والتحقق منه
// ==========================================================
$lecture_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$lecture_id) {
    header("Location: index.php?error=معرف غير صحيح");
    exit();
}

// ==========================================================
// 3. حذف البيانات من قاعدة البيانات والملف من الخادم
// ==========================================================
try {
    $pdo->beginTransaction();

    // أ. جلب مسار الملف أولاً لحذفه من الخادم لاحقاً
    $stmt_path = $pdo->prepare("SELECT file_path FROM lectures WHERE lecture_id = ?");
    $stmt_path->execute([$lecture_id]);
    $filepath_to_delete = $stmt_path->fetchColumn();

    // ب. حذف السجلات المرتبطة بالمحاضرة (مثل التعليقات والبلاغات إذا وجدت مستقبلاً)
    // $pdo->prepare("DELETE FROM comments WHERE content_type = 'lecture' AND content_id = ?")->execute([$lecture_id]);
    // $pdo->prepare("DELETE FROM reports WHERE content_type = 'lecture' AND content_id = ?")->execute([$lecture_id]);
    
    // ج. حذف سجل المحاضرة نفسه من قاعدة البيانات
    $stmt_delete = $pdo->prepare("DELETE FROM lectures WHERE lecture_id = ?");
    $stmt_delete->execute([$lecture_id]);

    // د. حذف الملف الفعلي من الخادم إذا كان موجوداً
    if ($filepath_to_delete && file_exists('../../' . $filepath_to_delete)) {
        unlink('../../' . $filepath_to_delete);
    }
    
    $pdo->commit();
    header("Location: index.php?status=deleted");
    exit();

} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: index.php?error=" . urlencode("حدث خطأ أثناء حذف المحاضرة."));
    // للتصحيح: error_log($e->getMessage());
    exit();
}
?>