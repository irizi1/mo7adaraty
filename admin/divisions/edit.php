<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة والتأكد من أن المستخدم هو أدمن
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$division_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$division_id) { 
    header("Location: index.php?error=معرف غير صحيح"); 
    exit(); 
}

try {
    $stmt = $pdo->prepare("SELECT * FROM divisions WHERE division_id = ?");
    $stmt->execute([$division_id]);
    $division = $stmt->fetch();
    if (!$division) { 
        header("Location: index.php?error=لم يتم العثور على الشعبة"); 
        exit(); 
    }
} catch (PDOException $e) { 
    die("خطأ: " . $e->getMessage()); 
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale-1.0">
    <title>تعديل شعبة - لوحة التحكم</title>
    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<?php require_once '../templates/header.php'; ?>

<div class="admin-container">
    
    <?php require_once '../templates/sidebar.php'; ?>

    <main class="admin-main-content">
        <h2><i class="fa-solid fa-pen-to-square"></i> تعديل الشعبة: <?php echo htmlspecialchars($division['division_name']); ?></h2>
        <p><a href="index.php">&larr; العودة إلى قائمة الشعب</a></p>

        <div class="form-section">
            <form action="update_process.php" method="POST">
                <input type="hidden" name="division_id" value="<?php echo $division['division_id']; ?>">
                <div class="form-group">
                    <label for="division_name">اسم الشعبة:</label>
                    <input type="text" id="division_name" name="division_name" value="<?php echo htmlspecialchars($division['division_name']); ?>" required>
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