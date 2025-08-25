<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$subject_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$subject_id) {
    header("Location: index.php?error=معرف غير صحيح");
    exit();
}

try {
    // جلب بيانات المادة الحالية
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE subject_id = ?");
    $stmt->execute([$subject_id]);
    $subject = $stmt->fetch();

    if (!$subject) {
        header("Location: index.php?error=لم يتم العثور على المادة");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل مادة - لوحة التحكم</title>
    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<?php require_once '../templates/header.php'; ?>

<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-pen-to-square"></i> تعديل المادة: <?php echo htmlspecialchars($subject['subject_name']); ?></h2>
        <p><a href="index.php">&larr; العودة إلى قائمة المواد</a></p>

        <div class="form-section">
            <form action="update_process.php" method="POST">
                <input type="hidden" name="subject_id" value="<?php echo $subject['subject_id']; ?>">
                
                <div class="form-group">
                    <label for="subject_name">اسم المادة:</label>
                    <input type="text" id="subject_name" name="subject_name" value="<?php echo htmlspecialchars($subject['subject_name']); ?>" required>
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