<?php
session_start();
require_once 'config/db_connexion.php';

// ==========================================================
// 1. حماية الملف والتحقق من الصلاحيات
// ==========================================================

// أ. التحقق من أن المستخدم مسجل دخوله
if (!isset($_SESSION['user_id'])) {
    die("خطأ: يجب تسجيل الدخول أولاً.");
}
$user_id = $_SESSION['user_id'];

// ب. التحقق من وجود المعرّفات المطلوبة في الرابط
$content_type = $_GET['type'] ?? '';
$content_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$content_id || !in_array($content_type, ['lecture', 'exam'])) {
    die("خطأ: طلب غير صالح.");
}

// ==========================================================
// 2. التحقق من صلاحية وصول الطالب للملف (منطق مُحدَّث)
// ==========================================================
try {
    $has_access = false;

    if ($content_type == 'lecture') {
        // التحقق من صلاحية تحميل المحاضرة: هل الطالب مسجل في مقررها؟
        $stmt_check = $pdo->prepare("
            SELECT COUNT(*) 
            FROM lectures l
            JOIN student_enrollments se ON l.offering_id = se.offering_id
            WHERE l.lecture_id = ? AND se.user_id = ? AND l.status = 'approved'
        ");
        $stmt_check->execute([$content_id, $user_id]);
        if ($stmt_check->fetchColumn() > 0) {
            $has_access = true;
        }
    } elseif ($content_type == 'exam') {
        // التحقق من صلاحية تحميل الامتحان: هل الطالب مسجل في فصله الدراسي؟
        $stmt_check = $pdo->prepare("
            SELECT COUNT(*)
            FROM exams e
            JOIN course_offerings co ON e.class_id = co.class_id
            JOIN student_enrollments se ON co.offering_id = se.offering_id
            WHERE e.exam_id = ? AND se.user_id = ?
        ");
        $stmt_check->execute([$content_id, $user_id]);
        if ($stmt_check->fetchColumn() > 0) {
            $has_access = true;
        }
    }

    // السماح للمسؤول بالتحميل دائماً
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $has_access = true;
    }

    if (!$has_access) {
        die("ليس لديك صلاحية تحميل هذا الملف.");
    }

    // ==========================================================
    // 3. جلب مسار الملف وبدء التحميل
    // ==========================================================
    $table_name = ($content_type == 'lecture') ? 'lectures' : 'exams';
    $id_column = ($content_type == 'lecture') ? 'lecture_id' : 'exam_id';

    $stmt_file = $pdo->prepare("SELECT file_path FROM $table_name WHERE $id_column = ?");
    $stmt_file->execute([$content_id]);
    $file_path_from_db = $stmt_file->fetchColumn();
    
    // بناء المسار الفعلي على الخادم
    $absolute_file_path = __DIR__ . '/' . $file_path_from_db;


    if (!$file_path_from_db || !file_exists($absolute_file_path)) {
        die("خطأ: الملف غير موجود على الخادم.");
    }

    // ==========================================================
    // 4. إرسال الملف للمتصفح (Headers مُحسَّنة)
    // ==========================================================
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($absolute_file_path) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($absolute_file_path));
    
    // مسح أي مخرجات سابقة قبل إرسال الملف
    ob_clean();
    flush();
    
    readfile($absolute_file_path);
    exit;

} catch (PDOException $e) {
    die("خطأ في قاعدة البيانات: " . $e->getMessage());
}
?>