<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

try {
    // [تم التحديث] - استعلام جديد لجلب الامتحانات مع كل التفاصيل
    $stmt = $pdo->query("
        SELECT 
            e.exam_id, e.title, e.upload_date,
            s.subject_name,
            p.professor_name,
            u.username as uploader_name,
            d.division_name,
            c.class_name,
            g.group_name,
            t.track_name
        FROM exams e
        JOIN users u ON e.uploader_user_id = u.user_id
        JOIN offering_subjects os ON e.offering_subject_id = os.offering_subject_id
        JOIN subjects s ON os.subject_id = s.subject_id
        LEFT JOIN professors p ON os.professor_id = p.professor_id
        JOIN course_offerings co ON os.offering_id = co.offering_id
        JOIN divisions d ON co.division_id = d.division_id
        JOIN classes c ON co.class_id = c.class_id
        JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        ORDER BY d.division_name, c.class_name, e.upload_date DESC
    ");
    $all_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // [تم التحديث] - إعادة هيكلة البيانات للعرض حسب الشعبة والفصل
    $structured_exams = [];
    foreach($all_exams as $exam){
        $structured_exams[$exam['division_name']][$exam['class_name']][] = $exam;
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
               <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">
    <title>إدارة الامتحانات</title>
    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require_once '../templates/header.php'; ?>
<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-file-pen"></i> إدارة الامتحانات</h2>
        
        <?php if (isset($_GET['status'])): ?>
            <p class="status-message success">تمت العملية بنجاح.</p>
        <?php elseif (isset($_GET['error'])): ?>
            <p class="status-message error"><?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>

        <div class="form-section">
            <h3>إضافة امتحان جديد</h3>
            <form action="add_process.php" method="POST" enctype="multipart/form-data" data-context="exams">
                <div class="filter-form">
                    <div class="form-group">
                        <label>الشعبة:</label>
                        <select id="division" required>
                            <option value="">-- اختر --</option>
                            <?php foreach ($divisions as $div): ?>
                                <option value="<?php echo $div['division_id']; ?>"><?php echo htmlspecialchars($div['division_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>المقرر المتاح:</label>
                        <select id="offering" name="offering_id" required disabled></select>
                    </div>
                    <div class="form-group">
                        <label>المادة:</label>
                        <select id="subject" name="offering_subject_id" required disabled></select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="title">عنوان الامتحان:</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="description">الوصف (اختياري):</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="exam_file">ملف الامتحان:</label>
                    <input type="file" name="exam_file" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <button type="submit"><i class="fa-solid fa-plus"></i> إضافة الامتحان</button>
            </form>
        </div>

        <div class="table-section">
            <h3>الامتحانات الحالية</h3>
            <?php if (empty($structured_exams)): ?>
                <p>لا توجد امتحانات حالياً.</p>
            <?php else: ?>
                <?php foreach ($structured_exams as $division_name => $classes): ?>
                    <h4 class="division-title" style="margin-top: 30px; border-bottom: 2px solid #007bff; padding-bottom: 5px;"><?php echo htmlspecialchars($division_name); ?></h4>
                    <?php foreach ($classes as $class_name => $exams_in_class): ?>
                        <h5>الفصل: <?php echo htmlspecialchars($class_name); ?></h5>
                        <table>
                            <thead> 
                                <tr> 
                                    <th>العنوان</th>
                                    <th>المادة</th> 
                                    <th>الأستاذ</th>
                                    <th>المقرر (الفوج/المسار)</th> 
                                    <th>الناشر</th> 
                                    <th>الإجراءات</th> 
                                </tr> 
                            </thead>
                            <tbody>
                                <?php foreach ($exams_in_class as $exam): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['professor_name'] ?? 'غير معين'); ?></td>
                                    <td><?php echo htmlspecialchars($exam['group_name'] . ($exam['track_name'] ? ' / ' . $exam['track_name'] : '')); ?></td>
                                    <td><?php echo htmlspecialchars($exam['uploader_name']); ?></td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $exam['exam_id']; ?>" class="action-btn btn-edit"><i class="fa-solid fa-edit"></i> تعديل</a>
                                        <a href="delete_process.php?id=<?php echo $exam['exam_id']; ?>" class="action-btn btn-delete" onclick="return confirm('هل أنت متأكد؟');"><i class="fa-solid fa-trash"></i> حذف</a>
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
<script src="exams_script.js"></script>
</body>
</html>