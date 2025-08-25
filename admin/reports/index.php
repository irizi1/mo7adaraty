<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$status_message = '';
$error_message = '';

// معالجة تغيير حالة البلاغ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id'])) {
    $report_id = $_POST['report_id'];
    $action = $_POST['action'];
    $new_status = in_array($action, ['resolve', 'dismiss']) ? ($action === 'resolve' ? 'resolved' : 'dismissed') : '';

    if ($new_status) {
        try {
            $stmt = $pdo->prepare("UPDATE reports SET status = ? WHERE report_id = ?");
            $stmt->execute([$new_status, $report_id]);
            $status_message = "تم تحديث حالة البلاغ بنجاح!";
        } catch (PDOException $e) { $error_message = "حدث خطأ أثناء تحديث البلاغ."; }
    }
}

// [تم التحديث] - استعلام شامل لدعم بلاغات المنشورات
try {
    $stmt = $pdo->query("
        SELECT 
            r.*, 
            reporter.username as reporter_username,
            COALESCE(l.title, e.title, SUBSTRING(p.post_text, 1, 50), SUBSTRING(c.comment_text, 1, 50)) as content_summary,
            author.username as author_username,
            CASE
                WHEN r.content_type = 'lecture' THEN CONCAT('../../student/class_lectures.php?id=', l.offering_id, '#lecture-', r.content_id)
                WHEN r.content_type = 'exam' THEN CONCAT('../../student/class_exams.php?id=', os_e.offering_id, '#exam-', r.content_id)
                WHEN r.content_type = 'post' THEN CONCAT('../../student/community/index.php#post-', r.content_id)
                WHEN r.content_type = 'comment' AND c.content_type = 'lecture' THEN CONCAT('../../student/class_lectures.php?id=', cl.offering_id, '#lecture-', c.content_id)
                WHEN r.content_type = 'comment' AND c.content_type = 'exam' THEN CONCAT('../../student/class_exams.php?id=', os_ce.offering_id, '#exam-', c.content_id)
                WHEN r.content_type = 'comment' AND c.content_type = 'post' THEN CONCAT('../../student/community/index.php#post-', c.content_id)
                ELSE '#'
            END as content_link
        FROM reports r
        JOIN users reporter ON r.reporter_user_id = reporter.user_id
        LEFT JOIN lectures l ON r.content_type = 'lecture' AND r.content_id = l.lecture_id
        LEFT JOIN exams e ON r.content_type = 'exam' AND r.content_id = e.exam_id
        LEFT JOIN posts p ON r.content_type = 'post' AND r.content_id = p.post_id
        LEFT JOIN comments c ON r.content_type = 'comment' AND r.content_id = c.comment_id
        LEFT JOIN users author ON author.user_id = COALESCE(l.uploader_user_id, e.uploader_user_id, p.user_id, c.user_id)
        
        LEFT JOIN offering_subjects os_e ON e.offering_subject_id = os_e.offering_subject_id
        LEFT JOIN lectures cl ON c.content_type = 'lecture' AND c.content_id = cl.lecture_id
        LEFT JOIN exams ce ON c.content_type = 'exam' AND c.content_id = ce.exam_id
        LEFT JOIN offering_subjects os_ce ON ce.offering_subject_id = os_ce.offering_subject_id

        ORDER BY FIELD(r.status, 'pending', 'resolved', 'dismissed'), r.created_at DESC
    ");
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("خطأ في جلب البلاغات: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
               <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">

<title>إدارة البلاغات - لوحة التحكم</title>
    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require_once '../templates/header.php'; ?>
<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>

    <main class="admin-main-content">
        <h2><i class="fa-solid fa-flag"></i> إدارة البلاغات</h2>
        
        <?php
        if ($status_message) echo '<p class="status-message success">' . $status_message . '</p>';
        if (isset($_GET['status']) && $_GET['status'] == 'deleted') echo '<p class="status-message success">تم حذف البلاغ بنجاح!</p>';
        if ($error_message) echo '<p class="status-message error">' . $error_message . '</p>';
        ?>

        <div class="table-section">
            <h3>قائمة البلاغات</h3>
            <table>
                <thead>
                    <tr>
                        <th>المُبلّغ</th>
                        <th>المحتوى المُبلغ عنه</th>
                        <th>صاحب المحتوى</th>
                        <th>السبب</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($reports) > 0): ?>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($report['reporter_username']); ?></td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($report['content_link']); ?>" target="_blank" title="عرض المحتوى">
                                        <?php echo htmlspecialchars($report['content_summary']); ?> <i class="fa-solid fa-external-link-alt"></i>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($report['author_username'] ?? 'غير معروف'); ?></td>
                                <td style="white-space: pre-wrap;"><?php echo htmlspecialchars($report['reason']); ?></td>
                                <td><?php echo htmlspecialchars($report['status']); ?></td>
                                <td>
                                    <?php if ($report['status'] === 'pending'): ?>
                                        <form action="index.php" method="POST" style="display: inline-block;">
                                            <input type="hidden" name="report_id" value="<?php echo $report['report_id']; ?>">
                                            <button type="submit" name="action" value="resolve" class="action-btn" style="background-color:#28a745;" title="الموافقة على البلاغ وحله">حل</button>
                                            <button type="submit" name="action" value="dismiss" class="action-btn" style="background-color:#6c757d;" title="رفض البلاغ">رفض</button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="delete_process.php?id=<?php echo $report['report_id']; ?>" class="action-btn btn-delete" onclick="return confirm('هل أنت متأكد من حذف هذا البلاغ نهائياً؟');" title="حذف البلاغ"><i class="fa-solid fa-trash-can"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">لا توجد أي بلاغات حالياً.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>