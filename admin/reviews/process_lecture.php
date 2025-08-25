<?php
session_start();
require_once '../../config/db_connexion.php';

// 1. حماية الصفحة والتحقق من الصلاحيات
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

// 2. استقبال البيانات من النموذج
$lecture_id = filter_input(INPUT_POST, 'lecture_id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';
$rejection_reason = trim($_POST['rejection_reason'] ?? '');

if (!$lecture_id || !in_array($action, ['approve', 'reject'])) {
    $_SESSION['review_status'] = ['type' => 'error', 'message' => 'طلب غير صالح.'];
    header("Location: index.php");
    exit();
}

// 3. معالجة الإجراء
try {
    // [تم التحديث] جلب معلومات المحاضرة الضرورية بناءً على الهيكل الجديد
    $stmt_info = $pdo->prepare("
        SELECT 
            l.title, l.uploader_user_id, l.file_path, l.offering_id
        FROM lectures l
        WHERE l.lecture_id = ?
    ");
    $stmt_info->execute([$lecture_id]);
    $lecture_info = $stmt_info->fetch(PDO::FETCH_ASSOC);

    if (!$lecture_info) {
        throw new Exception("لم يتم العثور على المحاضرة.");
    }

    $pdo->beginTransaction();

    if ($action === 'approve') {
        // --- منطق القبول ---
        
        // 1. تحديث حالة المحاضرة إلى 'approved'
        $stmt_approve = $pdo->prepare("UPDATE lectures SET status = 'approved' WHERE lecture_id = ?");
        $stmt_approve->execute([$lecture_id]);

        // 2. إرسال إشعار للطالب الناشر
        $notification_msg_uploader = "تهانينا! تم قبول ونشر محاضرتك: '" . htmlspecialchars($lecture_info['title']) . "'";
        $stmt_notify_uploader = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        $stmt_notify_uploader->execute([$lecture_info['uploader_user_id'], $notification_msg_uploader, "student/class_lectures.php?id=" . $lecture_info['offering_id']]);

        // 3. إرسال إشعار لباقي طلاب المقرر
        $stmt_students = $pdo->prepare("SELECT user_id FROM student_enrollments WHERE offering_id = ? AND user_id != ?");
        $stmt_students->execute([$lecture_info['offering_id'], $lecture_info['uploader_user_id']]);
        $students_to_notify = $stmt_students->fetchAll(PDO::FETCH_COLUMN);

        if(count($students_to_notify) > 0) {
            $notification_msg_students = "تمت إضافة محاضرة جديدة بعنوان: '" . htmlspecialchars($lecture_info['title']) . "'";
            $stmt_notify_students = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
            foreach ($students_to_notify as $student_id) {
                $stmt_notify_students->execute([$student_id, $notification_msg_students, "student/class_lectures.php?id=" . $lecture_info['offering_id']]);
            }
        }

        $_SESSION['review_status'] = ['type' => 'success', 'message' => 'تم قبول المحاضرة ونشرها بنجاح.'];

    } elseif ($action === 'reject') {
        // --- منطق الرفض ---

        if (empty($rejection_reason)) {
            $_SESSION['review_status'] = ['type' => 'error', 'message' => 'سبب الرفض مطلوب.'];
            header("Location: index.php");
            exit();
        }

        // 1. إرسال إشعار بالرفض للطالب الناشر (يجب إرساله قبل الحذف)
        $notification_msg_rejected = "نأسف، تم رفض محاضرتك '" . htmlspecialchars($lecture_info['title']) . "' بسبب: " . htmlspecialchars($rejection_reason);
        $stmt_notify_rejected = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        $stmt_notify_rejected->execute([$lecture_info['uploader_user_id'], $notification_msg_rejected, "student/my_lectures/index.php"]);

        // 2. حذف المحاضرة من قاعدة البيانات
        $stmt_delete = $pdo->prepare("DELETE FROM lectures WHERE lecture_id = ?");
        $stmt_delete->execute([$lecture_id]);

        // 3. حذف الملف الفعلي من الخادم
        if ($lecture_info['file_path'] && file_exists('../../' . $lecture_info['file_path'])) {
            unlink('../../' . $lecture_info['file_path']);
        }

        $_SESSION['review_status'] = ['type' => 'success', 'message' => 'تم رفض وحذف المحاضرة بنجاح.'];
    }

    $pdo->commit();

} catch (Exception $e) {
    if($pdo->inTransaction()){
        $pdo->rollBack();
    }
    error_log("Lecture processing failed: " . $e->getMessage());
    $_SESSION['review_status'] = ['type' => 'error', 'message' => 'حدث خطأ أثناء معالجة الطلب.'];
}

header("Location: index.php");
exit();
?>