<?php
// يتم استدعاء session_start() في الصفحة الرئيسية قبل استدعاء الهيدر
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// متغيرات افتراضية
$unread_notifications_count = 0;
$notifications = [];

// جلب الإشعارات فقط إذا كان المستخدم مسجل دخوله
if (isset($_SESSION['user_id'])) {
    // استدعاء ملف الاتصال مرة واحدة فقط
    require_once __DIR__ . '/../config/db_connexion.php';
    
    // جلب عدد الإشعارات غير المقروءة
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt_count->execute([$_SESSION['user_id']]);
    $unread_notifications_count = $stmt_count->fetchColumn();

    // جلب آخر 5 إشعارات
    $stmt_list = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt_list->execute([$_SESSION['user_id']]);
    $notifications = $stmt_list->fetchAll();
}
?>
<header class="main-header">
    <div class="logo">
        <a href="/mo7adaraty/index.php">Mo7adaraty</a>
    </div>
    <nav class="main-nav">
        <ul>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="/mo7adaraty/student/profil.php">ملفي الشخصي</a></li>
                <li class="notifications-menu">
                    <a href="/mo7adaraty/student/notifications.php">
                        🔔 الإشعارات
                        <?php if ($unread_notifications_count > 0): ?>
                            <span class="notification-count"><?php echo $unread_notifications_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="notifications-dropdown">
                        <?php if (count($notifications) > 0): ?>
                            <?php foreach ($notifications as $notification): ?>
                                <a class="notification-item" href="<?php echo '/mo7adaraty/' . htmlspecialchars($notification['link']); ?>">
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                </a>
                            <?php endforeach; ?>
                            <a class="view-all" href="/mo7adaraty/student/notifications.php">عرض كل الإشعارات</a>
                        <?php else: ?>
                            <p>لا توجد إشعارات جديدة.</p>
                        <?php endif; ?>
                    </div>
                </li>
                <li><a href="/mo7adaraty/logout.php">تسجيل الخروج</a></li>
            <?php else: ?>
                <li><a href="/mo7adaraty/login.php">تسجيل الدخول</a></li>
                <li><a href="/mo7adaraty/signup.php">إنشاء حساب</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
<style>
/* يمكنك نقل هذا الـ CSS إلى ملف منفصل لاحقاً */
.main-header { display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 10px 40px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.logo a { font-size: 24px; font-weight: bold; color: #007bff; text-decoration: none; }
.main-nav ul { list-style: none; margin: 0; padding: 0; display: flex; align-items: center; }
.main-nav li a { padding: 15px; text-decoration: none; color: #333; font-weight: 500; display: block; }
.notifications-menu { position: relative; }
.notification-count { background: red; color: white; border-radius: 50%; padding: 2px 6px; font-size: 12px; position: absolute; top: 10px; right: 5px; }
.notifications-dropdown { display: none; position: absolute; top: 100%; left: 0; background: white; border: 1px solid #ddd; box-shadow: 0 4px 10px rgba(0,0,0,0.1); min-width: 300px; z-index: 1000; }
.notifications-menu:hover .notifications-dropdown { display: block; }
.notification-item, .view-all { display: block; padding: 10px; border-bottom: 1px solid #eee; text-decoration: none; color: #333; }
.notification-item:hover { background: #f4f4f4; }
.view-all { text-align: center; font-weight: bold; }
.notifications-dropdown p { padding: 10px; text-align: center; color: #888; }
</style>