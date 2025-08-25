<?php
// الحصول على رابط الصفحة الحالية لتحديد أي رابط يجب تمييزه
$current_page = $_SERVER['REQUEST_URI'];
?>
<aside class="admin-sidebar">
    <nav>
        <ul>
            <li <?php if (strpos($current_page, '/admin/index.php') !== false || substr($current_page, -7) === '/admin/') echo 'class="active"'; ?>>
                <a href="/mo7adaraty/admin/"><i class="fa-solid fa-house"></i> الرئيسية</a>
            </li>

            <li <?php if (strpos($current_page, '/admin/lectures/') !== false) echo 'class="active"'; ?>>
                <a href="/mo7adaraty/admin/lectures/"><i class="fa-solid fa-file-import"></i> إدارة المحاضرات</a>
            </li>
            <li <?php if (strpos($current_page, '/admin/exams/') !== false) echo 'class="active"'; ?>>
                <a href="/mo7adaraty/admin/exams/"><i class="fa-solid fa-file-pen"></i> إدارة الامتحانات</a>
            </li>
                <li <?php if (strpos($current_page, '/admin/post_reviews/') !== false) echo 'class="active"'; ?>>
                <a href="/mo7adaraty/admin/post_reviews/"><i class="fa-solid fa-check-to-slot"></i> مراجعة المشاركات</a>
            </li>
              <li <?php if (strpos($current_page, '/admin/whatsapp_groups/') !== false) echo 'class="active"'; ?>>
                <a href="/mo7adaraty/admin/whatsapp_groups/"><i class="fab fa-whatsapp"></i> مجموعات الواتساب</a>
            </li>
            <li <?php if (strpos($current_page, '/admin/divisions/') !== false) echo 'class="active"'; ?>>
                <a href="/mo7adaraty/admin/divisions/"><i class="fa-solid fa-sitemap"></i> إدارة الشعب</a>
            </li>
            <li <?php if (strpos($current_page, '/admin/tracks/') !== false) echo 'class="active"'; ?>>
                <a href="/mo7adaraty/admin/tracks/"><i class="fa-solid fa-route"></i> إدارة المسارات</a>
            </li>
            <li <?php if (strpos($current_page, '/admin/offerings/') !== false) echo 'class="active"'; ?>>
                <a href="/mo7adaraty/admin/offerings/"><i class="fa-solid fa-layer-group"></i> إدارة المقررات</a>
            </li>
            <li <?php if (strpos($current_page, '/admin/subjects/') !== false) echo 'class="active"'; ?>>
                <a href="/mo7adaraty/admin/subjects/"><i class="fa-solid fa-book"></i> إدارة المواد</a>
            </li>
            <li <?php if (strpos($current_page, '/admin/professors/') !== false) echo 'class="active"'; ?>>
                <a href="/mo7adaraty/admin/professors/"><i class="fa-solid fa-user-tie"></i> إدارة الأساتذة</a>
            </li>

            <li <?php if (strpos($current_page, '/admin/users/') !== false) echo 'class="active"'; ?>>
                <a href="/mo7adaraty/admin/users/"><i class="fa-solid fa-users"></i> إدارة المستخدمين</a>
            </li>
            <li <?php if (strpos($current_page, '/admin/reports/') !== false) echo 'class="active"'; ?>>
                <a href="/mo7adaraty/admin/reports/"><i class="fa-solid fa-flag"></i> إدارة البلاغات</a>
            </li>
            
            <li <?php if (strpos($current_page, 'publish_announcement.php') !== false) echo 'class="active"'; ?>>
                <a href="/mo7adaraty/admin/publish_announcement.php"><i class="fa-solid fa-bullhorn"></i> نشر إعلان عام</a>
            </li>
            <li <?php if (strpos($current_page, '/admin/reviews/') !== false) echo 'class="active"'; ?>>
                <a href="/mo7adaraty/admin/reviews/"><i class="fa-solid fa-hourglass-half"></i> مراجعة المحاضرات</a>
            </li>
            <li <?php if (strpos($current_page, '/admin/reset_requests/') !== false) echo 'class="active"'; ?>>
                <a href="/mo7adaraty/admin/reset_requests/"><i class="fa-solid fa-key"></i> طلبات إعادة التعيين</a>
            </li>
            <li <?php if (strpos($current_page, '/admin/enrollment_requests/') !== false) echo 'class="active"'; ?>>
                <a href="/mo7adaraty/admin/enrollment_requests/"><i class="fa-solid fa-exchange-alt"></i> طلبات تغيير المسار</a>
            </li>
            <li <?php if (strpos($current_page, '/admin/messages/') !== false) echo 'class="active"'; ?>>
                <a href="/mo7adaraty/admin/messages/"><i class="fa-solid fa-inbox"></i> الرسائل الواردة</a>
            </li>
        </ul>
    </nav>
</aside>