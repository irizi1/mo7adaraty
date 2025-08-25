<?php
session_start();
require_once '../config/db_connexion.php';

// 1. حماية الصفحة والتحقق من الصلاحيات
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// 2. استقبال البيانات من النموذج والتحقق منها
$title = trim($_POST['title']);
$description = trim($_POST['description']);
$offering_id = filter_input(INPUT_POST, 'offering_id', FILTER_VALIDATE_INT);
$offering_subject_id = filter_input(INPUT_POST, 'offering_subject_id', FILTER_VALIDATE_INT);

// التحقق من أن جميع الحقول المطلوبة موجودة والملف صالح
if (empty($title) || !$offering_id || !$offering_subject_id || !isset($_FILES['lecture_file']) || $_FILES['lecture_file']['error'] != 0) {
    $_SESSION['upload_error'] = "البيانات غير كاملة أو الملف غير صالح. يرجى المحاولة مرة أخرى.";
    header("Location: upload_lecture.php?id=" . $offering_id);
    exit();
}

// 3. التحقق من أن الطالب مسجل في المقرر الذي يحاول الرفع إليه
try {
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM student_enrollments WHERE user_id = ? AND offering_id = ?");
    $stmt_check->execute([$user_id, $offering_id]);
    if ($stmt_check->fetchColumn() == 0) {
        $_SESSION['upload_error'] = "ليس لديك صلاحية الرفع في هذا المقرر.";
        header("Location: upload_lecture.php?id=" . $offering_id);
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['upload_error'] = "خطأ في التحقق من الصلاحيات.";
    header("Location: upload_lecture.php?id=" . $offering_id);
    exit();
}

// 4. معالجة الملف المرفوع
$upload_dir = '../uploads/lectures/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
$file_extension = strtolower(pathinfo($_FILES['lecture_file']['name'], PATHINFO_EXTENSION));
$file_name = uniqid('lecture_', true) . '.' . $file_extension;
$target_file = $upload_dir . $file_name;

// التحقق من نوع الملف وحجمه
$allowed_types = ['pdf', 'png', 'jpg', 'jpeg'];
$max_size = 5 * 1024 * 1024; // 5MB

if (!in_array($file_extension, $allowed_types) || $_FILES['lecture_file']['size'] > $max_size) {
    $_SESSION['upload_error'] = "الملف غير مسموح به أو حجمه كبير جداً (الحد الأقصى 5MB).";
    header("Location: upload_lecture.php?id=" . $offering_id);
    exit();
}

if (!move_uploaded_file($_FILES['lecture_file']['tmp_name'], $target_file)) {
    $_SESSION['upload_error'] = "حدث خطأ أثناء رفع الملف.";
    header("Location: upload_lecture.php?id=" . $offering_id);
    exit();
}

// 5. حفظ البيانات في قاعدة البيانات مع الحالة 'pending'
try {
    $stmt = $pdo->prepare(
        "INSERT INTO lectures (title, description, file_path, file_type, offering_id, offering_subject_id, uploader_user_id, status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
    );
    $stmt->execute([$title, $description, 'uploads/lectures/' . $file_name, $file_extension, $offering_id, $offering_subject_id, $user_id]);

    // إعادة التوجيه إلى صفحة المحاضرات مع رسالة نجاح
    header("Location: class_lectures.php?id=$offering_id&upload_status=success");
    exit();

} catch (PDOException $e) {
    // في حال حدوث خطأ، قم بحذف الملف الذي تم رفعه
    if (file_exists($target_file)) {
        unlink($target_file);
    }
    error_log("Student upload failed: " . $e->getMessage());
    $_SESSION['upload_error'] = "حدث خطأ في قاعدة البيانات، لم يتم حفظ المحاضرة.";
    header("Location: upload_lecture.php?id=" . $offering_id);
    exit();
}
?>