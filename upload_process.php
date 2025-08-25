<?php
session_start();
require_once 'config/db_connexion.php';

// ==========================================================
// 1. حماية الملف والتحقق من الصلاحيات
// ==========================================================

// أ. التحقق من أن الطلب تم عبر POST وأن المستخدم مسجل دخوله
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_SESSION['user_id'])) {
    // توجيه لأي مكان آخر أو عرض رسالة خطأ
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ==========================================================
// 2. استقبال البيانات والتحقق منها
// ==========================================================
$class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
$content_type = $_POST['content_type'] ?? '';
$title = trim($_POST['title']);
$description = trim($_POST['description']);

// التحقق من أن البيانات الأساسية موجودة
if (!$class_id || !in_array($content_type, ['lecture', 'exam']) || empty($title) || !isset($_FILES['file']) || $_FILES['file']['error'] != 0) {
    header("Location: student/class_details.php?id=$class_id&error=المرجو ملء جميع الحقول ورفع ملف صحيح.");
    exit();
}

// ==========================================================
// 3. التحقق من أن الطالب مسجل في الفصل الذي يحاول النشر فيه
// ==========================================================
try {
    $stmt_check = $pdo->prepare("
        SELECT COUNT(*) FROM student_enrollments se
        JOIN `groups` g ON se.group_id = g.group_id
        WHERE se.user_id = ? AND g.class_id = ?
    ");
    $stmt_check->execute([$user_id, $class_id]);
    if ($stmt_check->fetchColumn() == 0) {
        header("Location: student/profil.php?error=ليس لديك صلاحية النشر في هذا الفصل.");
        exit();
    }
} catch (PDOException $e) {
    header("Location: student/class_details.php?id=$class_id&error=خطأ في التحقق من الصلاحيات.");
    exit();
}

// ==========================================================
// 4. معالجة الملف المرفوع
// ==========================================================
$table_name = ($content_type == 'lecture') ? 'lectures' : 'exams';
$upload_dir = 'uploads/' . $table_name . '/';

// التأكد من وجود مجلد الرفع، وإن لم يكن، يتم إنشاؤه
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// إنشاء اسم فريد للملف
$file_extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
$file_name = uniqid($content_type . '_', true) . '.' . $file_extension;
$target_file = $upload_dir . $file_name;

// نقل الملف
if (!move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
    header("Location: student/class_details.php?id=$class_id&error=حدث خطأ أثناء رفع الملف.");
    exit();
}


// ==========================================================
// 5. حفظ البيانات في قاعدة البيانات وإرسال الإشعارات
// ==========================================================
try {
    // أ. إضافة المحتوى إلى الجدول المناسب
    $stmt_insert = $pdo->prepare("
        INSERT INTO $table_name (title, description, file_path, file_type, class_id, uploader_user_id)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt_insert->execute([$title, $description, $target_file, $file_extension, $class_id, $user_id]);

    // ب. إرسال إشعارات لباقي الطلاب في الفصل
    // جلب كل الطلاب المسجلين في الفصل باستثناء الطالب الذي قام بالنشر
    $stmt_students = $pdo->prepare("
        SELECT se.user_id FROM student_enrollments se
        JOIN `groups` g ON se.group_id = g.group_id
        WHERE g.class_id = ? AND se.user_id != ?
    ");
    $stmt_students->execute([$class_id, $user_id]);
    $students_to_notify = $stmt_students->fetchAll(PDO::FETCH_COLUMN);
    
    // إنشاء الإشعار
    if (count($students_to_notify) > 0) {
        $notification_message = "قام الطالب " . $_SESSION['username'] . " بإضافة " . ($content_type == 'lecture' ? 'محاضرة' : 'امتحان') . " جديد بعنوان: " . $title;
        $notification_link = "student/class_details.php?id=" . $class_id;
        
        $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        foreach ($students_to_notify as $student_id) {
            $stmt_notify->execute([$student_id, $notification_message, $notification_link]);
        }
    }

} catch (PDOException $e) {
    header("Location: student/class_details.php?id=$class_id&error=خطأ أثناء حفظ البيانات.");
    exit();
}

// ==========================================================
// 6. توجيه المستخدم مرة أخرى إلى صفحة الفصل مع رسالة نجاح
// ==========================================================
header("Location: student/class_details.php?id=$class_id&status=upload_success");
exit();
?>