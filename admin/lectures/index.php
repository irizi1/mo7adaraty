<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

try {
    // [تم التحديث] - استعلام جديد لجلب المحاضرات مع اسم الأستاذ والشعبة للفرز
    $stmt = $pdo->query("
        SELECT 
            l.lecture_id, l.title,
            s.subject_name,
            p.professor_name,
            u.username as uploader_name,
            d.division_name,
            c.class_name,
            g.group_name,
            t.track_name
        FROM lectures l
        JOIN users u ON l.uploader_user_id = u.user_id
        JOIN offering_subjects os ON l.offering_subject_id = os.offering_subject_id
        JOIN subjects s ON os.subject_id = s.subject_id
        LEFT JOIN professors p ON os.professor_id = p.professor_id
        JOIN course_offerings co ON os.offering_id = co.offering_id
        JOIN divisions d ON co.division_id = d.division_id
        JOIN classes c ON co.class_id = c.class_id
        JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        WHERE l.status = 'approved'
        ORDER BY d.division_name, c.class_name, l.upload_date DESC
    ");
    $all_lectures = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // [تم التحديث] - إعادة هيكلة البيانات للعرض حسب الشعبة ثم الفصل
    $structured_lectures = [];
    foreach ($all_lectures as $lecture) {
        $division_key = $lecture['division_name'];
        $class_key = $lecture['class_name'];
        $structured_lectures[$division_key][$class_key][] = $lecture;
    }

    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name")->fetchAll();

} catch (PDOException $e) { 
    die("خطأ في جلب البيانات: " . $e->getMessage()); 
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
              <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">

    <title>إدارة المحاضرات</title>
    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require_once '../templates/header.php'; ?>
<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-file-import"></i> إدارة المحاضرات</h2>
        
        <div class="form-section">
            <h3>إضافة محاضرة جديدة</h3>
            <form action="add_process.php" method="POST" enctype="multipart/form-data" data-context="lectures">
                <div class="filter-form">
                    <div class="form-group">
                        <label for="division">الشعبة:</label>
                        <select id="division" name="division_id" required>
                            <option value="">-- اختر --</option>
                            <?php foreach ($divisions as $div): ?>
                                <option value="<?php echo $div['division_id']; ?>"><?php echo htmlspecialchars($div['division_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="offering">المقرر المتاح:</label>
                        <select id="offering" name="offering_id" required disabled></select>
                    </div>
                    <div class="form-group">
                        <label for="subject">المادة:</label>
                        <select id="subject" name="offering_subject_id" required disabled></select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="title">عنوان المحاضرة:</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="description">الوصف (اختياري):</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="lecture_file">ملف المحاضرة:</label>
                    <input type="file" name="lecture_file" required>
                </div>
                <button type="submit"><i class="fa-solid fa-plus"></i> إضافة المحاضرة</button>
            </form>
        </div>

        <div class="table-section">
            <h3>المحاضرات الحالية</h3>
            <?php if (empty($structured_lectures)): ?>
                <p>لا توجد محاضرات حالياً.</p>
            <?php else: ?>
                <?php foreach ($structured_lectures as $division_name => $classes): ?>
                    <h4 class="division-title" style="margin-top: 30px; border-bottom: 2px solid #007bff; padding-bottom: 5px;"><?php echo htmlspecialchars($division_name); ?></h4>
                    <?php foreach ($classes as $class_name => $lectures): ?>
                        <h5>الفصل: <?php echo htmlspecialchars($class_name); ?></h5>
                        <table>
                            <thead> 
                                <tr> 
                                    <th>عنوان المحاضرة</th>
                                    <th>المادة</th> 
                                    <th>الأستاذ</th>
                                    <th>المقرر (الفوج/المسار)</th>
                                    <th>الناشر</th> 
                                    <th>الإجراءات</th> 
                                </tr> 
                            </thead>
                            <tbody>
                                <?php foreach ($lectures as $lecture): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($lecture['title']); ?></td>
                                    <td><?php echo htmlspecialchars($lecture['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($lecture['professor_name'] ?? 'غير معين'); ?></td>
                                    <td><?php echo htmlspecialchars($lecture['group_name'] . ($lecture['track_name'] ? ' / ' . $lecture['track_name'] : '')); ?></td>
                                    <td><?php echo htmlspecialchars($lecture['uploader_name']); ?></td>
                                    <td>
                                        <a href="delete_process.php?id=<?php echo $lecture['lecture_id']; ?>" class="action-btn btn-delete" onclick="return confirm('هل أنت متأكد؟');">حذف</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="lectures_script.js"></script>
</body>
</html>