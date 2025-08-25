<?php
session_start();
require_once '../../config/db_connexion.php';

// 1. حماية الصفحة والتحقق من الصلاحيات
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php?error=وصول غير مصرح به");
    exit();
}

// 2. استقبال معرّف الامتحان والتحقق منه
$exam_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$exam_id) {
    header("Location: index.php?error=معرف غير صحيح");
    exit();
}

// 3. حذف البيانات من قاعدة البيانات والملف من الخادم
try {
    $pdo->beginTransaction();

    // أ. جلب مسار الملف أولاً لحذفه من الخادم لاحقاً
    $stmt = $pdo->prepare("SELECT file_path FROM exams WHERE exam_id = ?");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch();
    $filepath_to_delete = $exam ? '../../' . $exam['file_path'] : null;

    // ب. حذف التعليقទيقات والإعجابات المرتبطة
    $pdo->prepare("DELETE FROM content_reactions WHERE content_type = 'exam' AND content_id = ?")->execute([$exam_id]);
    
    // ج. حذف سجل الامتحان
    $pdo->prepare("DELETE FROM exams WHERE exam_id = ?")->execute([$exam_id]);

    // د. حذف الملف الفعلي من الخادم إذا كان موجوداً
    if ($filepath_to_delete && file_exists($filepath_to_delete)) {
        unlink($filepath_to_delete);
    }
    
    $pdo->commit();
    header("Location: index.php?status=deleted");
    exit();

} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: index.php?error=" . urlencode("حدث خطأ أثناء حذف الامتحان."));
    exit();
}
?>