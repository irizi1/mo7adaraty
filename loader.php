<?php
// استقبال البيانات
$target = $_POST['target'] ?? $_GET['target'] ?? 'home.php';
$message = $_POST['message'] ?? $_GET['message'] ?? 'جارٍ التحميل...';

// تخزين باقي بيانات POST (عدا target و message)
$postData = $_POST;
unset($postData['target'], $postData['message']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($message); ?></title>
    <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Tajawal', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, #ffffff, #f1f5ff);
        }
        .loader-container {
            text-align: center;
        }
        .loader-logo {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            animation: pulse 2s infinite ease-in-out;
            box-shadow: 0 0 15px rgba(0, 123, 255, 0.3);
        }
        .loader-text {
            font-size: 22px;
            color: #333;
            margin-top: 15px;
            animation: fadeIn 1.5s ease-in-out infinite alternate;
        }
        .progress-bar-container {
            width: 250px;
            height: 10px;
            background-color: #e9ecef;
            border-radius: 20px;
            overflow: hidden;
            margin: 20px auto 0;
        }
        .progress-bar {
            width: 0;
            height: 100%;
            background: linear-gradient(90deg, #007bff, #6f42c1);
            border-radius: 20px;
            animation: load 2s ease-out forwards;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        @keyframes load {
            0% { width: 0%; }
            100% { width: 100%; }
        }
        @keyframes fadeIn {
            from { opacity: 0.5; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="loader-container">
        <img src="image.png" alt="Logo" class="loader-logo">
        <p class="loader-text"><?php echo htmlspecialchars($message); ?></p>
        <div class="progress-bar-container">
            <div class="progress-bar"></div>
        </div>
    </div>

    <!-- إعادة توجيه -->
    <?php if (!empty($postData)) : ?>
        <form id="redirectForm" action="<?php echo htmlspecialchars($target); ?>" method="POST" style="display:none;">
            <?php foreach ($postData as $key => $value): ?>
                <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
            <?php endforeach; ?>
        </form>
        <script>
            setTimeout(() => {
                document.getElementById('redirectForm').submit();
            }, 2000);
        </script>
    <?php else: ?>
        <script>
            setTimeout(() => {
                window.location.href = "<?php echo htmlspecialchars($target); ?>";
            }, 2000);
        </script>
    <?php endif; ?>
</body>
</html>
