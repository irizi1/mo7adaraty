<?php
session_start();
require_once '../../config/db_connexion.php';

// 1. حماية الصفحة والتحقق من الصلاحيات
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php?error=وصول غير مصرح به");
    exit();
}

// 2. استقبال البيانات من النموذج والتحقق منها
$title = trim($_POST['title']);
$description = trim($_POST['description']);
$offering_id = filter_input(INPUT_POST, 'offering_id', FILTER_VALIDATE_INT);
$offering_subject_id = filter_input(INPUT_POST, 'offering_subject_id', FILTER_VALIDATE_INT);
$uploader_user_id = $_SESSION['user_id'];

// التحقق من أن الحقول المطلوبة موجودة والملف تم رفعه بنجاح
if (empty($title) || !$offering_id || !$offering_subject_id || !isset($_FILES['exam_file']) || $_FILES['exam_file']['error'] != 0) {
    header("Location: index.php?error=البيانات غير كاملة أو الملف غير صالح.");
    exit();
}

// 3. معالجة الملف المرفوع
$upload_dir = '../../uploads/exams/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
$file_extension = strtolower(pathinfo($_FILES['exam_file']['name'], PATHINFO_EXTENSION));
$file_name = uniqid('exam_', true) . '.' . $file_extension;
$target_file = $upload_dir . $file_name;

if (!move_uploaded_file($_FILES['exam_file']['tmp_name'], $target_file)) {
    header("Location: index.php?error=" . urlencode("حدث خطأ أثناء رفع الملف."));
    exit();
}

// 4. حفظ البيانات وإرسال الإشعارات
try {
    $pdo->beginTransaction();

    // أ. جلب class_id من course_offerings
    $stmt_class = $pdo->prepare("SELECT class_id FROM course_offerings WHERE offering_id = ?");
    $stmt_class->execute([$offering_id]);
    $class_id = $stmt_class->fetchColumn();

    if (!$class_id) {
        throw new Exception("لم يتم العثور على الفصل الدراسي المرتبط بهذا المقرر.");
    }

    // ب. حفظ الامتحان في قاعدة البيانات
    $stmt = $pdo->prepare(
        "INSERT INTO exams (title, description, file_path, file_type, class_id, offering_subject_id, uploader_user_id) 
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$title, $description, 'uploads/exams/' . $file_name, $file_extension, $class_id, $offering_subject_id, $uploader_user_id]);

    // ج. [تم التحديث] جلب جميع الطلاب المسجلين في هذا الفصل الدراسي بأكمله
    $stmt_students = $pdo->prepare("
        SELECT se.user_id, se.offering_id
        FROM student_enrollments se
        JOIN course_offerings co ON se.offering_id = co.offering_id
        WHERE co.class_id = ?
    ");
    $stmt_students->execute([$class_id]);
    $students_to_notify = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
    
    // د. إرسال الإشعارات
    if (count($students_to_notify) > 0) {
        $notification_message = "تم نشر امتحان جديد: " . $title;
        
        $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        foreach ($students_to_notify as $student) {
            // كل طالب يتلقى رابطًا خاصًا بالمقرر المسجل فيه
            $notification_link = "student/class_exams.php?id=" . $student['offering_id'];
            $stmt_notify->execute([$student['user_id'], $notification_message, $notification_link]);
        }
    }

    $pdo->commit();
    header("Location: index.php?status=added_successfully");
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    if (file_exists($target_file)) {
        unlink($target_file);
    }
    header("Location: index.php?error=" . urlencode("خطأ في قاعدة البيانات: " . $e->getMessage()));
    exit();
}
?>