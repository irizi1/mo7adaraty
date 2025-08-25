<?php
session_start();
require_once '../config/db_connexion.php';

// ==========================================================
// 1. حماية الصفحة والتحقق من الصلاحيات
/// حماية الصفحة
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// ==========================================================
// 2. جلب الإشعارات وتحديث حالتها
// ==========================================================
try {
    // أ. جلب جميع الإشعارات الخاصة بالمستخدم (الأحدث أولاً)
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $all_notifications = $stmt->fetchAll();

    // ب. تحديث حالة الإشعارات غير المقروءة إلى "مقروءة"
    // يتم هذا بعد جلب الإشعارات لكي يرى المستخدم العدد الصحيح في الهيدر أولاً
    $stmt_update = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt_update->execute([$user_id]);

} catch (PDOException $e) {
    die("خطأ في جلب الإشعارات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإشعارات - Mo7adaraty</title>
     <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
         .page-container { max-width: 900px; margin: 40px auto; padding: 20px; }
         .notification-page-item { 
             background: #fff; 
             padding: 15px 20px; 
             border-right: 5px solid #007bff;
             border-radius: 8px; 
             margin-bottom: 15px;
             transition: box-shadow 0.2s;
         }
         .notification-page-item:hover {
             box-shadow: 0 4px 15px rgba(0,0,0,0.08);
         }
         .notification-page-item a { text-decoration: none; color: #333; display: block; }
         .notification-page-item p { margin: 0 0 5px 0; }
         .notification-page-item small { color: #888; }
    </style>
</head>
<body>

<?php require_once '../templates/header.php'; ?>

<div class="page-container">
    <h1><i class="fa-solid fa-bell"></i> جميع الإشعارات</h1>

    <div class="notifications-list">
        <?php if (count($all_notifications) > 0): ?>
            <?php foreach ($all_notifications as $notification): ?>
                <div class="notification-page-item">
                    <a href="<?php echo '/mo7adaraty/' . htmlspecialchars($notification['link']); ?>">
                        <p><?php echo htmlspecialchars($notification['message']); ?></p>
                        <small><?php echo date('d/m/Y \ا\ل\س\ا\ع\ة H:i', strtotime($notification['created_at'])); ?></small>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; padding: 20px; background: #fff; border-radius: 8px;">لا يوجد أي إشعارات لعرضها.</p>
        <?php endif; ?>
    </div>
</div>
<?php include '../templates/footer.php'; ?>

</body>
</html>