<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة والتأكد من أن المستخدم هو أدمن
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// التأكد من وجود معرّف المسار في الرابط
$track_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$track_id) {
    header("Location: index.php?error=معرف غير صحيح");
    exit();
}

try {
    // جلب البيانات الحالية للمسار
    $stmt_track = $pdo->prepare("SELECT * FROM tracks WHERE track_id = ?");
    $stmt_track->execute([$track_id]);
    $track = $stmt_track->fetch();

    if (!$track) {
        header("Location: index.php?error=لم يتم العثور على المسار");
        exit();
    }

    // جلب جميع الشعب لعرضها في القائمة المنسدلة
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
    <title>تعديل المسار - لوحة التحكم</title>
    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<?php require_once '../templates/header.php'; ?>

<div class="admin-container">
    
    <?php require_once '../templates/sidebar.php'; ?>

    <main class="admin-main-content">
        <h2><i class="fa-solid fa-pen-to-square"></i> تعديل المسار: <?php echo htmlspecialchars($track['track_name']); ?></h2>
        <p><a href="index.php">&larr; العودة إلى قائمة المسارات</a></p>

        <div class="form-section">
            <form action="update_process.php" method="POST">
                <input type="hidden" name="track_id" value="<?php echo $track['track_id']; ?>">
                
                <div class="form-group">
                    <label for="track_name">اسم المسار:</label>
                    <input type="text" id="track_name" name="track_name" value="<?php echo htmlspecialchars($track['track_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="division_id">الشعبة التابع لها:</label>
                    <select id="division_id" name="division_id" required>
                        <option value="" disabled>-- اختر الشعبة --</option>
                        <?php foreach ($divisions as $division): ?>
                            <option value="<?php echo $division['division_id']; ?>" <?php if($division['division_id'] == $track['division_id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($division['division_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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