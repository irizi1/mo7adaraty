<?php
session_start();
// [تم التحديث] - تحديث المسار ليتناسب مع الموقع الجديد
require_once '../../config/db_connexion.php';

// التحقق من أن المستخدم مسجل دخوله وأن الطلب صحيح
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'وصول غير مصرح به.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$content_id = $_POST['content_id'];
$content_type = $_POST['content_type'];
$reaction_type = $_POST['reaction_type'];

// التحقق من صحة البيانات
if (empty($content_id) || !in_array($content_type, ['lecture', 'exam', 'post']) || !in_array($reaction_type, ['like', 'dislike'])) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير صالحة.']);
    exit();
}

try {
    // التحقق إذا كان المستخدم قد تفاعل مع هذا المحتوى من قبل
    $stmt_check = $pdo->prepare("SELECT reaction_type FROM content_reactions WHERE user_id = ? AND content_id = ? AND content_type = ?");
    $stmt_check->execute([$user_id, $content_id, $content_type]);
    $existing_reaction = $stmt_check->fetchColumn();

    if ($existing_reaction) {
        // إذا كان المستخدم يضغط على نفس التفاعل مرة أخرى، يتم حذفه
        if ($existing_reaction === $reaction_type) {
            $stmt_delete = $pdo->prepare("DELETE FROM content_reactions WHERE user_id = ? AND content_id = ? AND content_type = ?");
            $stmt_delete->execute([$user_id, $content_id, $content_type]);
        } else {
            // إذا كان يغير تفاعله
            $stmt_update = $pdo->prepare("UPDATE content_reactions SET reaction_type = ? WHERE user_id = ? AND content_id = ? AND content_type = ?");
            $stmt_update->execute([$reaction_type, $user_id, $content_id, $content_type]);
        }
    } else {
        // إذا كان هذا هو التفاعل الأول للمستخدم
        $stmt_insert = $pdo->prepare("INSERT INTO content_reactions (user_id, content_id, content_type, reaction_type) VALUES (?, ?, ?, ?)");
        $stmt_insert->execute([$user_id, $content_id, $content_type, $reaction_type]);
    }

    // جلب العدد الجديد للإعجابات وعدم الإعجاب
    $stmt_likes = $pdo->prepare("SELECT COUNT(*) FROM content_reactions WHERE content_id = ? AND content_type = ? AND reaction_type = 'like'");
    $stmt_likes->execute([$content_id, $content_type]);
    $likes_count = $stmt_likes->fetchColumn();

    $stmt_dislikes = $pdo->prepare("SELECT COUNT(*) FROM content_reactions WHERE content_id = ? AND content_type = ? AND reaction_type = 'dislike'");
    $stmt_dislikes->execute([$content_id, $content_type]);
    $dislikes_count = $stmt_dislikes->fetchColumn();

    // جلب تفاعل المستخدم الحالي بعد التحديث
    $stmt_user_reaction = $pdo->prepare("SELECT reaction_type FROM content_reactions WHERE user_id = ? AND content_id = ? AND content_type = ?");
    $stmt_user_reaction->execute([$user_id, $content_id, $content_type]);
    $user_reaction = $stmt_user_reaction->fetchColumn();


    // إرجاع الأعداد الجديدة وحالة تفاعل المستخدم
    echo json_encode([
        'success' => true, 
        'likes' => $likes_count, 
        'dislikes' => $dislikes_count,
        'user_reaction' => $user_reaction
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ في قاعدة البيانات.']);
}
?>