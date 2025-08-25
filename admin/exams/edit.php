<?php
session_start();
require_once '../../config/db_connexion.php';

// 1. حماية الصفحة والتحقق من الصلاحيات
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$exam_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$exam_id) {
    header("Location: index.php?error=معرف غير صحيح");
    exit();
}

// 2. جلب البيانات اللازمة للنموذج
try {
    // أ. جلب بيانات الامتحان الحالي
    $stmt = $pdo->prepare("
        SELECT 
            e.*, 
            os.offering_id,
            co.class_id,
            co.group_id,
            co.track_id,
            co.division_id
        FROM exams e
        JOIN offering_subjects os ON e.offering_subject_id = os.offering_subject_id
        JOIN course_offerings co ON os.offering_id = co.offering_id
        WHERE e.exam_id = ?
    ");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch();

    if (!$exam) {
        header("Location: index.php?error=لم يتم العثور على الامتحان");
        exit();
    }

    // ب. جلب البيانات للقوائم المنسدلة
    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name")->fetchAll();
    $tracks = $pdo->query("SELECT * FROM tracks WHERE division_id = " . (int)$exam['division_id'])->fetchAll();
    $classes = $pdo->query("SELECT * FROM classes WHERE track_id = " . (int)$exam['track_id'])->fetchAll();
    $groups = $pdo->query("SELECT * FROM `groups` WHERE class_id = " . (int)$exam['class_id'])->fetchAll();
    $offering_subjects = $pdo->query("
        SELECT os.offering_subject_id, s.subject_name, p.professor_name
        FROM offering_subjects os
        JOIN subjects s ON os.subject_id = s.subject_id
        LEFT JOIN professors p ON os.professor_id = p.professor_id
        WHERE os.offering_id = " . (int)$exam['offering_id'])->fetchAll();

} catch (PDOException $e) { 
    die("خطأ: " . $e->getMessage()); 
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل امتحان - لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Tajawal', sans-serif; background: #f4f8fc; margin: 0; }
        .admin-container { display: flex; min-height: 100vh; }
        .admin-main-content { flex: 1; padding: 20px; }
        .form-section { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; }
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; padding: 10px; border: 1px solid #e9ecef; 
            border-radius: 8px; font-size: 16px; box-sizing: border-box; 
        }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .form-group input[type="file"] { padding: 5px; }
        .filter-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        button { 
            background: #28a745; color: white; border: none; padding: 12px 20px; 
            border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; 
            transition: background 0.2s ease; width: 100%; 
        }
        button:hover { background: #218838; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<?php require_once '../templates/header.php'; ?>
<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-pen-to-square"></i> تعديل الامتحان: <?php echo htmlspecialchars($exam['title']); ?></h2>
        <p><a href="index.php">&larr; العودة إلى قائمة الامتحانات</a></p>

        <div class="form-section">
            <form action="update_process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="exam_id" value="<?php echo $exam['exam_id']; ?>">
                
                <div class="form-group">
                    <label for="title">عنوان الامتحان:</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($exam['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">الوصف (اختياري):</label>
                    <textarea name="description" rows="3"><?php echo htmlspecialchars($exam['description']); ?></textarea>
                </div>

                <div class="filter-form">
                    <div class="form-group">
                        <label>الشعبة:</label>
                        <select id="division" required>
                            <?php foreach ($divisions as $div): ?>
                                <option value="<?php echo $div['division_id']; ?>" <?php if($div['division_id'] == $exam['division_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($div['division_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>المسار:</label>
                        <select id="track" required>
                            <?php foreach ($tracks as $track): ?>
                                <option value="<?php echo $track['track_id']; ?>" <?php if($track['track_id'] == $exam['track_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($track['track_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>الفصل:</label>
                        <select id="class" required>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['class_id']; ?>" <?php if($class['class_id'] == $exam['class_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($class['class_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>الفوج:</label>
                        <select id="group" required>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?php echo $group['group_id']; ?>" <?php if($group['group_id'] == $exam['group_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($group['group_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>المقرر (المادة والأستاذ):</label>
                        <select id="subject" name="offering_subject_id" required>
                            <?php foreach ($offering_subjects as $os): ?>
                                <option value="<?php echo $os['offering_subject_id']; ?>" <?php if($os['offering_subject_id'] == $exam['offering_subject_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($os['subject_name'] . ' - ' . ($os['professor_name'] ?? 'غير محدد')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="exam_file">استبدال ملف الامتحان (اختياري):</label>
                    <input type="file" name="exam_file" accept=".pdf,.jpg,.jpeg,.png">
                    <p style="font-size: 14px; color: #6c757d;">الملف الحالي: <?php echo basename($exam['file_path']); ?></p>
                </div>
                
                <button type="submit"><i class="fa-solid fa-save"></i> حفظ التعديلات</button>
            </form>
        </div>
    </main>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../../assets/js/admin_script.js"></script>
</body>
</html>