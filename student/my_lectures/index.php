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
    // [تم التحديث] - استعلام جديد لجلب المحاضرات المعلقة مع تفاصيلها الكاملة
    $stmt = $pdo->prepare("
        SELECT 
            l.lecture_id, l.title, l.description, l.upload_date,
            s.subject_name,
            c.class_name,
            g.group_name,
            t.track_name
        FROM lectures l
        JOIN offering_subjects os ON l.offering_subject_id = os.offering_subject_id
        JOIN subjects s ON os.subject_id = s.subject_id
        JOIN course_offerings co ON l.offering_id = co.offering_id
        JOIN classes c ON co.class_id = c.class_id
        JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        WHERE l.uploader_user_id = ? AND l.status = 'pending'
        ORDER BY l.upload_date DESC
    ");
    $stmt->execute([$user_id]);
    $pending_lectures = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
  
    <title>محاضراتي قيد المراجعة - Mo7adaraty</title>
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
        .lecture-card {
            background: var(--card-bg); border-radius: 10px; margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden;
            transition: box-shadow 0.3s ease;
        }
        .lecture-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .card-body { padding: 20px; }
        .card-body h3 { margin: 0 0 10px; font-size: 18px; color: var(--brand-color); }
        .card-body p { margin: 0 0 15px; color: var(--text-secondary); }
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
    <h1><i class="fa-solid fa-hourglass-half"></i> محاضراتي قيد المراجعة</h1>
    <p style="text-align:center;"><a href="../profil.php">&larr; العودة إلى الملف الشخصي</a></p>

    <?php if (isset($_SESSION['lecture_action_status'])): ?>
        <p class="status-message <?php echo $_SESSION['lecture_action_status']['type']; ?>">
            <?php echo htmlspecialchars($_SESSION['lecture_action_status']['message']); ?>
        </p>
        <?php unset($_SESSION['lecture_action_status']); ?>
    <?php endif; ?>

    <?php if (empty($pending_lectures)): ?>
        <p class="no-data">لا توجد لديك محاضرات قيد المراجعة حالياً.</p>
    <?php else: ?>
        <?php foreach ($pending_lectures as $lecture): ?>
            <div class="lecture-card">
                <div class="card-body">
                    <h3><?php echo htmlspecialchars($lecture['title']); ?></h3>
                    <p>
                        <strong>المادة:</strong> <?php echo htmlspecialchars($lecture['subject_name'] ?? 'غير محدد'); ?><br>
                        <strong>المقرر:</strong> <?php echo htmlspecialchars($lecture['class_name'] . ' / ' . $lecture['group_name'] . ($lecture['track_name'] ? ' / ' . $lecture['track_name'] : '')); ?><br>
                        <strong>تاريخ الرفع:</strong> <?php echo date('Y-m-d', strtotime($lecture['upload_date'])); ?>
                    </p>
                    <?php if(!empty($lecture['description'])): ?>
                        <p><strong>الوصف:</strong> <?php echo nl2br(htmlspecialchars($lecture['description'])); ?></p>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="edit.php?id=<?php echo $lecture['lecture_id']; ?>" class="action-btn edit-btn">
                        <i class="fa-solid fa-pen"></i> تعديل
                    </a>
                    <form action="delete_process.php" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه المحاضرة؟');" style="display: inline;">
                        <input type="hidden" name="lecture_id" value="<?php echo $lecture['lecture_id']; ?>">
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