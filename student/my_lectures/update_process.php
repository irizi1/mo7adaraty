<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id'])) {
        header("Location: ../../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// استقبال البيانات والتحقق منها
$lecture_id = filter_input(INPUT_POST, 'lecture_id', FILTER_VALIDATE_INT);
$title = trim($_POST['title']);
$description = trim($_POST['description']);

if (!$lecture_id || empty($title)) {
    $_SESSION['lecture_action_status'] = ['type' => 'error', 'message' => 'البيانات غير كاملة.'];
    header("Location: index.php");
    exit();
}

try {
    // جلب بيانات المحاضرة للتأكد من ملكية الطالب
    $stmt_check = $pdo->prepare("SELECT * FROM lectures WHERE lecture_id = ? AND uploader_user_id = ? AND status = 'pending'");
    $stmt_check->execute([$lecture_id, $user_id]);
    $lecture = $stmt_check->fetch();

    if (!$lecture) {
        throw new Exception("محاولة تعديل محاضرة غير مسموح بها.");
    }

    $pdo->beginTransaction();

    // التحقق إذا تم رفع ملف جديد
    if (isset($_FILES['lecture_file']) && $_FILES['lecture_file']['error'] === UPLOAD_ERR_OK) {
        // حذف الملف القديم
        if (file_exists('../../' . $lecture['file_path'])) {
            unlink('../../' . $lecture['file_path']);
        }
        
        // رفع الملف الجديد
        $upload_dir = '../../uploads/lectures/';
        $file_extension = strtolower(pathinfo($_FILES['lecture_file']['name'], PATHINFO_EXTENSION));
        $new_file_name = uniqid('lecture_', true) . '.' . $file_extension;
        $target_file = $upload_dir . $new_file_name;

        if (move_uploaded_file($_FILES['lecture_file']['tmp_name'], $target_file)) {
            // تحديث كل البيانات بما في ذلك مسار الملف الجديد
            $stmt_update = $pdo->prepare(
                "UPDATE lectures SET title = ?, description = ?, file_path = ?, file_type = ? WHERE lecture_id = ?"
            );
            $stmt_update->execute([$title, $description, 'uploads/lectures/' . $new_file_name, $file_extension, $lecture_id]);
        } else {
            throw new Exception("فشل رفع الملف الجديد.");
        }
    } else {
        // تحديث البيانات النصية فقط
        $stmt_update = $pdo->prepare("UPDATE lectures SET title = ?, description = ? WHERE lecture_id = ?");
        $stmt_update->execute([$title, $description, $lecture_id]);
    }

    $pdo->commit();
    $_SESSION['lecture_action_status'] = ['type' => 'success', 'message' => 'تم تحديث المحاضرة بنجاح.'];

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Lecture update failed: " . $e->getMessage());
    $_SESSION['lecture_action_status'] = ['type' => 'error', 'message' => 'حدث خطأ أثناء تحديث المحاضرة.'];
}

header("Location: index.php");
exit();
?>