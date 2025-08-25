<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة والتأكد من أن المستخدم هو أدمن
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

try {
    // 1. استعلام شامل لجلب كل المقررات الدراسية مع كامل تفاصيلها الهرمية
    $stmt = $pdo->query("
        SELECT 
            s.subject_id, s.subject_name,
            gs.group_subject_id,
            g.group_name,
            p.professor_name,
            c.class_name,
            t.track_name,
            d.division_name
        FROM group_subjects gs
        JOIN subjects s ON gs.subject_id = s.subject_id
        JOIN `groups` g ON gs.group_id = g.group_id
        JOIN professors p ON gs.professor_id = p.professor_id
        JOIN classes c ON g.class_id = c.class_id
        JOIN tracks t ON c.track_id = t.track_id
        JOIN divisions d ON t.division_id = d.division_id
        ORDER BY d.division_name, t.track_name, c.class_name, s.subject_name
    ");
    $all_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. إعادة هيكلة البيانات للعرض بشكل هرمي
    $structured_assignments = [];
    foreach ($all_assignments as $assignment) {
        $structured_assignments[$assignment['division_name']][$assignment['track_name']][$assignment['class_name']][] = $assignment;
    }

} catch (PDOException $e) { 
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض المواد حسب الهيكل الدراسي</title>
    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require_once '../templates/header.php'; ?>
<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-book"></i> عرض المواد حسب الهيكل الدراسي</h2>
        <p>هذه الصفحة تعرض جميع المواد المسندة للأفواج والأساتذة ضمن الهيكل الدراسي الكامل.</p>
        
        <div class="table-section">
            <?php if (empty($structured_assignments)): ?>
                <p>لا توجد مواد مسندة للأفواج حالياً. يرجى إضافة مقررات من صفحة "إدارة المقررات".</p>
            <?php else: ?>
                <?php foreach ($structured_assignments as $division_name => $tracks): ?>
                    <div class="division-group" style="margin-bottom: 25px; padding: 20px; background: #fff; border-radius: 8px; border: 1px solid #e9ecef;">
                        <h3 class="division-title" style="font-size: 22px; color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-top: 0;">
                            الشعبة: <?php echo htmlspecialchars($division_name); ?>
                        </h3>
                        <?php foreach ($tracks as $track_name => $classes): ?>
                            <div class="track-group" style="margin-top: 20px; padding-right: 15px; border-right: 3px solid #f1f1f1;">
                                <h4 class="track-title" style="font-size: 18px; margin-bottom: 15px;">
                                    المسار: <?php echo htmlspecialchars($track_name); ?>
                                </h4>
                                <?php foreach ($classes as $class_name => $assignments_in_class): ?>
                                    <h5 style="margin-bottom: 10px; font-size: 16px;">الفصل الدراسي: <?php echo htmlspecialchars($class_name); ?></h5>
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>المادة</th>
                                                <th>الفوج</th>
                                                <th>الأستاذ المسؤول</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($assignments_in_class as $assignment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
                                                <td><?php echo htmlspecialchars($assignment['group_name']); ?></td>
                                                <td><?php echo htmlspecialchars($assignment['professor_name']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>