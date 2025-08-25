<?php
session_start();
require_once '../config/db_connexion.php';

// 1. حماية الصفحة والتحقق من أن المستخدم مسجل دخوله
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// 2. استقبال معرّف المقرر (offering_id)
$offering_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$offering_id) {
    header("Location: profil.php?error=رابط المقرر غير صحيح");
    exit();
}

try {
    // 3. جلب معلومات المقرر لعرضها في الصفحة
    $stmt_offering = $pdo->prepare("
        SELECT c.class_name 
        FROM course_offerings co
        JOIN classes c ON co.class_id = c.class_id
        WHERE co.offering_id = ?
    ");
    $stmt_offering->execute([$offering_id]);
    $class_info = $stmt_offering->fetch();

    if (!$class_info) {
        die("خطأ: لم يتم العثور على المقرر المطلوب.");
    }

    // 4. جلب المواد المتاحة للطالب في هذا المقرر
    $stmt_subjects = $pdo->prepare("
        SELECT os.offering_subject_id, s.subject_name, p.professor_name
        FROM offering_subjects os
        JOIN subjects s ON os.subject_id = s.subject_id
        LEFT JOIN professors p ON os.professor_id = p.professor_id
        WHERE os.offering_id = ?
        ORDER BY s.subject_name
    ");
    $stmt_subjects->execute([$offering_id]);
    $available_subjects = $stmt_subjects->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رفع محاضرة جديدة - Mo7adaraty</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --brand-color: #007bff; 
            --bg-gradient: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            --card-bg: #ffffff;
            --border-color: #e9ecef; 
            --text-primary: #343a40;
            --text-secondary: #6c757d; 
            --green-color: #28a745;
            --red-color: #dc3545;
        }
        body { 
            font-family: 'Tajawal', sans-serif; 
            background: var(--bg-gradient);
            margin: 0; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh;
            padding: 20px;
        }
        .form-container {
            max-width: 650px; 
            width: 100%; 
            background: var(--card-bg); 
            padding: 40px; 
            border-radius: 16px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            border-top: 5px solid var(--brand-color);
        }
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-header h2 { 
            color: var(--text-primary); 
            margin: 0 0 10px; 
            font-size: 26px; 
        }
        .form-header p { 
            color: var(--text-secondary); 
            margin: 0;
            font-size: 16px;
        }
        .form-header .course-name {
            font-weight: bold;
            color: var(--brand-color);
        }
        .status-message.error { 
            background: #f8d7da; 
            color: var(--red-color); 
            padding: 12px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            text-align: center; 
            border: 1px solid #f5c6cb;
        }
        .form-group { 
            margin-bottom: 25px; 
        }
        .form-group label { 
            display: block; 
            font-weight: 600; 
            margin-bottom: 8px; 
            color: var(--text-primary); 
        }
        .form-group input, 
        .form-group select, 
        .form-group textarea { 
            width: 100%; 
            padding: 12px 15px; 
            border: 1px solid var(--border-color); 
            border-radius: 8px; 
            font-size: 16px; 
            box-sizing: border-box; 
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus, 
        .form-group select:focus, 
        .form-group textarea:focus {
            outline: none;
            border-color: var(--brand-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
        }
        .form-group textarea { 
            resize: vertical; 
            min-height: 100px; 
        }
        .file-upload-wrapper {
            position: relative;
            border: 2px dashed var(--border-color);
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        .file-upload-wrapper:hover {
            border-color: var(--brand-color);
        }
        .file-upload-wrapper input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }
        .file-upload-icon {
            font-size: 40px;
            color: var(--brand-color);
            margin-bottom: 15px;
        }
        .file-upload-text {
            color: var(--text-secondary);
        }
        .file-name {
            font-weight: bold;
            color: var(--green-color);
            margin-top: 10px;
            display: block;
        }
        .main-button { 
            background: var(--green-color); 
            color: white; 
            border: none; 
            padding: 14px 20px; 
            border-radius: 8px; 
            width: 100%; 
            font-size: 18px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: background 0.2s ease, transform 0.1s ease;
        }
        .main-button:hover { 
            background: #218838; 
            transform: translateY(-2px);
        }
        .back-link { 
            display: block;
            text-align: center; 
            margin-top: 20px; 
            color: var(--brand-color); 
            text-decoration: none; 
            font-weight: 500; 
        }
    </style>
</head>
<body>
<div class="form-container">
    <div class="form-header">
        <h2><i class="fa-solid fa-cloud-arrow-up"></i> رفع محاضرة جديدة</h2>
        <p>للمقرر الدراسي: <span class="course-name"><?php echo htmlspecialchars($class_info['class_name']); ?></span></p>
    </div>

    <?php if (isset($_SESSION['upload_error'])): ?>
        <p class="status-message error"><?php echo htmlspecialchars($_SESSION['upload_error']); ?></p>
        <?php unset($_SESSION['upload_error']); ?>
    <?php endif; ?>

    <form action="upload_lecture_process.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="offering_id" value="<?php echo $offering_id; ?>">

        <div class="form-group">
            <label for="offering_subject_id">اختر المادة</label>
            <select name="offering_subject_id" id="offering_subject_id" required>
                <option value="" disabled selected>-- اختر المادة التي تتبع لها المحاضرة --</option>
                <?php foreach ($available_subjects as $subject): ?>
                    <option value="<?php echo $subject['offering_subject_id']; ?>">
                        <?php echo htmlspecialchars($subject['subject_name'] . ' - ' . ($subject['professor_name'] ?? 'غير محدد')); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="title">عنوان المحاضرة</label>
            <input type="text" id="title" name="title" placeholder="مثال: المحاضرة الخامسة - الفصل الثاني" required>
        </div>

        <div class="form-group">
            <label for="description">وصف مختصر (اختياري)</label>
            <textarea id="description" name="description" rows="3" placeholder="أضف أي ملاحظات حول المحاضرة هنا..."></textarea>
        </div>

        <div class="form-group">
            <label>ملف المحاضرة</label>
            <div class="file-upload-wrapper">
                <input type="file" id="lecture_file" name="lecture_file" accept=".pdf,.jpg,.jpeg,.png" required onchange="displayFileName(this)">
                <div class="file-upload-icon"><i class="fas fa-file-alt"></i></div>
                <div class="file-upload-text">
                    <p>اسحب الملف وأفلته هنا أو انقر للاختيار</p>
                    <small>الصيغ المسموحة: PDF, JPG, PNG (الحجم الأقصى: 5MB)</small>
                    <span id="file-name" class="file-name"></span>
                </div>
            </div>
        </div>
        
        <button type="submit" class="main-button">
            <i class="fa-solid fa-paper-plane"></i> إرسال للمراجعة
        </button>
    </form>
    <a href="class_lectures.php?id=<?php echo $offering_id; ?>" class="back-link">
        <i class="fa-solid fa-arrow-right"></i> العودة إلى المحاضرات
    </a>
</div>

<script>
    function displayFileName(input) {
        const fileNameDisplay = document.getElementById('file-name');
        if (input.files.length > 0) {
            fileNameDisplay.textContent = 'الملف المختار: ' + input.files[0].name;
        } else {
            fileNameDisplay.textContent = '';
        }
    }
</script>

</body>
</html>