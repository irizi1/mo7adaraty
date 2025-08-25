<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// [تم التصحيح هنا] - استخلاص معرفات المقررات من مصفوفة 'enrollments' المعقدة
$requested_offering_ids = [];
if (isset($_POST['enrollments']) && is_array($_POST['enrollments'])) {
    foreach ($_POST['enrollments'] as $enrollment) {
        if (!empty($enrollment['offering_id'])) {
            $requested_offering_ids[] = $enrollment['offering_id'];
        }
    }
}
$reason = trim($_POST['reason']);

if (empty($requested_offering_ids)) {
    $_SESSION['settings_msg'] = ['type' => 'error', 'message' => 'الرجاء اختيار مقرر واحد على الأقل.'];
    header("Location: index.php");
    exit();
}

try {
    // التحقق من عدم وجود طلب معلق بالفعل
    $stmt_check = $pdo->prepare("SELECT request_id FROM enrollment_change_requests WHERE user_id = ? AND status = 'pending'");
    $stmt_check->execute([$user_id]);
    if ($stmt_check->fetch()) {
        throw new Exception("لديك طلب تغيير معلق بالفعل. يرجى الانتظار.");
    }

    // جلب معلومات التسجيل الحالية للطالب
    $stmt_current = $pdo->prepare("
        SELECT d.division_name, c.class_name, g.group_name, t.track_name
        FROM student_enrollments se
        JOIN course_offerings co ON se.offering_id = co.offering_id
        JOIN divisions d ON co.division_id = d.division_id
        JOIN classes c ON co.class_id = c.class_id
        JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        WHERE se.user_id = ?
    ");
    $stmt_current->execute([$user_id]);
    $current_enrollments = $stmt_current->fetchAll(PDO::FETCH_ASSOC);
    $current_enrollment_info = "";
    if (empty($current_enrollments)) {
        $current_enrollment_info = "الطالب غير مسجل حالياً في أي مقرر.";
    } else {
        foreach($current_enrollments as $enroll) {
            $track_info = $enroll['track_name'] ? ' / ' . $enroll['track_name'] : '';
            $current_enrollment_info .= $enroll['division_name'] . ' > ' . $enroll['class_name'] . ' / ' . $enroll['group_name'] . $track_info . "\n";
        }
    }

    // إدراج طلب جديد لكل مقرر مطلوب
    $pdo->beginTransaction();
    $stmt_insert = $pdo->prepare(
        "INSERT INTO enrollment_change_requests (user_id, current_enrollment_info, requested_offering_id, reason) VALUES (?, ?, ?, ?)"
    );
    
    foreach($requested_offering_ids as $offering_id){
        $stmt_insert->execute([$user_id, $current_enrollment_info, (int)$offering_id, $reason]);
    }
    $pdo->commit();

    $_SESSION['settings_msg'] = ['type' => 'success', 'message' => 'تم إرسال طلب تغيير المقرر بنجاح.'];

} catch (Exception $e) {
    if($pdo->inTransaction()){ $pdo->rollBack(); }
    $_SESSION['settings_msg'] = ['type' => 'error', 'message' => $e->getMessage()];
}

header("Location: index.php");
exit();
?>