<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

try {
    // [تم التحديث هنا] - استعلام جديد لجلب المحاضرات المعلقة بناءً على الهيكل الجديد
    $stmt = $pdo->query("
        SELECT 
            l.lecture_id, l.title, l.description, l.file_path, l.upload_date,
            u.username as uploader_name,
            s.subject_name,
            c.class_name,
            g.group_name,
            t.track_name,
            d.division_name
        FROM lectures l
        JOIN users u ON l.uploader_user_id = u.user_id
        JOIN offering_subjects os ON l.offering_subject_id = os.offering_subject_id
        JOIN subjects s ON os.subject_id = s.subject_id
        JOIN course_offerings co ON os.offering_id = co.offering_id
        JOIN divisions d ON co.division_id = d.division_id
        JOIN classes c ON co.class_id = c.class_id
        JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        WHERE l.status = 'pending'
        ORDER BY l.upload_date ASC
    ");
    $pending_lectures = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) { 
    die("خطأ في جلب البيانات: " . $e->getMessage()); 
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>مراجعة المحاضرات - لوحة التحكم</title>
               <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">

    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require_once '../templates/header.php'; ?>
<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-hourglass-half"></i> مراجعة المحاضرات المعلقة</h2>

        <?php
        if (isset($_SESSION['review_status'])) {
            $status_type = $_SESSION['review_status']['type'];
            $message = $_SESSION['review_status']['message'];
            echo '<p class="status-message ' . $status_type . '">' . htmlspecialchars($message) . '</p>';
            unset($_SESSION['review_status']);
        }
        ?>

        <div class="table-section">
            <h3>المحاضرات التي تنتظر الموافقة (<?php echo count($pending_lectures); ?>)</h3>
            <?php if (empty($pending_lectures)): ?>
                <p>لا توجد محاضرات معلقة للمراجعة حالياً.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>عنوان المحاضرة</th>
                            <th>الطالب</th>
                            <th>المقرر الدراسي الكامل</th>
                            <th>تحميل للمراجعة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_lectures as $lecture): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($lecture['title']); ?></td>
                            <td><?php echo htmlspecialchars($lecture['uploader_name']); ?></td>
                            <td><?php echo htmlspecialchars($lecture['division_name'] . ' > ' . $lecture['class_name'] . ' / ' . $lecture['group_name'] . ($lecture['track_name'] ? ' / ' . $lecture['track_name'] : '')); ?></td>
                            <td>
                                <a href="../../<?php echo htmlspecialchars($lecture['file_path']); ?>" target="_blank" class="action-btn" style="background-color: #17a2b8;">
                                    <i class="fa-solid fa-eye"></i> عرض
                                </a>
                            </td>
                            <td>
                                <form action="process_lecture.php" method="POST" style="display: inline-block;">
                                    <input type="hidden" name="lecture_id" value="<?php echo $lecture['lecture_id']; ?>">
                                    <button type="submit" name="action" value="approve" class="action-btn" style="background-color: #28a745;">
                                        <i class="fa-solid fa-check"></i> قبول
                                    </button>
                                </form>
                                <form action="process_lecture.php" method="POST" style="display: inline-block;">
                                    <input type="hidden" name="lecture_id" value="<?php echo $lecture['lecture_id']; ?>">
                                    <input type="text" name="rejection_reason" placeholder="سبب الرفض (إلزامي للرفض)" required style="width: 150px; padding: 5px;">
                                    <button type="submit" name="action" value="reject" class="action-btn btn-delete">
                                        <i class="fa-solid fa-times"></i> رفض
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>