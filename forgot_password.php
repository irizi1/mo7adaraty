<?php
session_start();
require_once 'config/db_connexion.php';

$step = 1; // الخطوة الافتراضية هي الأولى
$username = '';
$user_id = null;
$error = $_SESSION['reset_error'] ?? null;
$success = $_SESSION['reset_success'] ?? null;
unset($_SESSION['reset_error'], $_SESSION['reset_success']);

// التحقق إذا كانت هناك خطوة سابقة (التحقق من اسم المستخدم)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_username'])) {
    $username = trim($_POST['username']);
    if (empty($username)) {
        $error = "الرجاء إدخال اسم المستخدم.";
    } else {
        $stmt = $pdo->prepare("SELECT user_id, username FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            // المستخدم موجود، انتقل للخطوة الثانية
            $step = 2;
            $user_id = $user['user_id'];
            $username = $user['username'];
        } else {
            $error = "اسم المستخدم غير موجود.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
           <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">
    <title>إعادة تعيين كلمة المرور - Mo7adaraty</title>
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .form-container {
            background: #fff;
            width: 380px;
            max-width: 90%;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            text-align: center;
        }
        h2 {
            margin-bottom: 15px;
            color: #4e73df;
        }
        p {
            font-size: 14px;
            color: #555;
        }
        .form-group {
            margin: 15px 0;
            text-align: right;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: 0.3s;
        }
        input:focus {
            border-color: #4e73df;
            outline: none;
            box-shadow: 0 0 6px rgba(78,115,223,0.4);
        }
        button {
            width: 100%;
            padding: 12px;
            background: #4e73df;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.3s;
        }
        button:hover {
            background: #2e59d9;
        }
        .status-message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 8px;
            font-size: 14px;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
        }
        .success {
            background: #d4edda;
            color: #155724;
        }
        .switch-form {
            margin-top: 15px;
        }
        .switch-form a {
            color: #1cc88a;
            font-weight: bold;
            text-decoration: none;
        }
        .switch-form a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>🔑 إعادة تعيين كلمة المرور</h2>
    
    <?php if ($error): ?>
        <p class="status-message error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="status-message success"><?php echo htmlspecialchars($success); ?></p>
        <div class="switch-form"><p><a href="login.php">⬅ العودة إلى صفحة الدخول</a></p></div>
    <?php else: ?>

        <?php if ($step === 1): ?>
            <p>الرجاء إدخال اسم المستخدم الخاص بك للتحقق من حسابك.</p>
            <form action="forgot_password.php" method="POST">
                <div class="form-group">
                    <label for="username">👤 اسم المستخدم:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <button type="submit" name="check_username">تحقق من الحساب</button>
            </form>
        <?php elseif ($step === 2): ?>
            <p>مرحباً <strong><?php echo htmlspecialchars($username); ?></strong> 👋<br>الرجاء تأكيد هويتك بإدخال بريدك الإلكتروني المسجل.</p>
            <form action="forgot_password_process.php" method="POST">
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">

                <div class="form-group">
                    <label for="registered_email">📧 البريد الإلكتروني المسجل:</label>
                    <input type="email" id="registered_email" name="registered_email" required>
                </div>
                <div class="form-group">
                    <label for="contact_info">☎ وسيلة للتواصل (بريد أو واتساب):</label>
                    <input type="text" id="contact_info" name="contact_info" placeholder="سيتم إرسال كلمة المرور الجديدة إليه" required>
                </div>
                <button type="submit">إرسال طلب إعادة التعيين</button>
            </form>
        <?php endif; ?>
        
        <div class="switch-form">
            <p>تذكرت كلمة المرور؟ <a href="login.php">تسجيل الدخول</a></p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
