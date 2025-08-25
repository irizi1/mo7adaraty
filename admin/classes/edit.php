<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$class_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$class_id) {
    header("Location: index.php?error=معرف غير صحيح");
    exit();
}

try {
    // جلب بيانات الفصل الحالي مع المسار والشعبة
    $stmt_class = $pdo->prepare("
        SELECT c.class_id, c.class_name, c.track_id, t.division_id 
        FROM classes c
        JOIN tracks t ON c.track_id = t.track_id
        WHERE c.class_id = ?
    ");
    $stmt_class->execute([$class_id]);
    $class = $stmt_class->fetch();

    if (!$class) {
        header("Location: index.php?error=لم يتم العثور على الفصل");
        exit();
    }

    // جلب جميع الشعب لملء القائمة
    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name")->fetchAll();
    
    // جلب المسارات التابعة للشعبة الحالية للفصل
    $stmt_tracks = $pdo->prepare("SELECT * FROM tracks WHERE division_id = ? ORDER BY track_name");
    $stmt_tracks->execute([$class['division_id']]);
    $tracks = $stmt_tracks->fetchAll();

} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل فصل - لوحة التحكم</title>
    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<?php require_once '../templates/header.php'; ?>

<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-pen-to-square"></i> تعديل الفصل: <?php echo htmlspecialchars($class['class_name']); ?></h2>
        <p><a href="index.php">&larr; العودة إلى قائمة الفصول</a></p>

        <div class="form-section">
            <form action="update_process.php" method="POST">
                <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
                
                <div class="filter-form">
                    <div class="form-group">
                        <label for="division">الشعبة:</label>
                        <select id="division" name="division_id" required>
                            <?php foreach ($divisions as $division): ?>
                                <option value="<?php echo $division['division_id']; ?>" <?php if($division['division_id'] == $class['division_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($division['division_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="track">المسار:</label>
                        <select id="track" name="track_id" required>
                            <?php foreach ($tracks as $track): ?>
                                <option value="<?php echo $track['track_id']; ?>" <?php if($track['track_id'] == $class['track_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($track['track_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="class_name">اسم الفصل:</label>
                    <input type="text" id="class_name" name="class_name" value="<?php echo htmlspecialchars($class['class_name']); ?>" required>
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