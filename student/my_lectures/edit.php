<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id'])) {
        header("Location: ../../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// جلب ID المحاضرة والتحقق منه
$lecture_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$lecture_id) {
    header("Location: index.php");
    exit();
}

try {
    // جلب بيانات المحاضرة والتأكد من أن الطالب هو مالكها وأنها ما زالت قيد المراجعة
    $stmt = $pdo->prepare("
        SELECT * FROM lectures 
        WHERE lecture_id = ? AND uploader_user_id = ? AND status = 'pending'
    ");
    $stmt->execute([$lecture_id, $user_id]);
    $lecture = $stmt->fetch();

    if (!$lecture) {
        // إذا لم يتم العثور عليها (إما لأنها ليست ملكه أو تمت مراجعتها بالفعل)
        $_SESSION['lecture_action_status'] = ['type' => 'error', 'message' => 'لا يمكنك تعديل هذه المحاضرة.'];
        header("Location: index.php");
        exit();
    }

} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تعديل المحاضرة - Mo7adaraty</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="center-content">

<div class="form-container">
    <h2><i class="fa-solid fa-pen-to-square"></i> تعديل المحاضرة</h2>

    <form action="update_process.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="lecture_id" value="<?php echo $lecture['lecture_id']; ?>">

        <div class="form-group">
            <label for="title">عنوان المحاضرة:</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($lecture['title']); ?>" required>
        </div>

        <div class="form-group">
            <label for="description">الوصف:</label>
            <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($lecture['description']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="lecture_file">استبدال الملف (اختياري):</label>
            <input type="file" id="lecture_file" name="lecture_file" accept=".pdf,.jpg,.jpeg,.png">
            <p class="hint">الملف الحالي: <?php echo basename($lecture['file_path']); ?></p>
        </div>
        
        <button type="submit">حفظ التعديلات</button>
    </form>
     <div class="switch-form">
        <p><a href="index.php">العودة إلى محاضراتي</a></p>
    </div>
</div>

</body>
</html>