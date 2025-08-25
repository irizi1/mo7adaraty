<?php
require_once 'config/db_connexion.php';
try {
    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name")->fetchAll();
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات.");
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب جديد - Mo7adaraty</title>
             <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #5f6368;
            --bg-light: #f5f7fa;
            --bg-white: #ffffff;
            --text-dark: #202124;
            --text-light: #f1f3f4;
            --accent-color: #34c759;
            --error-color: #d93025;
            --shadow-color: rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(135deg, var(--bg-light), #e8ecef);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            color: var(--text-dark);
        }

        .form-container {
            background: var(--bg-white);
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 8px 30px var(--shadow-color);
            width: 100%;
            max-width: 600px;
            margin: 20px auto;
        }

        .form-container h2 {
            text-align: center;
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 30px;
            position: relative;
        }
        .form-container h2::after {
            content: '';
            width: 60px;
            height: 4px;
            background-color: var(--primary-color);
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 2px;
        }

        .status-message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 1rem;
        }
        .status-message.error {
            background-color: #fdeded;
            color: var(--error-color);
        }

        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-dark);
        }
        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        input:focus, select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 8px rgba(26, 115, 232, 0.2);
            outline: none;
        }

        #password-container {
            position: relative;
        }
        #togglePassword {
            position: absolute;
            top: 50%;
            left: 12px;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--secondary-color);
            font-size: 1.2rem;
        }

        .password-strength {
            margin-top: 8px;
            font-size: 0.9rem;
            color: var(--secondary-color);
        }
        .password-strength.weak { color: var(--error-color); }
        .password-strength.medium { color: #f4b400; }
        .password-strength.strong { color: var(--accent-color); }

        .enrollment-block {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            background-color: var(--bg-light);
        }
        .enrollment-block h4 {
            margin: 0 0 15px;
            font-size: 1.3rem;
            color: var(--text-dark);
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .track-group {
            display: none;
        }

        button {
            background-color: var(--primary-color);
            color: var(--text-light);
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease;
            width: 100%;
        }
        button:hover {
            background-color: #1557b0;
            transform: translateY(-2px);
        }
        #add-enrollment-btn {
            width: auto;
            background-color: var(--accent-color);
            padding: 10px 20px;
            margin-top: 10px;
        }
        #add-enrollment-btn:hover {
            background-color: #2ea44f;
        }

        .switch-form {
            text-align: center;
            margin-top: 20px;
        }
        .switch-form a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
        }
        .switch-form a:hover {
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .form-container {
                padding: 20px;
                margin: 10px;
            }
            .form-container h2 {
                font-size: 1.8rem;
            }
            .form-group {
                margin-bottom: 15px;
            }
            input, select {
                padding: 10px;
                font-size: 0.95rem;
            }
            button {
                padding: 12px;
                font-size: 1rem;
            }
            #add-enrollment-btn {
                padding: 8px 16px;
            }
        }

        @media (max-width: 576px) {
            .form-container {
                padding: 15px;
            }
            .form-container h2 {
                font-size: 1.6rem;
            }
            .enrollment-block {
                padding: 15px;
            }
            .enrollment-block h4 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>

<div class="form-container" data-aos="fade-up">
    <h2>إنشاء حساب جديد</h2>
    <?php if (isset($_GET['error'])): ?>
        <p class="status-message error"><?php echo htmlspecialchars($_GET['error']); ?></p>
    <?php endif; ?>

    <form id="signupForm" action="signup_process.php" method="POST" enctype="multipart/form-data">
        <fieldset data-aos="fade-up" data-aos-delay="100">
            <legend>المعلومات الأساسية</legend>
            <div class="form-group">
                <label for="username">اسم المستخدم:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">البريد الإلكتروني:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">كلمة المرور:</label>
                <div id="password-container">
                    <input type="password" id="password" name="password" minlength="8" required>
                    <i class="fas fa-eye" id="togglePassword"></i>
                </div>
                <div class="password-strength" id="passwordStrength"></div>
            </div>
            <div class="form-group">
                <label for="profile_picture">الصورة الشخصية (اختياري):</label>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
            </div>
        </fieldset>

        <fieldset data-aos="fade-up" data-aos-delay="200">
            <legend>اختيار المقررات الدراسية (حتى 2)</legend>
            <div class="form-group">
                <label for="division">اختر الشعبة:</label>
                <select id="division" name="division_id" required>
                    <option value="" selected disabled>-- اختر الشعبة لعرض الفصول --</option>
                    <?php foreach ($divisions as $division): ?>
                        <option value="<?php echo $division['division_id']; ?>"><?php echo htmlspecialchars($division['division_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="enrollment-container"></div>
            
            <button type="button" id="add-enrollment-btn" style="display:none;" data-aos="fade-up" data-aos-delay="300">+ إضافة مقرر آخر</button>
        </fieldset>
        
        <button type="submit" data-aos="fade-up" data-aos-delay="400">إنشاء الحساب</button>
    </form>
    <div class="switch-form" data-aos="fade-up" data-aos-delay="500">
        <p>لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a>.</p>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>
    $(document).ready(function() {
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });

        let enrollmentCounter = 0;
        const ADVANCED_CLASSES_IDS = [5, 6];

        // Password visibility toggle
        $('#togglePassword').click(function() {
            const passwordInput = $('#password');
            const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
            passwordInput.attr('type', type);
            $(this).toggleClass('fa-eye fa-eye-slash');
        });

        // Password strength checker
        $('#password').on('input', function() {
            const password = $(this).val();
            const strengthIndicator = $('#passwordStrength');
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            if (password.length === 0) {
                strengthIndicator.text('').removeClass('weak medium strong');
            } else if (strength <= 2) {
                strengthIndicator.text('كلمة المرور ضعيفة').addClass('weak').removeClass('medium strong');
            } else if (strength === 3) {
                strengthIndicator.text('كلمة المرور متوسطة').addClass('medium').removeClass('weak strong');
            } else {
                strengthIndicator.text('كلمة المرور قوية').addClass('strong').removeClass('weak medium');
            }
        });

        function addEnrollmentBlock() {
            if (enrollmentCounter >= 2) return;
            enrollmentCounter++;
            const blockId = `enrollment-block-${enrollmentCounter}`;

            const enrollmentHtml = `
                <div class="enrollment-block" id="${blockId}" data-aos="fade-up" data-aos-delay="${enrollmentCounter * 100}">
                    <h4>المقرر ${enrollmentCounter}</h4>
                    <div class="form-group">
                        <label for="class-${enrollmentCounter}">الفصل:</label>
                        <select id="class-${enrollmentCounter}" name="enrollments[${enrollmentCounter}][class_id]" class="class-select" required>
                            <option value="">-- اختر الفصل --</option>
                        </select>
                    </div>
                    <div class="form-group track-group">
                        <label for="track-${enrollmentCounter}">المسار:</label>
                        <select id="track-${enrollmentCounter}" name="enrollments[${enrollmentCounter}][track_id]" class="track-select">
                            <option value="">-- اختر المسار --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="group-${enrollmentCounter}">الفوج:</label>
                        <select id="group-${enrollmentCounter}" name="enrollments[${enrollmentCounter}][offering_id]" class="group-select" required>
                            <option value="">-- اختر --</option>
                        </select>
                    </div>
                </div>`;
            
            $('#enrollment-container').append(enrollmentHtml);
            updateAddButtonVisibility();
            populateClasses(enrollmentCounter);
            AOS.refresh();
        }
        
        function updateAddButtonVisibility() {
            $('#add-enrollment-btn').toggle(enrollmentCounter < 2);
        }

        $('#division').on('change', function() {
            $('#enrollment-container').empty();
            enrollmentCounter = 0;
            if ($(this).val()) {
                $('#add-enrollment-btn').show();
                addEnrollmentBlock();
            } else {
                $('#add-enrollment-btn').hide();
            }
        });

        $('#add-enrollment-btn').on('click', addEnrollmentBlock);

        function populateClasses(counter) {
            const divisionID = $('#division').val();
            const $classSelect = $(`#class-${counter}`);
            $classSelect.html('<option value="">جارٍ التحميل...</option>');

            $.post('ajax/get_data_for_signup.php', { action: 'get_classes', division_id: divisionID }, function(classes) {
                $classSelect.html('<option value="">-- اختر الفصل --</option>');
                if (classes.length > 0) {
                    classes.forEach(cls => {
                        $classSelect.append(`<option value="${cls.class_id}">${cls.class_name}</option>`);
                    });
                }
            }, 'json');
        }

        $('#enrollment-container').on('change', '.class-select', function() {
            const block = $(this).closest('.enrollment-block');
            const classID = parseInt($(this).val());
            const $trackGroup = block.find('.track-group');
            const $trackSelect = block.find('.track-select');
            const $groupSelect = block.find('.group-select');

            $trackGroup.hide();
            $trackSelect.html('').prop('required', false);
            $groupSelect.html('<option value="">-- اختر --</option>');

            if (ADVANCED_CLASSES_IDS.includes(classID)) {
                $trackGroup.show();
                $trackSelect.prop('required', true);
                const divisionID = $('#division').val();
                $.post('ajax/get_data_for_signup.php', { action: 'get_tracks', division_id: divisionID }, function(tracks) {
                    $trackSelect.html('<option value="">-- اختر المسار --</option>');
                    tracks.forEach(track => {
                        $trackSelect.append(`<option value="${track.track_id}">${track.track_name}</option>`);
                    });
                }, 'json');
            } else {
                $.post('ajax/get_data_for_signup.php', { action: 'get_groups', class_id: classID }, function(groups) {
                    $groupSelect.html('<option value="">-- اختر الفوج --</option>');
                    groups.forEach(group => {
                        $groupSelect.append(`<option value="${group.offering_id}">${group.group_name}</option>`);
                    });
                }, 'json');
            }
        });

        $('#enrollment-container').on('change', '.track-select', function() {
            const block = $(this).closest('.enrollment-block');
            const classID = block.find('.class-select').val();
            const trackID = $(this).val();
            const $groupSelect = block.find('.group-select');

            $groupSelect.html('<option value="">-- اختر --</option>');
            if (trackID) {
                $.post('ajax/get_data_for_signup.php', { action: 'get_groups', class_id: classID, track_id: trackID }, function(groups) {
                    $groupSelect.html('<option value="">-- اختر الفوج --</option>');
                    groups.forEach(group => {
                        $groupSelect.append(`<option value="${group.offering_id}">${group.group_name}</option>`);
                    });
                }, 'json');
            }
        });
    });
</script>
</body>
</html>