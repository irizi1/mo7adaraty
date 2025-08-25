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
    // جلب المقررات التي سجل بها الطالب مع روابط الواتساب المرتبطة بها
    $stmt = $pdo->prepare("
        SELECT 
            co.offering_id,
            c.class_name,
            g.group_name,
            t.track_name,
            wg.group_link,
            wg.group_name as whatsapp_group_name
        FROM student_enrollments se
        JOIN course_offerings co ON se.offering_id = co.offering_id
        JOIN classes c ON co.class_id = c.class_id
        JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        LEFT JOIN whatsapp_groups wg ON co.offering_id = wg.offering_id
        WHERE se.user_id = ?
        ORDER BY c.class_name, g.group_name
    ");
    $stmt->execute([$user_id]);
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>مجموعات الواتساب - Mo7adaraty</title>
             <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --brand-color: #25D366; --bg-color: #f4f8fc; --card-bg: #ffffff; --border-color: #e9ecef; }
        body { font-family: 'Tajawal', sans-serif; background-color: var(--bg-color); }
        .container { max-width: 900px; margin: 20px auto; padding: 20px; }
        h1 { text-align: center; color: #128C7E; }
        .group-card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            padding: 20px;
            transition: transform 0.2s;
        }
        .group-card:hover { transform: translateY(-5px); }
        .course-info { flex-grow: 1; }
        .course-info h3 { margin: 0 0 5px; font-size: 18px; }
        .course-info p { margin: 0; color: #6c757d; }
        .join-button {
            background-color: var(--brand-color);
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }
        .no-link { color: #aaa; }
    </style>
</head>
<body>
    <?php require_once '../../templates/header.php'; ?>

    <div class="container">
        <h1><i class="fab fa-whatsapp"></i> مجموعات الواتساب</h1>

        <?php if (empty($enrollments)): ?>
            <p style="text-align:center;">أنت غير مسجل في أي مقرر حالياً.</p>
        <?php else: ?>
            <?php foreach ($enrollments as $enrollment): ?>
                <div class="group-card">
                    <div class="course-info">
                        <h3><?php echo htmlspecialchars($enrollment['class_name'] . ' / ' . $enrollment['group_name'] . ($enrollment['track_name'] ? ' / ' . $enrollment['track_name'] : '')); ?></h3>
                        <p><?php echo htmlspecialchars($enrollment['whatsapp_group_name'] ?: 'مجموعة المقرر'); ?></p>
                    </div>
                    <div>
                        <?php if (!empty($enrollment['group_link'])): ?>
                            <a href="<?php echo htmlspecialchars($enrollment['group_link']); ?>" target="_blank" class="join-button">
                                <i class="fab fa-whatsapp"></i> الانضمام للمجموعة
                            </a>
                        <?php else: ?>
                            <span class="no-link">لا يوجد رابط متاح حالياً</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>