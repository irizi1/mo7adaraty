<?php
session_start();
require_once '../../config/db_connexion.php';

// 1. حماية الصفحة والتحقق من الصلاحيات
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// 2. استقبال البيانات والتحقق منها
$post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
$post_text = trim($_POST['post_text']);
$post_image = $_FILES['post_image'];

if (!$post_id) {
    $_SESSION['post_action_status'] = ['type' => 'error', 'message' => 'معرف المنشور غير صحيح.'];
    header("Location: my_pending_posts.php");
    exit();
}

try {
    // 3. جلب بيانات المنشور للتأكد من ملكية الطالب وأنه لا يزال قيد المراجعة
    $stmt_check = $pdo->prepare("SELECT * FROM posts WHERE post_id = ? AND user_id = ? AND status = 'pending'");
    $stmt_check->execute([$post_id, $user_id]);
    $post = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        throw new Exception("محاولة تعديل منشور غير مسموح بها أو تمت مراجعته بالفعل.");
    }

    $pdo->beginTransaction();

    $new_image_path = $post['image_path']; // احتفظ بالمسار القديم كقيمة افتراضية

    // 4. التحقق إذا تم رفع صورة جديدة
    if (isset($post_image) && $post_image['error'] === UPLOAD_ERR_OK) {
        // حذف الصورة القديمة (إذا كانت موجودة)
        if (!empty($post['image_path']) && file_exists('../../' . $post['image_path'])) {
            unlink('../../' . $post['image_path']);
        }
        
        // رفع الصورة الجديدة
        $upload_dir = '../../uploads/posts/';
        $image_name = uniqid('post_', true) . '.' . pathinfo($post_image['name'], PATHINFO_EXTENSION);
        $target_file = $upload_dir . $image_name;

        if (move_uploaded_file($post_image['tmp_name'], $target_file)) {
            $new_image_path = 'uploads/posts/' . $image_name;
        } else {
            throw new Exception("فشل رفع الصورة الجديدة.");
        }
    }

    // 5. تحديث البيانات في قاعدة البيانات
    $stmt_update = $pdo->prepare(
        "UPDATE posts SET post_text = ?, image_path = ? WHERE post_id = ?"
    );
    $stmt_update->execute([$post_text, $new_image_path, $post_id]);

    $pdo->commit();
    $_SESSION['post_action_status'] = ['type' => 'success', 'message' => 'تم تحديث المنشور بنجاح.'];

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Post update failed: " . $e->getMessage());
    $_SESSION['post_action_status'] = ['type' => 'error', 'message' => 'حدث خطأ أثناء تحديث المنشور.'];
}

header("Location: my_pending_posts.php");
exit();
?>