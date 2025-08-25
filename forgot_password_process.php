<?php
session_start();
require_once 'config/db_connexion.php';

// التحقق من أن الطلب هو POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// 1. استقبال البيانات من النموذج
$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$username = trim($_POST['username']);
$registered_email = trim($_POST['registered_email']);
$contact_info = trim($_POST['contact_info']);

// 2. التحقق من أن جميع البيانات موجودة
if (!$user_id || empty($username) || empty($registered_email) || empty($contact_info)) {
    $_SESSION['reset_error'] = "حدث خطأ. يرجى المحاولة مرة أخرى.";
    header("Location: forgot_password.php");
    exit();
}

try {
    // 3. التحقق من أن البريد الإلكتروني المدخل يطابق البريد المسجل للمستخدم
    $stmt = $pdo->prepare("SELECT email FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_email_from_db = $stmt->fetchColumn();

    if ($user_email_from_db && $user_email_from_db === $registered_email) {
        // --- البريد صحيح، قم بتسجيل الطلب ---

        // التحقق من عدم وجود طلب معلق بالفعل لهذا المستخدم
        $stmt_check = $pdo->prepare("SELECT request_id FROM password_reset_requests WHERE user_id = ? AND status = 'pending'");
        $stmt_check->execute([$user_id]);
        if ($stmt_check->fetch()) {
            $_SESSION['reset_success'] = "لديك طلب معلق بالفعل. يرجى الانتظار حتى تتم معالجته من قبل الإدارة.";
            header("Location: forgot_password.php");
            exit();
        }

        // إدراج الطلب الجديد في قاعدة البيانات
        $stmt_insert = $pdo->prepare(
            "INSERT INTO password_reset_requests (user_id, username, contact_info) VALUES (?, ?, ?)"
        );
        $stmt_insert->execute([$user_id, $username, $contact_info]);

        // إرسال رسالة نجاح
        $_SESSION['reset_success'] = "تم إرسال طلبك بنجاح. ستقوم الإدارة بمراجعته والتواصل معك قريباً عبر الوسيلة التي قدمتها.";
        header("Location: forgot_password.php");
        exit();

    } else {
        // --- البريد غير صحيح ---
        $_SESSION['reset_error'] = "البريد الإلكتروني المسجل الذي أدخلته غير صحيح. يرجى المحاولة مرة أخرى.";
        // نعيد المستخدم للخطوة الأولى للأمان
        header("Location: forgot_password.php");
        exit();
    }

} catch (PDOException $e) {
    error_log("Password reset request error: " . $e->getMessage());
    $_SESSION['reset_error'] = "حدث خطأ في قاعدة البيانات. يرجى المحاولة لاحقاً.";
    header("Location: forgot_password.php");
    exit();
}
?>