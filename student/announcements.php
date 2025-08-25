<?php
session_start();
require_once '../config/db_connexion.php';

// حماية الصفحة والتأكد من أن المستخدم طالب مسجل دخوله
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

try {
    // جلب جميع الإعلانات العامة من قاعدة البيانات، الأحدث أولاً
    $stmt = $pdo->query("SELECT * FROM global_announcements ORDER BY created_at DESC");
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {
    die("خطأ في جلب الإعلانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
  
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">
    <title>الإعلانات - Mo7adaraty</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; }
        .page-container { max-width: 900px; margin: 40px auto; padding: 20px; }
        .page-container h1 { text-align: center; margin-bottom: 30px; }
        .announcement-card {
            background-color: #fff;
            border-right: 5px solid #007bff;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .announcement-card h2 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 22px;
        }
        .announcement-card .date {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 15px;
        }
        .announcement-card .content {
            line-height: 1.7;
            color: #333;
        }
    </style>
</head>
<body>

<?php require_once '../templates/header.php'; ?>

<div class="page-container">
    <h1><i class="fa-solid fa-bullhorn"></i> الإعلانات العامة</h1>

    <?php if (count($announcements) > 0): ?>
        <?php foreach ($announcements as $announcement): ?>
            <div class="announcement-card">
                <h2><?php echo htmlspecialchars($announcement['title']); ?></h2>
                <p class="date">
                    تم النشر في: <?php echo date('d-m-Y \ا\ل\س\ا\ع\ة H:i', strtotime($announcement['created_at'])); ?>
                </p>
                <div class="content">
                    <?php 
                        // استخدام nl2br للحفاظ على فواصل الأسطر التي كتبها الأدمن
                        echo nl2br(htmlspecialchars($announcement['content'])); 
                    ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="text-align: center;">لا توجد أي إعلانات لعرضها حالياً.</p>
    <?php endif; ?>
</div>
<?php include '../templates/footer.php'; ?>

</body>
</html>