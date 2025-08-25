<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

try {
    $stmt = $pdo->query("
        SELECT p.*, u.username, u.profile_picture
        FROM posts p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.status = 'pending'
        ORDER BY p.created_at ASC
    ");
    $pending_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في جلب المنشورات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>مراجعة المشاركات - لوحة التحكم</title>
               <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">

    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .post-review-card { background: #fff; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); }
        .post-header { padding: 15px 20px; border-bottom: 1px solid #e9ecef; display: flex; align-items: center; gap: 10px; }
        .post-header img { width: 40px; height: 40px; border-radius: 50%; }
        .post-body { padding: 20px; }
        .post-body img { max-width: 100%; border-radius: 8px; margin-top: 15px; }
        .post-actions { padding: 15px 20px; background-color: #f8f9fa; border-top: 1px solid #e9ecef; display: flex; gap: 10px; align-items: center; }
        .post-actions input[type="text"] { flex-grow: 1; padding: 8px; border: 1px solid #ccc; border-radius: 5px; }
    </style>
</head>
<body>
<?php require_once '../templates/header.php'; ?>
<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-check-to-slot"></i> مراجعة المشاركات المعلقة</h2>

        <?php if (isset($_SESSION['review_status'])): ?>
            <p class="status-message <?php echo $_SESSION['review_status']['type']; ?>"><?php echo htmlspecialchars($_SESSION['review_status']['message']); ?></p>
            <?php unset($_SESSION['review_status']); ?>
        <?php endif; ?>

        <?php if (empty($pending_posts)): ?>
            <p>لا توجد مشاركات معلقة للمراجعة حالياً.</p>
        <?php else: ?>
            <?php foreach ($pending_posts as $post): ?>
                <div class="post-review-card">
                    <div class="post-header">
                        <img src="../../uploads/profile_pictures/<?php echo htmlspecialchars($post['profile_picture'] ?: 'default_profile.png'); ?>" alt="صورة المستخدم">
                        <strong><?php echo htmlspecialchars($post['username']); ?></strong>
                    </div>
                    <div class="post-body">
                        <?php if(!empty($post['post_text'])): ?>
                            <p><?php echo nl2br(htmlspecialchars($post['post_text'])); ?></p>
                        <?php endif; ?>
                        <?php if(!empty($post['image_path'])): ?>
                            <img src="../../<?php echo htmlspecialchars($post['image_path']); ?>" alt="صورة المنشور">
                        <?php endif; ?>
                    </div>
                    <div class="post-actions">
                        <form action="process_post.php" method="POST" style="display:contents;">
                            <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                            <button type="submit" name="action" value="approve" class="action-btn" style="background-color:#28a745;"><i class="fa-solid fa-check"></i> قبول</button>
                            <input type="text" name="rejection_reason" placeholder="سبب الرفض (إلزامي للرفض)">
                            <button type="submit" name="action" value="reject" class="action-btn btn-delete"><i class="fa-solid fa-times"></i> رفض</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>
</body>
</html>