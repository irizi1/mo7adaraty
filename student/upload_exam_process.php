<?php
session_start();
require_once '../config/db_connexion.php';

// 1. حماية الصفحة والتحقق من الصلاحيات
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// 2. استقبال البيانات من النموذج المبسط
$title = trim($_POST['title']);
$description = trim($_POST['description']);
$offering_id = filter_input(INPUT_POST, 'offering_id', FILTER_VALIDATE_INT);

// التحقق من أن البيانات الأساسية موجودة والملف صالح
if (empty($title) || !$offering_id || !isset($_FILES['exam_file']) || $_FILES['exam_file']['error'] != 0) {
    $_SESSION['upload_error'] = "البيانات غير كاملة أو الملف غير صالح.";
    header("Location: upload_exam.php?id=" . $offering_id);
    exit();
}

// 3. التحقق من صلاحية الطالب للرفع في هذا المقرر
try {
    $stmt_check = $pdo->prepare("SELECT co.class_id FROM student_enrollments se JOIN course_offerings co ON se.offering_id = co.offering_id WHERE se.user_id = ? AND se.offering_id = ?");
    $stmt_check->execute([$user_id, $offering_id]);
    $class_id = $stmt_check->fetchColumn();

    if (!$class_id) {
        $_SESSION['upload_error'] = "ليس لديك صلاحية النشر في هذا المقرر.";
        header("Location: upload_exam.php?id=" . $offering_id);
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['upload_error'] = "خطأ في التحقق من الصلاحيات.";
    header("Location: upload_exam.php?id=" . $offering_id);
    exit();
}

// 4. معالجة الملف المرفوع
$upload_dir = '../uploads/exams/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
$file_extension = strtolower(pathinfo($_FILES['exam_file']['name'], PATHINFO_EXTENSION));
$file_name = uniqid('exam_student_', true) . '.' . $file_extension;
$target_file = $upload_dir . $file_name;

if (!move_uploaded_file($_FILES['exam_file']['tmp_name'], $target_file)) {
    $_SESSION['upload_error'] = "حدث خطأ أثناء رفع الملف.";
    header("Location: upload_exam.php?id=" . $offering_id);
    exit();
}

// 5. حفظ البيانات وإرسال الإشعارات
try {
    $pdo->beginTransaction();

    // أ. حفظ الامتحان وربطه مباشرة بالفصل (offering_subject_id يكون NULL)
    $stmt_insert = $pdo->prepare(
        "INSERT INTO exams (title, description, file_path, file_type, class_id, uploader_user_id, offering_subject_id) 
         VALUES (?, ?, ?, ?, ?, ?, NULL)"
    );
    $stmt_insert->execute([$title, $description, 'uploads/exams/' . $file_name, $file_extension, $class_id, $user_id]);

    // ب. إرسال إشعارات لجميع طلاب الفصل
    $stmt_students = $pdo->prepare("
        SELECT se.user_id, se.offering_id
        FROM student_enrollments se
        JOIN course_offerings co ON se.offering_id = co.offering_id
        WHERE co.class_id = ?
    ");
    $stmt_students->execute([$class_id]);
    $students_to_notify = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($students_to_notify) > 0) {
        $notification_message = "قام الطالب " . $_SESSION['username'] . " بإضافة امتحان جديد: " . $title;
        
        $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        foreach ($students_to_notify as $student) {
            $notification_link = "student/class_exams.php?id=" . $student['offering_id'];
            $stmt_notify->execute([$student['user_id'], $notification_message, $notification_link]);
        }
    }

    $pdo->commit();
    header("Location: class_exams.php?id=$offering_id&status=upload_success");
    exit();

} catch (PDOException $e) {
    $pdo->rollBack();
    if (file_exists($target_file)) {
        unlink($target_file);
    }
    $_SESSION['upload_error'] = "حدث خطأ أثناء حفظ الامتحان في قاعدة البيانات.";
    header("Location: upload_exam.php?id=" . $offering_id);
    exit();
}
?>