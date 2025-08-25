<?php
session_start();
require_once '../../config/db_connexion.php';

// 1. حماية الصفحة والتحقق من الصلاحيات
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

// 2. استقبال البيانات من النموذج
$title = trim($_POST['title']);
$description = trim($_POST['description']);
$initial_offering_id = filter_input(INPUT_POST, 'offering_id', FILTER_VALIDATE_INT);
$initial_offering_subject_id = filter_input(INPUT_POST, 'offering_subject_id', FILTER_VALIDATE_INT);
$uploader_user_id = $_SESSION['user_id'];

// التحقق من أن الحقول المطلوبة موجودة والملف تم رفعه بنجاح
if (empty($title) || !$initial_offering_id || !$initial_offering_subject_id || !isset($_FILES['lecture_file']) || $_FILES['lecture_file']['error'] != 0) {
    header("Location: index.php?error=البيانات غير كاملة أو الملف غير صالح.");
    exit();
}

// 3. معالجة الملف المرفوع (مرة واحدة)
$upload_dir = '../../uploads/lectures/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
$file_extension = strtolower(pathinfo($_FILES['lecture_file']['name'], PATHINFO_EXTENSION));
$file_name = uniqid('lecture_', true) . '.' . $file_extension;
$target_file = $upload_dir . $file_name;

if (!move_uploaded_file($_FILES['lecture_file']['tmp_name'], $target_file)) {
    header("Location: index.php?error=حدث خطأ أثناء رفع الملف.");
    exit();
}

// 4. [منطق النشر المتعدد]
try {
    $pdo->beginTransaction();

    // أ. جلب المعلومات الأساسية لتحديد نطاق النشر (الفصل، المادة، الأستاذ)
    $stmt_scope = $pdo->prepare("
        SELECT co.class_id, os.subject_id, os.professor_id
        FROM course_offerings co
        JOIN offering_subjects os ON co.offering_id = os.offering_id
        WHERE co.offering_id = ? AND os.offering_subject_id = ?
    ");
    $stmt_scope->execute([$initial_offering_id, $initial_offering_subject_id]);
    $scope_info = $stmt_scope->fetch(PDO::FETCH_ASSOC);

    if (!$scope_info) {
        throw new Exception("لم يتم العثور على معلومات النطاق.");
    }

    // ب. جلب كل المقررات (الأفواج) المستهدفة التي تطابق الشروط
    $stmt_targets = $pdo->prepare("
        SELECT os.offering_id, os.offering_subject_id
        FROM offering_subjects os
        JOIN course_offerings co ON os.offering_id = co.offering_id
        WHERE co.class_id = ? AND os.subject_id = ? AND os.professor_id = ?
    ");
    $stmt_targets->execute([$scope_info['class_id'], $scope_info['subject_id'], $scope_info['professor_id']]);
    $target_offerings = $stmt_targets->fetchAll(PDO::FETCH_ASSOC);

    // ج. نشر المحاضرة (إضافة سجل) لكل مقرر مستهدف
    $stmt_insert = $pdo->prepare(
        "INSERT INTO lectures (title, description, file_path, file_type, offering_id, offering_subject_id, uploader_user_id, status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, 'approved')"
    );

    $target_offering_ids = [];
    foreach ($target_offerings as $target) {
        $stmt_insert->execute([$title, $description, 'uploads/lectures/' . $file_name, $file_extension, $target['offering_id'], $target['offering_subject_id'], $uploader_user_id]);
        $target_offering_ids[] = $target['offering_id'];
    }

    // د. إرسال إشعارات لجميع الطلاب في جميع المقررات المستهدفة
    if (!empty($target_offering_ids)) {
        $placeholders = implode(',', array_fill(0, count($target_offering_ids), '?'));
        $stmt_students = $pdo->prepare("
            SELECT user_id, offering_id 
            FROM student_enrollments 
            WHERE offering_id IN ($placeholders)
        ");
        $stmt_students->execute($target_offering_ids);
        $students_to_notify = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

        if (count($students_to_notify) > 0) {
            $notification_message = "تم نشر محاضرة جديدة: " . $title;
            $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
            foreach ($students_to_notify as $student) {
                $notification_link = "student/class_lectures.php?id=" . $student['offering_id'];
                $stmt_notify->execute([$student['user_id'], $notification_message, $notification_link]);
            }
        }
    }

    $pdo->commit();
    header("Location: index.php?status=added_successfully");
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (file_exists($target_file)) {
        unlink($target_file);
    }
    header("Location: index.php?error=" . urlencode("خطأ في قاعدة البيانات: " . $e->getMessage()));
    exit();
}
?>