<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة والتأكد من أن المستخدم هو أدمن
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$lecture_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$lecture_id) { 
    header("Location: index.php?error=معرف غير صحيح"); 
    exit(); 
}

try {
    // جلب بيانات المحاضرة الحالية مع كل ما يتعلق بها لتعبئة النموذج
    $stmt = $pdo->prepare("
        SELECT 
            l.*, 
            s.class_id,
            c.track_id,
            t.division_id
        FROM lectures l
        JOIN subjects s ON l.subject_id = s.subject_id
        JOIN classes c ON s.class_id = c.class_id
        JOIN tracks t ON c.track_id = t.track_id
        WHERE l.lecture_id = ?
    ");
    $stmt->execute([$lecture_id]);
    $lecture = $stmt->fetch();

    if (!$lecture) { 
        header("Location: index.php?error=لم يتم العثور على المحاضرة"); 
        exit(); 
    }

    // جلب كل البيانات اللازمة لملء القوائم المنسدلة
    $divisions = $pdo->query("SELECT * FROM divisions")->fetchAll();
    $tracks = $pdo->query("SELECT * FROM tracks WHERE division_id = " . (int)$lecture['division_id'])->fetchAll();
    $classes = $pdo->query("SELECT * FROM classes WHERE track_id = " . (int)$lecture['track_id'])->fetchAll();
    $subjects = $pdo->query("SELECT * FROM subjects WHERE class_id = " . (int)$lecture['class_id'])->fetchAll();

} catch (PDOException $e) { 
    die("خطأ: " . $e->getMessage()); 
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل محاضرة - لوحة التحكم</title>
    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require_once '../templates/header.php'; ?>
<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-pen-to-square"></i> تعديل المحاضرة: <?php echo htmlspecialchars($lecture['title']); ?></h2>
        <p><a href="index.php">&larr; العودة إلى قائمة المحاضرات</a></p>

        <div class="form-section">
            <form action="update_process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="lecture_id" value="<?php echo $lecture['lecture_id']; ?>">
                
                <div class="form-group">
                    <label for="title">رقم المحاضرة:</label>
                    <select id="title" name="title" required>
                        <option value="" disabled>-- اختر رقم المحاضرة --</option>
                        <?php for ($i = 1; $i <= 20; $i++): 
                            $option_value = "المحاضرة " . $i;
                            $is_selected = ($lecture['title'] == $option_value) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $option_value; ?>" <?php echo $is_selected; ?>><?php echo $option_value; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">الوصف:</label>
                    <textarea name="description" rows="3"><?php echo htmlspecialchars($lecture['description']); ?></textarea>
                </div>

                <div class="filter-form">
                    <div class="form-group">
                        <label>الشعبة:</label>
                        <select id="division" required>
                            <?php foreach ($divisions as $div): ?>
                                <option value="<?php echo $div['division_id']; ?>" <?php if($div['division_id'] == $lecture['division_id']) echo 'selected'; ?>><?php echo htmlspecialchars($div['division_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>المسار:</label>
                        <select id="track" required>
                             <?php foreach ($tracks as $track): ?>
                                <option value="<?php echo $track['track_id']; ?>" <?php if($track['track_id'] == $lecture['track_id']) echo 'selected'; ?>><?php echo htmlspecialchars($track['track_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>الفصل:</label>
                        <select id="class" required>
                             <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['class_id']; ?>" <?php if($class['class_id'] == $lecture['class_id']) echo 'selected'; ?>><?php echo htmlspecialchars($class['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>المادة:</label>
                        <select id="subject" name="subject_id" required>
                             <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['subject_id']; ?>" <?php if($subject['subject_id'] == $lecture['subject_id']) echo 'selected'; ?>><?php echo htmlspecialchars($subject['subject_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="lecture_file">استبدال ملف المحاضرة (اختياري):</label>
                    <input type="file" name="lecture_file">
                    <p style="font-size: 14px; color: #6c757d;">الملف الحالي: <?php echo basename($lecture['file_path']); ?></p>
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