<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

try {
    // جلب منشورات المستخدم الحالي التي هي فقط قيد المراجعة
    $stmt = $pdo->prepare("
        SELECT *
        FROM posts
        WHERE user_id = ? AND status = 'pending'
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $pending_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>مشاركاتي قيد المراجعة - Mo7adaraty</title>
         <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --brand-color: #007bff; --bg-color: #f4f8fc; --card-bg: #ffffff;
            --border-color: #e9ecef; --text-secondary: #6c757d;
        }
        body { background-color: var(--bg-color); }
        .page-container { max-width: 900px; margin: 20px auto; padding: 20px; }
        .page-container h1 { text-align: center; }
        .post-card {
            background: var(--card-bg); border-radius: 10px; margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden;
            transition: box-shadow 0.3s ease;
        }
        .post-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .card-body { padding: 20px; }
        .post-text { margin: 0 0 15px; color: #333; white-space: pre-wrap; }
        .post-image img { max-width: 100%; border-radius: 8px; margin-top: 10px; }
        .card-meta { color: var(--text-secondary); font-size: 14px; margin-bottom: 15px; }
        .card-footer {
            background: #f8f9fa; padding: 15px 20px;
            display: flex; gap: 10px; justify-content: flex-end;
            border-top: 1px solid var(--border-color);
        }
        .action-btn {
            padding: 8px 15px; text-decoration: none; color: white; border-radius: 6px;
            font-size: 14px; font-weight: 600; border: none; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .action-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
        .edit-btn { background-color: #ffc107; color: #212529; }
        .delete-btn { background-color: #dc3545; }
        .no-data { text-align: center; padding: 25px; background-color: var(--card-bg); border-radius: 12px; color: var(--text-secondary); }
    </style>
</head>
<body>
<?php require_once '../../templates/header.php'; ?>
<div class="container page-container">
    <h1><i class="fa-solid fa-hourglass-half"></i> مشاركاتي قيد المراجعة</h1>
    <p style="text-align:center;"><a href="index.php">&larr; العودة إلى ساحة المشاركات</a></p>

    <?php if (isset($_SESSION['post_action_status'])): ?>
        <p class="status-message <?php echo $_SESSION['post_action_status']['type']; ?>">
            <?php echo htmlspecialchars($_SESSION['post_action_status']['message']); ?>
        </p>
        <?php unset($_SESSION['post_action_status']); ?>
    <?php endif; ?>

    <?php if (empty($pending_posts)): ?>
        <p class="no-data">لا توجد لديك مشاركات قيد المراجعة حالياً.</p>
    <?php else: ?>
        <?php foreach ($pending_posts as $post): ?>
            <div class="post-card">
                <div class="card-body">
                    <p class="card-meta">
                        <strong>تاريخ الإرسال:</strong> <?php echo date('Y-m-d', strtotime($post['created_at'])); ?>
                    </p>
                    <?php if(!empty($post['post_text'])): ?>
                        <p class="post-text"><?php echo nl2br(htmlspecialchars($post['post_text'])); ?></p>
                    <?php endif; ?>
                     <?php if(!empty($post['image_path'])): ?>
                        <div class="post-image">
                            <img src="../../<?php echo htmlspecialchars($post['image_path']); ?>" alt="صورة المنشور">
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="edit_post.php?id=<?php echo $post['post_id']; ?>" class="action-btn edit-btn">
                        <i class="fa-solid fa-pen"></i> تعديل
                    </a>
                    <form action="delete_post.php" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا المنشور؟');" style="display: inline;">
                        <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                        <button type="submit" class="action-btn delete-btn">
                            <i class="fa-solid fa-trash"></i> حذف
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>