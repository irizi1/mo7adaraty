<?php
session_start();
require_once '../config/db_connexion.php';

// حماية الصفحة والتأكد من أن المستخدم هو أدمن
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=ليس لديك صلاحية الوصول لهذه الصفحة");
    exit();
}

try {
    // --- الإحصائيات الخاصة بالمهام العاجلة ---
    $pending_lectures_count = $pdo->query("SELECT COUNT(*) FROM lectures WHERE status = 'pending'")->fetchColumn();
    $pending_enrollment_requests_count = $pdo->query("SELECT COUNT(*) FROM enrollment_change_requests WHERE status = 'pending'")->fetchColumn();
    $pending_messages_count = $pdo->query("SELECT COUNT(*) FROM admin_messages WHERE status = 'pending_reply'")->fetchColumn();
    $pending_posts_count = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'pending'")->fetchColumn();
    $pending_reports_count = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();

    // --- [تم التحديث] إحصاءات عامة للموقع ---
    $total_students_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $total_professors_count = $pdo->query("SELECT COUNT(*) FROM professors")->fetchColumn();
    $total_lectures_count = $pdo->query("SELECT COUNT(*) FROM lectures WHERE status = 'approved'")->fetchColumn();
    $total_exams_count = $pdo->query("SELECT COUNT(*) FROM exams")->fetchColumn();


    // --- إحصاءات مفصلة حسب الشعبة ---
    $stmt = $pdo->query("
        SELECT 
            d.division_name,
            d.division_id,
            (SELECT COUNT(DISTINCT se.user_id)
             FROM course_offerings co_s
             JOIN student_enrollments se ON co_s.offering_id = se.offering_id
             WHERE co_s.division_id = d.division_id) as student_count,
            
            (SELECT COUNT(DISTINCT l.lecture_id)
             FROM lectures l
             JOIN offering_subjects os ON l.offering_subject_id = os.offering_subject_id
             JOIN course_offerings co_l ON os.offering_id = co_l.offering_id
             WHERE co_l.division_id = d.division_id AND l.status = 'approved') as lecture_count,

            (SELECT COUNT(*)
             FROM course_offerings co_o
             WHERE co_o.division_id = d.division_id) as offering_count
        FROM divisions d
        GROUP BY d.division_id, d.division_name
        ORDER BY d.division_name
    ");
    $division_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("خطأ في جلب الإحصائيات: " . $e->getMessage());
    die("خطأ في جلب الإحصائيات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - Mo7adaraty</title>
                <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">

    <link rel="stylesheet" href="../assets/css/admin_style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .card a { text-decoration: none; color: inherit; display: block; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .card { transition: all 0.3s ease; }
        h3 { font-size: 20px; color: #343a40; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #e9ecef;}
    </style>
</head>
<body>
<?php require_once 'templates/header.php'; ?>
<div class="admin-container">
    <?php require_once 'templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-chart-line"></i> لوحة التحكم الرئيسية</h2>
        
        <h3><i class="fa-solid fa-circle-exclamation"></i> المهام العاجلة</h3>
        <div class="stats-cards">
            <div class="card" style="border-left-color: #f59e0b;"><a href="reviews/index.php"><h3><i class="fa-solid fa-hourglass-half"></i> محاضرات للمراجعة</h3><p class="stat-number"><?php echo $pending_lectures_count; ?></p></a></div>
            <div class="card" style="border-left-color: #ef4444;"><a href="post_reviews/index.php"><h3><i class="fa-solid fa-check-to-slot"></i> مشاركات للمراجعة</h3><p class="stat-number"><?php echo $pending_posts_count; ?></p></a></div>
            <div class="card" style="border-left-color: #3b82f6;"><a href="enrollment_requests/index.php"><h3><i class="fa-solid fa-exchange-alt"></i> طلبات تغيير المقرر</h3><p class="stat-number"><?php echo $pending_enrollment_requests_count; ?></p></a></div>
            <div class="card" style="border-left-color: #10b981;"><a href="messages/index.php"><h3><i class="fa-solid fa-inbox"></i> الرسائل الجديدة</h3><p class="stat-number"><?php echo $pending_messages_count; ?></p></a></div>
            <div class="card" style="border-left-color: #6366f1;"><a href="reports/index.php"><h3><i class="fa-solid fa-flag"></i> البلاغات الجديدة</h3><p class="stat-number"><?php echo $pending_reports_count; ?></p></a></div>
        </div>

        <h3><i class="fa-solid fa-globe"></i> إحصاءات عامة</h3>
        <div class="stats-cards">
            <div class="card" style="border-left-color: #14b8a6;"><a href="users/index.php"><h3><i class="fa-solid fa-users"></i> إجمالي الطلاب</h3><p class="stat-number"><?php echo $total_students_count; ?></p></a></div>
            <div class="card" style="border-left-color: #8b5cf6;"><a href="professors/index.php"><h3><i class="fa-solid fa-user-tie"></i> إجمالي الأساتذة</h3><p class="stat-number"><?php echo $total_professors_count; ?></p></a></div>
            <div class="card" style="border-left-color: #d946ef;"><a href="lectures/index.php"><h3><i class="fa-solid fa-book-open"></i> المحاضرات المنشورة</h3><p class="stat-number"><?php echo $total_lectures_count; ?></p></a></div>
            <div class="card" style="border-left-color: #f97316;"><a href="exams/index.php"><h3><i class="fa-solid fa-file-signature"></i> الامتحانات المرفوعة</h3><p class="stat-number"><?php echo $total_exams_count; ?></p></a></div>
        </div>

        <h3><i class="fa-solid fa-sitemap"></i> إحصاءات حسب الشعبة</h3>
        <div class="table-section">
            <?php if (empty($division_stats)): ?>
                <p>لا توجد بيانات لعرضها. قم بإضافة شعب دراسية ومقررات أولاً.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>الشعبة الدراسية</th>
                            <th><i class="fa-solid fa-users"></i> عدد الطلاب</th>
                            <th><i class="fa-solid fa-file-alt"></i> المحاضرات المنشورة</th>
                            <th><i class="fa-solid fa-layer-group"></i> المقررات المتاحة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($division_stats as $stats): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($stats['division_name']); ?></strong></td>
                                <td><?php echo $stats['student_count']; ?></td>
                                <td><?php echo $stats['lecture_count']; ?></td>
                                <td><?php echo $stats['offering_count']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../assets/js/admin_script.js"></script>
</body>
</html>