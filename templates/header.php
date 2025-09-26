<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$unread_notifications_count = 0;
$notifications = [];

if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/db_connexion.php';
    
    // عدد الإشعارات غير المقروءة
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt_count->execute([$_SESSION['user_id']]);
    $unread_notifications_count = $stmt_count->fetchColumn();

    // آخر 5 إشعارات
    $stmt = $pdo->prepare("
        SELECT message, link, is_read, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll();
}
?>
<header class="main-header">
  <div class="logo">
    <a href="/mo7adaraty/index.php">Mo7adaraty</a>
  </div>

  <nav class="main-nav" id="mainNav">
    <ul>
      <?php if (isset($_SESSION['user_id'])): ?>
        <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
          <li><a href="/mo7adaraty/admin/index.php"><i class="fa-solid fa-tachometer-alt"></i> لوحة التحكم</a></li>
        <?php endif; ?>
        <li><a href="/mo7adaraty/student/profil.php"><i class="fa-solid fa-user"></i> ملفي الشخصي</a></li>
        <li><a href="/mo7adaraty/student/community/index.php"><i class="fa-solid fa-users"></i> ساحة المشاركات</a></li>
        <li><a href="/mo7adaraty/student/announcements.php"><i class="fa-solid fa-bullhorn"></i> الإعلانات العامة</a></li>
        
        <li><a href="/mo7adaraty/student/whatsapp_groups/index.php"><i class="fab fa-whatsapp"></i> مجموعات الواتساب</a></li>
        <li><a href="/mo7adaraty/student/contact/index.php"><i class="fa-solid fa-envelope"></i> تواصل مع الإدارة</a></li>
        <li class="notifications-menu">
          <a href="/mo7adaraty/student/notifications.php">
            <i class="fa-solid fa-bell"></i>
            <?php if ($unread_notifications_count > 0): ?>
              <span class="notification-count"><?php echo $unread_notifications_count; ?></span>
            <?php endif; ?>
          </a>
          <div class="notifications-dropdown">
            <?php if (!empty($notifications)): ?>
              <?php foreach ($notifications as $n): ?>
                <a class="notification-item" href="<?php echo '/mo7adaraty/' . htmlspecialchars($n['link']); ?>">
                  <i class="fa-regular fa-envelope"></i>
                  <span><?php echo htmlspecialchars($n['message']); ?></span>
                  <small class="time"><?php echo date("d/m H:i", strtotime($n['created_at'])); ?></small>
                </a>
              <?php endforeach; ?>
              <a class="view-all" href="/mo7adaraty/student/notifications.php">عرض كل الإشعارات</a>
            <?php else: ?>
              <p>لا توجد إشعارات جديدة.</p>
            <?php endif; ?>
          </div>
        </li>
        
        <li><a href="/mo7adaraty/logout.php"><i class="fa-solid fa-right-from-bracket"></i> خروج</a></li>
      <?php else: ?>
        <li><a href="/mo7adaraty/login.php">دخول</a></li>
        <li><a href="/mo7adaraty/signup.php">تسجيل</a></li>
      <?php endif; ?>
    </ul>
  </nav>

  <button class="menu-toggle" id="menuToggle"><i class="fa-solid fa-bars"></i></button>
</header>










<style>
:root {
  --brand:#1a73e8;
  --radius:14px;
}

.main-header {
  display: flex; justify-content: space-between; align-items: center;
  background: #fff;
  padding: 12px 24px;
  border-bottom: 1px solid #e5e7eb;
}
.logo a { font-size: 22px; font-weight: 700; color: var(--brand); text-decoration: none; }
.main-nav ul { list-style: none; margin: 0; padding: 0; display: flex; align-items: center; gap:14px; }
.main-nav li a {
  padding: 8px 12px; text-decoration: none; color: #334155; font-weight: 600;
  border-radius: var(--radius); transition: background .2s, color .2s;
}
.main-nav li a:hover { background: #f1f5f9; color: var(--brand); }
.notifications-menu { position: relative; }
.notification-count {
  background: #f87171; color: white; border-radius: 9999px;
  padding: 2px 6px; font-size: 12px; position: absolute; top: -4px; right: -6px;
}
.notifications-dropdown {
  display: none; position: absolute; top: 110%; 
  left: 0; /* تم التعديل هنا */
  background: #fff; border-radius: var(--radius); box-shadow: 0 6px 16px rgba(0,0,0,0.1);
  min-width: 300px; max-height: 280px; overflow-y: auto;
  z-index: 1001;
}
.notifications-menu:hover .notifications-dropdown { display: block; }
.notification-item, .view-all {
  display: flex; justify-content: space-between; align-items:center;
  padding: 10px 15px; border-bottom: 1px solid #eee; text-decoration: none; color: #333;
}
.notification-item:hover { background: #f3f6fb; }
.notification-item span { flex-grow: 1; margin: 0 10px; }
.notification-item .time { font-size: 11px; color: #777; white-space: nowrap; }
.view-all { text-align: center; font-weight: bold; justify-content: center; }
.notifications-dropdown p { padding: 10px; text-align: center; color: #888; }
.menu-toggle { display:none; background:none; border:none; font-size:20px; cursor:pointer; color: var(--brand); }

@media(max-width:768px){
  .main-nav{
    display: none; /* مخفية بشكل افتراضي */
    position:absolute; top:70px; right:10px; background:#fff; border-radius:var(--radius);
    box-shadow:0 4px 12px rgba(0,0,0,.2); width:200px;
    border: 1px solid #e5e7eb;
    opacity: 0;
    transform: translateY(-10px);
    transition: opacity 0.2s ease, transform 0.2s ease;
  }
  .main-nav.open { 
    display: block; 
    opacity: 1;
    transform: translateY(0);
  }
  .main-nav ul{flex-direction:column; align-items:flex-start; padding: 5px;}
  .main-nav li{width: 100%;}
  .main-nav li a{color:#333; width:100%; box-sizing: border-box;}
  
  /* === [   التعديل الجديد هنا   ] === */
  .notifications-dropdown {
      left: auto; /* إلغاء التموضع من اليسار */
      right: 0; /* التموضع من اليمين في الهاتف */
  }
  /* =============================== */
  .menu-toggle{display:block;}
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const nav = document.getElementById('mainNav');

    if(menuToggle) {
        menuToggle.addEventListener('click', function(event) {
            event.stopPropagation(); // منع انتشار الحدث
            nav.classList.toggle('open');
        });
    }
    // إخفاء القائمة عند الضغط في أي مكان آخر
    document.addEventListener('click', function(event) {
        if (nav && !nav.contains(event.target) && !menuToggle.contains(event.target)) {
            nav.classList.remove('open');
        }
    });
});
</script>
