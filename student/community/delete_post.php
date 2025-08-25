<?php
session_start();
require_once '../../config/db_connexion.php';

// 1. حماية الصفحة والتحقق من الصلاحيات
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST'){
    header("Location: ../../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// 2. استقبال ID المنشور والتحقق منه
$post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
if (!$post_id) {
    header("Location: my_pending_posts.php");
    exit();
}

try {
    $pdo->beginTransaction();

    // 3. التأكد من أن الطالب هو مالك المنشور وأنه لا يزال قيد المراجعة
    $stmt_check = $pdo->prepare("SELECT image_path FROM posts WHERE post_id = ? AND user_id = ? AND status = 'pending'");
    $stmt_check->execute([$post_id, $user_id]);
    $post = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if ($post) {
        // 4. إذا تم العثور على المنشور، قم بحذفه
        $stmt_delete = $pdo->prepare("DELETE FROM posts WHERE post_id = ?");
        $stmt_delete->execute([$post_id]);

        // 5. إذا نجح الحذف من قاعدة البيانات، قم بحذف الصورة من الخادم (إن وجدت)
        if ($stmt_delete->rowCount() > 0) {
            if (!empty($post['image_path']) && file_exists('../../' . $post['image_path'])) {
                unlink('../../' . $post['image_path']);
            }
            
            $pdo->commit();
            $_SESSION['post_action_status'] = ['type' => 'success', 'message' => 'تم حذف المنشور بنجاح.'];

        } else {
            throw new Exception("فشل حذف سجل المنشور من قاعدة البيانات.");
        }
        
    } else {
        throw new Exception("محاولة حذف منشور غير مسموح بها أو تمت مراجعته بالفعل.");
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Post deletion by student failed: " . $e->getMessage());
    $_SESSION['post_action_status'] = ['type' => 'error', 'message' => 'حدث خطأ أثناء محاولة حذف المنشور.'];
}

// 6. إعادة التوجيه إلى الصفحة السابقة
header("Location: my_pending_posts.php");
exit();
?>