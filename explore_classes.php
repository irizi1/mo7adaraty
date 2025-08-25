<?php
session_start();
require_once 'config/db_connexion.php';

// حماية الصفحة، يجب أن يكون المستخدم طالباً مسجلاً
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

try {
    // 1. جلب جميع الفصول منظمة حسب الشعب والمسارات
    $stmt_all_classes = $pdo->query("
        SELECT 
            d.division_id, d.division_name,
            t.track_id, t.track_name,
            c.class_id, c.class_name,
            p.professor_name
        FROM divisions d
        JOIN tracks t ON d.division_id = t.division_id
        JOIN classes c ON t.track_id = c.track_id
        JOIN professors p ON c.professor_id = p.professor_id
        ORDER BY d.division_name, t.track_name, c.class_name
    ");
    $all_content = $stmt_all_classes->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

    // 2. جلب ID الفصول التي سجل بها الطالب حالياً
    $stmt_enrolled = $pdo->prepare("
        SELECT g.class_id 
        FROM student_enrollments se
        JOIN `groups` g ON se.group_id = g.group_id
        WHERE se.user_id = ?
    ");
    $stmt_enrolled->execute([$user_id]);
    // تحويل النتيجة إلى مصفوفة بسيطة لسهولة البحث
    $enrolled_class_ids = $stmt_enrolled->fetchAll(PDO::FETCH_COLUMN, 0);

} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استكشاف الفصول - Mo7adaraty</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* تنسيقات خاصة بصفحة استكشاف الفصول */
        .page-container { max-width: 1000px; margin: 20px auto; padding: 20px; }
        .division-block { margin-bottom: 25px; background: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .division-header { background-color: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid #dee2e6; cursor: pointer; }
        .division-header h2 { margin: 0; font-size: 22px; }
        .tracks-container { padding: 0 20px; max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; }
        .track-block { margin: 15px 0; }
        .track-block h3 { border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .class-list-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #eee; }
        .class-list-item:last-child { border-bottom: none; }
        .enroll-btn { background-color: #28a745; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; border: none; cursor: pointer;}
        .enrolled-label { background-color: #6c757d; color: white; padding: 8px 15px; border-radius: 5px; font-weight: bold; }
    </style>
</head>
<body>

<?php require_once 'templates/header.php'; ?>

<div class="page-container">
    <h1>استكشاف الفصول الدراسية</h1>
    <p>تصفح جميع الفصول المتاحة في المنصة وسجل في الفصول التي تهمك.</p>

    <?php foreach ($all_content as $division_name => $tracks): ?>
        <div class="division-block">
            <div class="division-header" onclick="toggleTracks(this)">
                <h2><?php echo htmlspecialchars($division_name); ?></h2>
            </div>
            <div class="tracks-container">
                <?php $grouped_tracks = []; foreach ($tracks as $item) { $grouped_tracks[$item['track_name']][] = $item; } ?>
                <?php foreach ($grouped_tracks as $track_name => $classes): ?>
                    <div class="track-block">
                        <h3><?php echo htmlspecialchars($track_name); ?></h3>
                        <div class="class-list">
                            <?php foreach ($classes as $class): ?>
                                <div class="class-list-item">
                                    <div>
                                        <strong><?php echo htmlspecialchars($class['class_name']); ?></strong><br>
                                        <small>الأستاذ: <?php echo htmlspecialchars($class['professor_name']); ?></small>
                                    </div>
                                    <div>
                                        <?php if (in_array($class['class_id'], $enrolled_class_ids)): ?>
                                            <span class="enrolled-label">تم التسجيل</span>
                                        <?php else: ?>
                                            <form action="enroll_process.php" method="POST">
                                                <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
                                                <button type="submit" class="enroll-btn">التسجيل في الفصل</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

</div>

<script>
function toggleTracks(header) {
    var content = header.nextElementSibling;
    if (content.style.maxHeight) {
        content.style.maxHeight = null;
    } else {
        content.style.maxHeight = content.scrollHeight + "px";
    } 
}
</script>

</body>
</html>