<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

try {
    // [تم التحديث هنا] - استعلام جديد لجلب الرسائل مع معلومات تسجيل الطالب الصحيحة
    $stmt = $pdo->query("
        SELECT 
            m.message_id, m.student_user_id, m.message_subject, m.message_content, 
            m.admin_reply, m.status, m.created_at, m.replied_at,
            u.username,
            (
                SELECT GROUP_CONCAT(
                    DISTINCT CONCAT(d.division_name, ' > ', c.class_name, ' / ', g.group_name, IF(t.track_name IS NOT NULL, CONCAT(' / ', t.track_name), '')) 
                    SEPARATOR '<br>'
                )
                FROM student_enrollments se
                JOIN course_offerings co ON se.offering_id = co.offering_id
                JOIN divisions d ON co.division_id = d.division_id
                JOIN classes c ON co.class_id = c.class_id
                JOIN `groups` g ON co.group_id = g.group_id
                LEFT JOIN tracks t ON co.track_id = t.track_id
                WHERE se.user_id = m.student_user_id
            ) as enrollment_info
        FROM admin_messages m
        JOIN users u ON m.student_user_id = u.user_id
        ORDER BY m.status ASC, m.created_at DESC
    ");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في جلب الرسائل: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الرسائل الواردة - لوحة التحكم</title>
                <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">
    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .message-card { background: #fff; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); border: 1px solid #e9ecef; }
        .message-header { padding: 15px 20px; border-bottom: 1px solid #e9ecef; display: flex; justify-content: space-between; align-items: center; }
        .message-header h4 { margin: 0; font-size: 18px; color: #007bff; }
        .message-status { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-pending { background-color: #fffbeb; color: #b45309; }
        .status-replied { background-color: #f0fdf4; color: #15803d; }
        .message-body { padding: 20px; }
        .student-info { background-color: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 20px; border: 1px solid #e9ecef; }
        .student-info p { margin: 0 0 8px 0; }
        .student-info strong { color: #343a40; }
        .admin-reply-form { margin-top: 20px; border-top: 1px dashed #ced4da; padding-top: 20px; }
    </style>
</head>
<body>
<?php require_once '../templates/header.php'; ?>
<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-inbox"></i> الرسائل الواردة</h2>

        <?php if (isset($_SESSION['reply_status'])): ?>
            <p class="status-message success"><?php echo htmlspecialchars($_SESSION['reply_status']); ?></p>
            <?php unset($_SESSION['reply_status']); ?>
        <?php endif; ?>

        <?php if (empty($messages)): ?>
            <p>لا توجد رسائل واردة حالياً.</p>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <div class="message-card">
                    <div class="message-header">
                        <h4><?php echo htmlspecialchars($msg['message_subject']); ?></h4>
                        <?php if ($msg['status'] === 'replied'): ?>
                            <span class="message-status status-replied"><i class="fa-solid fa-check-circle"></i> تم الرد</span>
                        <?php else: ?>
                            <span class="message-status status-pending"><i class="fa-solid fa-clock"></i> بانتظار الرد</span>
                        <?php endif; ?>
                    </div>
                    <div class="message-body">
                        <div class="student-info">
                            <p><strong><i class="fa-solid fa-user"></i> الطالب:</strong> <?php echo htmlspecialchars($msg['username']); ?></p>
                            <p><strong><i class="fa-solid fa-graduation-cap"></i> تسجيلاته الدراسية:</strong><br><?php echo $msg['enrollment_info'] ?: '<em>غير مسجل في أي مقرر.</em>'; ?></p>
                            <p><strong><i class="fa-solid fa-calendar-alt"></i> تاريخ الإرسال:</strong> <?php echo date('Y-m-d H:i', strtotime($msg['created_at'])); ?></p>
                        </div>

                        <div class="message-content">
                            <p><strong>محتوى الرسالة:</strong></p>
                            <blockquote><?php echo nl2br(htmlspecialchars($msg['message_content'])); ?></blockquote>
                        </div>

                        <?php if ($msg['status'] === 'replied'): ?>
                            <div style="background: #f0fdf4; border-right: 4px solid #28a745; padding: 15px; border-radius: 8px; margin-top: 15px;">
                                <p><strong><i class="fa-solid fa-user-shield"></i> ردك:</strong><br><?php echo nl2br(htmlspecialchars($msg['admin_reply'])); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="admin-reply-form">
                                <form action="process.php" method="POST">
                                    <input type="hidden" name="message_id" value="<?php echo $msg['message_id']; ?>">
                                    <input type="hidden" name="student_user_id" value="<?php echo $msg['student_user_id']; ?>">
                                    <div class="form-group">
                                        <label for="admin_reply_<?php echo $msg['message_id']; ?>"><strong>كتابة رد:</strong></label>
                                        <textarea id="admin_reply_<?php echo $msg['message_id']; ?>" name="admin_reply" rows="3" required></textarea>
                                    </div>
                                    <button type="submit"><i class="fa-solid fa-paper-plane"></i> إرسال الرد</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>
</body>
</html>