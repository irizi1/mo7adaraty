<?php
session_start();
require_once '../../config/db_connexion.php';

$content_id = filter_input(INPUT_GET, 'content_id', FILTER_VALIDATE_INT);
$content_type = $_GET['content_type'] ?? '';

if (!$content_id || !in_array($content_type, ['lecture', 'exam', 'post'])) {
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT c.comment_id, c.comment_text, c.created_at, c.user_id,
               u.username, u.profile_picture 
        FROM comments c 
        JOIN users u ON c.user_id = u.user_id
        WHERE c.content_id = ? AND c.content_type = ?
        ORDER BY c.created_at ASC
    ");
    
    $stmt->execute([$content_id, $content_type]);
    $comments = $stmt->fetchAll();

    if (count($comments) > 0) {
        foreach ($comments as $comment) {
            $profile_picture = !empty($comment['profile_picture']) ? htmlspecialchars($comment['profile_picture']) : 'default_profile.png';
            $image_path = '../../uploads/profile_pictures/' . $profile_picture;

            echo '<div class="comment">';
            echo '  <div class="comment-author-info">';
            echo '      <img src="' . $image_path . '" alt="صورة الكاتب" onerror="this.onerror=null;this.src=\'../../uploads/profile_pictures/default_profile.png\';">';
            echo '  </div>';
            echo '  <div class="comment-content">';
            echo '      <span class="comment-author">' . htmlspecialchars($comment['username']) . '</span>';
            echo '      <p class="comment-text">' . nl2br(htmlspecialchars($comment['comment_text'])) . '</p>';
            
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $comment['user_id']) {
                // [تم التصحيح هنا] - تحديث مسارات أزرار التعديل والحذف
                echo '<div class="comment-actions">';
                echo '  <a href="#" class="edit-comment-btn" data-comment-id="' . $comment['comment_id'] . '">تعديل</a>';
                echo '  <a href="delete_comment.php?id=' . $comment['comment_id'] . '" class="delete-comment-btn" onclick="return confirm(\'هل أنت متأكد من حذف هذا التعليق؟\');">حذف</a>';
                echo '</div>';
            }
            
            echo '  </div>';
            echo '</div>';
        }
    } else {
        echo '<p class="no-comments">لا توجد تعليقات حتى الآن. كن أول من يعلق!</p>';
    }

} catch (PDOException $e) {
    // Log error
}
?>