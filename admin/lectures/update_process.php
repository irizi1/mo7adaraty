<?php
session_start();
require_once '../../config/db_connexion.php';

// ==========================================================
// 1. حماية الصفحة والتحقق من الصلاحيات
// ==========================================================
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

// ===========================================
// 2. استقبال البيانات من نموذج التعديل
// ===========================================
$lecture_id = filter_input(INPUT_POST, 'lecture_id', FILTER_VALIDATE_INT);
$title = trim($_POST['title']);
$description = trim($_POST['description']);
$subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);

// التحقق من أن البيانات الأساسية موجودة
if (empty($title) || !$subject_id || !$lecture_id) {
    header("Location: edit.php?id=$lecture_id&error=البيانات غير كاملة.");
    exit();
}

try {
    // ===========================================
    // 3. معالجة الملف الجديد (إذا تم رفعه)
    // ===========================================
    $new_file_uploaded = isset($_FILES['lecture_file']) && $_FILES['lecture_file']['error'] == 0 && $_FILES['lecture_file']['size'] > 0;
    
    if ($new_file_uploaded) {
        // جلب مسار الملف القديم لحذفه لاحقاً
        $stmt_old_file = $pdo->prepare("SELECT file_path FROM lectures WHERE lecture_id = ?");
        $stmt_old_file->execute([$lecture_id]);
        $old_file_path = $stmt_old_file->fetchColumn();

        // معالجة الملف الجديد
        $upload_dir = '../../uploads/lectures/';
        $file_extension = strtolower(pathinfo($_FILES['lecture_file']['name'], PATHINFO_EXTENSION));
        $new_file_name = uniqid('lecture_', true) . '.' . $file_extension;
        $target_file = $upload_dir . $new_file_name;

        if (move_uploaded_file($_FILES['lecture_file']['tmp_name'], $target_file)) {
            // تم رفع الملف الجديد بنجاح، قم بحذف الملف القديم إذا كان موجوداً
            if ($old_file_path && file_exists('../../' . $old_file_path)) {
                unlink('../../' . $old_file_path);
            }

            // تحديث قاعدة البيانات بالمعلومات الجديدة + مسار الملف الجديد
            $stmt = $pdo->prepare("UPDATE lectures SET title = ?, description = ?, subject_id = ?, file_path = ?, file_type = ? WHERE lecture_id = ?");
            $stmt->execute([$title, $description, $subject_id, 'uploads/lectures/' . $new_file_name, $file_extension, $lecture_id]);

        } else {
            // فشل رفع الملف الجديد
            header("Location: edit.php?id=$lecture_id&error=حدث خطأ أثناء رفع الملف الجديد.");
            exit();
        }
    } else {
        // لم يتم رفع ملف جديد، قم بتحديث البيانات النصية فقط
        $stmt = $pdo->prepare("UPDATE lectures SET title = ?, description = ?, subject_id = ? WHERE lecture_id = ?");
        $stmt->execute([$title, $description, $subject_id, $lecture_id]);
    }

    // ===========================================
    // 4. إعادة التوجيه
    // ===========================================
    header("Location: index.php?status=updated");
    exit();

} catch (PDOException $e) {
    header("Location: edit.php?id=$lecture_id&error=" . urlencode("خطأ في قاعدة البيانات: " . $e->getMessage()));
    exit();
}
?>