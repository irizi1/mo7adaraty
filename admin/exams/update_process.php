<?php
session_start();
require_once '../../config/db_connexion.php';

// ==========================================================
// 1. حماية الصفحة والتحقق من الصلاحيات
// ==========================================================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php?error=وصول غير مصرح به");
    exit();
}

// ===========================================
// 2. استقبال البيانات من نموذج التعديل
// ===========================================
$exam_id = filter_input(INPUT_POST, 'exam_id', FILTER_VALIDATE_INT);
$title = trim($_POST['title']);
$description = trim($_POST['description']);
$group_subject_id = filter_input(INPUT_POST, 'group_subject_id', FILTER_VALIDATE_INT);

// التحقق من أن البيانات الأساسية موجودة
if (empty($title) || !$group_subject_id || !$exam_id) {
    header("Location: edit.php?id=$exam_id&error=البيانات غير كاملة.");
    exit();
}

try {
    // ===========================================
    // 3. معالجة الملف الجديد (إذا تم رفعه)
    // ===========================================
    $new_file_uploaded = isset($_FILES['exam_file']) && $_FILES['exam_file']['error'] == 0 && $_FILES['exam_file']['size'] > 0;
    
    if ($new_file_uploaded) {
        // جلب مسار الملف القديم لحذفه لاحقاً
        $stmt_old_file = $pdo->prepare("SELECT file_path FROM exams WHERE exam_id = ?");
        $stmt_old_file->execute([$exam_id]);
        $old_file_path = $stmt_old_file->fetchColumn();

        // معالجة الملف الجديد
        $upload_dir = '../../uploads/exams/';
        $file_extension = strtolower(pathinfo($_FILES['exam_file']['name'], PATHINFO_EXTENSION));
        $new_file_name = uniqid('exam_', true) . '.' . $file_extension;
        $target_file = $upload_dir . $new_file_name;

        if (move_uploaded_file($_FILES['exam_file']['tmp_name'], $target_file)) {
            // تم رفع الملف الجديد بنجاح، قم بحذف الملف القديم إذا كان موجوداً
            if ($old_file_path && file_exists('../../' . $old_file_path)) {
                unlink('../../' . $old_file_path);
            }

            // تحديث قاعدة البيانات بالمعلومات الجديدة + مسار الملف الجديد
            $stmt = $pdo->prepare("UPDATE exams SET title = ?, description = ?, group_subject_id = ?, file_path = ?, file_type = ? WHERE exam_id = ?");
            $stmt->execute([$title, $description, $group_subject_id, 'uploads/exams/' . $new_file_name, $file_extension, $exam_id]);

        } else {
            // فشل رفع الملف الجديد
            header("Location: edit.php?id=$exam_id&error=حدث خطأ أثناء رفع الملف الجديد.");
            exit();
        }
    } else {
        // لم يتم رفع ملف جديد، قم بتحديث البيانات النصية فقط
        $stmt = $pdo->prepare("UPDATE exams SET title = ?, description = ?, group_subject_id = ? WHERE exam_id = ?");
        $stmt->execute([$title, $description, $group_subject_id, $exam_id]);
    }

    // ===========================================
    // 4. إعادة التوجيه
    // ===========================================
    header("Location: index.php?status=updated");
    exit();

} catch (PDOException $e) {
    header("Location: edit.php?id=$exam_id&error=" . urlencode("خطأ في قاعدة البيانات: " . $e->getMessage()));
    exit();
}
?>