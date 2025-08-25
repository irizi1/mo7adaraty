<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

$post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';
$rejection_reason = trim($_POST['rejection_reason'] ?? '');

if (!$post_id || !in_array($action, ['approve', 'reject'])) {
    $_SESSION['review_status'] = ['type' => 'error', 'message' => 'طلب غير صالح.'];
    header("Location: index.php");
    exit();
}

try {
    $stmt_info = $pdo->prepare("SELECT user_id, post_text FROM posts WHERE post_id = ?");
    $stmt_info->execute([$post_id]);
    $post_info = $stmt_info->fetch(PDO::FETCH_ASSOC);

    if (!$post_info) {
        throw new Exception("لم يتم العثور على المنشور.");
    }

    $pdo->beginTransaction();

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE posts SET status = 'approved' WHERE post_id = ?");
        $stmt->execute([$post_id]);

        $notification_msg = "تهانينا! تم قبول منشورك: '" . mb_substr($post_info['post_text'], 0, 50) . "...'";
        $_SESSION['review_status'] = ['type' => 'success', 'message' => 'تم قبول المنشور بنجاح.'];
    } else { // reject
        if (empty($rejection_reason)) {
            $_SESSION['review_status'] = ['type' => 'error', 'message' => 'سبب الرفض مطلوب.'];
            header("Location: index.php");
            exit();
        }

        // (اختياري: يمكنك حذف المنشور المرفوض بدلاً من تحديث حالته)
        $stmt = $pdo->prepare("UPDATE posts SET status = 'rejected' WHERE post_id = ?");
        $stmt->execute([$post_id]);

        $notification_msg = "نأسف، تم رفض منشورك بسبب: " . htmlspecialchars($rejection_reason);
        $_SESSION['review_status'] = ['type' => 'success', 'message' => 'تم رفض المنشور بنجاح.'];
    }

    // إرسال الإشعار للطالب
    $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
    $stmt_notify->execute([$post_info['user_id'], $notification_msg, 'student/community/index.php']);

    $pdo->commit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['review_status'] = ['type' => 'error', 'message' => 'حدث خطأ أثناء معالجة الطلب.'];
}

header("Location: index.php");
exit();
?>