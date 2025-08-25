<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">
    <title>تسجيل الدخول - Mo7adaraty</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* تنسيقات عامة للجسم */
        body {
            background-color: #f0f4f8;
            font-family: 'Tajawal', sans-serif;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .login-wrapper {
            display: flex;
            max-width: 900px;
            width: 100%;
            background-color: rgba(255, 255, 255, 0.9); /* شفافية للخلفية */
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .image-container {
            flex: 1;
            display: none; /* مخفي بشكل افتراضي وسيظهر على الشاشات الكبيرة */
        }

        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .form-container {
            flex: 1;
            padding: 40px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background-color: transparent; /* جعل الفورم شفاف */
        }

        h2 {
            color: #007bff;
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: bold;
        }

        .status-message {
            text-align: center;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: bold;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: right;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        .form-group input:focus {
            border-color: #007bff;
            outline: none;
        }

        .password-group {
            position: relative;
        }
        .password-group input {
            padding-left: 40px; /* مساحة للأيقونة */
        }
        .password-group .toggle-password {
            position: absolute;
            top: 50%;
            left: 15px; /* تم تغيير الموقع لليسار */
            transform: translateY(-50%);
            cursor: pointer;
            color: #777;
            font-size: 18px;
            transition: color 0.3s ease;
        }
        .password-group .toggle-password:hover {
            color: #007bff;
        }

        button[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button[type="submit"]:hover {
            background-color: #0056b3;
        }

        .switch-form {
            margin-top: 20px;
            font-size: 14px;
        }
        .switch-form a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        .switch-form a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (min-width: 768px) {
            .image-container {
                display: block; /* إظهار الصورة على الشاشات الأكبر من 768px */
            }
            .form-container {
                padding: 50px;
            }
        }

        #bg-video {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            z-index: -1;
        }
        .overlay {
            position: relative;
            z-index: 1;
            background: transparent; /* جعل الـ overlay شفاف */
            border-radius: 12px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <video autoplay muted loop id="bg-video">
        <source src="https://streamable.com/de8pkj" type="video/mp4">
    </video>
    <div class="overlay">
        <div class="login-wrapper">
           
            <div class="form-container">
                <h2>تسجيل الدخول إلى Mo7adaraty</h2>
                
                <?php
                    if (isset($_GET['error'])) {
                        echo '<p class="status-message error">' . htmlspecialchars($_GET['error']) . '</p>';
                    }
                    if (isset($_GET['status']) && $_GET['status'] == 'signup_success') {
                        echo '<p class="status-message success">تم إنشاء حسابك بنجاح! يمكنك الآن تسجيل الدخول.</p>';
                    }
                ?>

                <form action="loader.php" method="POST">
                    <input type="hidden" name="target" value="login_process.php">
                    <input type="hidden" name="message" value="جارٍ تسجيل الدخول...">
                    <div class="form-group">
                        <label for="login_identifier">البريد الإلكتروني أو اسم المستخدم:</label>
                        <input type="text" id="login_identifier" name="login_identifier" required>
                    </div>

                    <div class="form-group password-group">
                        <label for="password">كلمة المرور:</label>
                        <input type="password" id="password" name="password" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                    </div>
                    
                    <button type="submit">دخول</button>
                </form>

                <div class="switch-form">
                    <p>ليس لديك حساب؟ <a href="signup.php">إنشاء حساب جديد</a>.</p>
                    <p><a href="forgot_password.php">هل نسيت كلمة المرور؟</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>