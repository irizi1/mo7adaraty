<?php
session_start();
require_once '../../config/db_connexion.php';

// التحقق من تسجيل الدخول وأن الطلب من نوع POST
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$post_text = trim($_POST['post_text'] ?? '');
$background_style = trim($_POST['background_style'] ?? 'none');
$post_image = $_FILES['post_image'] ?? null;

// التحقق من أن هناك نص أو صورة على الأقل
if (empty($post_text) && (!isset($post_image) || $post_image['error'] === UPLOAD_ERR_NO_FILE)) {
    header("Location: index.php?error=empty_post");
    exit();
}

// قائمة الأنماط المسموح بها
$valid_styles = ['none', 'blue', 'red', 'green', 'yellow', 'purple'];
if (!in_array($background_style, $valid_styles)) {
    $background_style = 'none'; // الافتراضي إذا كانت القيمة غير صالحة
}

// معالجة رفع الصورة
$image_path = null;
if (isset($post_image) && $post_image['error'] === UPLOAD_ERR_OK) {
    // التحقق من أن الصورة ليست مع خلفية
    if ($background_style !== 'none') {
        header("Location: index.php?error=background_with_image_not_allowed");
        exit();
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5 ميجابايت
    $upload_dir = '../../uploads/posts/';
    
    // التحقق من نوع الملف وحجمه
    if (!in_array($post_image['type'], $allowed_types) || $post_image['size'] > $max_size) {
        header("Location: index.php?error=invalid_image");
        exit();
    }

    // إنشاء اسم ملف فريد
    $file_extension = pathinfo($post_image['name'], PATHINFO_EXTENSION);
    $image_path = 'uploads/posts/post_' . uniqid() . '.' . $file_extension;

    // نقل الملف إلى المجلد
    if (!move_uploaded_file($post_image['tmp_name'], '../../' . $image_path)) {
        header("Location: index.php?error=upload_failed");
        exit();
    }
}

// إذا لم يكن هناك نص وتم اختيار خلفية، رفض المنشور
if (empty($post_text) && $background_style !== 'none') {
    header("Location: index.php?error=background_without_text");
    exit();
}

try {
    // إدراج المنشور في قاعدة البيانات
    $stmt = $pdo->prepare("
        INSERT INTO posts (user_id, post_text, image_path, background_style, status)
        VALUES (?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([$user_id, $post_text ?: null, $image_path, $background_style]);

    header("Location: index.php?status=post_submitted");
    exit();
} catch (PDOException $e) {
    header("Location: index.php?error=db_error&message=" . urlencode($e->getMessage()));
    exit();
}
?>