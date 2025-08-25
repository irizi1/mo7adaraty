<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

try {
    // جلب بيانات المستخدم
    $stmt_user = $pdo->prepare("SELECT username, email FROM users WHERE user_id = ?");
    $stmt_user->execute([$user_id]);
    $user = $stmt_user->fetch();

    // جلب الشعب لنموذج طلب التغيير
    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name")->fetchAll();

} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>إعدادات الملف الشخصي - Mo7adaraty</title>
          <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">

  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
  <style>
    body {
        background-color: #f0f4f8;
        font-family: 'Tajawal', sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
        padding: 20px 0;
    }
    .form-container {
        background-color: #ffffff;
        padding: 30px 40px;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 600px;
        border-top: 5px solid #007bff;
    }
    .form-container h2 { text-align: center; color: #333; margin-top: 0; margin-bottom: 30px; }
    .form-section { margin-bottom: 30px; border-bottom: 1px solid #e9ecef; padding-bottom: 20px; }
    .form-section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .form-section h3 { font-size: 18px; color: #007bff; margin-bottom: 20px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media (max-width: 600px) { .grid-2 { grid-template-columns: 1fr; } }
    .enrollment-block { border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
    .enrollment-block h4 { margin-top: 0; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
    .track-group { display: none; }
    #add-enrollment-btn { width: auto; background-color: #28a745; margin-top: 10px; }
    .back-link { display: block; text-align: center; margin-top: 20px; color: #007bff; font-weight: bold; text-decoration: none; }
  </style>
</head>
<body>

<div class="form-container">
    <h2><i class="fa-solid fa-cogs"></i> إعدادات الملف الشخصي</h2>

    <?php
    if (isset($_SESSION['settings_msg'])) {
        $msg_type = $_SESSION['settings_msg']['type'];
        $message = $_SESSION['settings_msg']['message'];
        echo "<p class='status-message $msg_type'>$message</p>";
        unset($_SESSION['settings_msg']);
    }
    ?>

    <div class="form-section">
        <h3>تعديل المعلومات الأساسية</h3>
        <form action="update_profile_process.php" method="POST" enctype="multipart/form-data">
             <div class="grid-2">
                <div class="form-group"><label for="username">اسم المستخدم:</label><input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required></div>
                <div class="form-group"><label for="email">البريد الإلكتروني:</label><input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required></div>
            </div>
            <div class="form-group"><label>الصورة الشخصية (اختياري):</label><input type="file" name="profile_picture" accept="image/*"></div>
            <hr style="border: 1px solid #f1f1f1; margin: 20px 0;">
            <div class="grid-2">
                <div class="form-group"><label>كلمة المرور الحالية:</label><input type="password" name="current_password"></div>
                <div class="form-group"><label>كلمة المرور الجديدة:</label><input type="password" name="new_password"></div>
            </div>
            <button type="submit" name="update_info"><i class="fa-solid fa-save"></i> حفظ التعديلات</button>
        </form>
    </div>

    <div class="form-section">
        <h3>طلب تغيير المقرر الدراسي</h3>
        <form id="enrollmentChangeForm" action="request_enrollment_change_process.php" method="POST">
             <div class="form-group">
                <label for="division">اختر الشعبة:</label>
                <select id="division" name="division_id" required>
                    <option value="" selected disabled>-- اختر الشعبة لعرض الفصول --</option>
                    <?php foreach ($divisions as $division): ?>
                        <option value="<?php echo $division['division_id']; ?>"><?php echo htmlspecialchars($division['division_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="enrollment-container">
                </div>
            
            <button type="button" id="add-enrollment-btn" style="display:none;">+ إضافة مقرر آخر</button>
            <hr style="border: 1px solid #f1f1f1; margin: 20px 0;">
            <div class="form-group">
              <label for="reason">سبب الطلب (اختياري):</label>
              <textarea id="reason" name="reason" rows="3"></textarea>
            </div>
            <button type="submit" name="request_change"><i class="fa-solid fa-paper-plane"></i> إرسال الطلب</button>
        </form>
    </div>

    <a href="../profil.php" class="back-link">&larr; العودة إلى الملف الشخصي</a>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        let enrollmentCounter = 0;
        const ADVANCED_CLASSES_IDS = [5, 6];

        function addEnrollmentBlock() {
            if (enrollmentCounter >= 2) return;
            enrollmentCounter++;
            const blockId = `enrollment-block-${enrollmentCounter}`;

            const enrollmentHtml = `
                <div class="enrollment-block" id="${blockId}">
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

            $.post('../../ajax/get_data_for_settings.php', { action: 'get_classes', division_id: divisionID }, function(classes) {
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
                $.post('../../ajax/get_data_for_settings.php', { action: 'get_tracks', division_id: divisionID }, function(tracks) {
                     $trackSelect.html('<option value="">-- اختر المسار --</option>');
                    tracks.forEach(track => {
                        $trackSelect.append(`<option value="${track.track_id}">${track.track_name}</option>`);
                    });
                }, 'json');
            } else {
                $.post('../../ajax/get_data_for_settings.php', { action: 'get_groups', class_id: classID }, function(groups) {
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
                $.post('../../ajax/get_data_for_settings.php', { action: 'get_groups', class_id: classID, track_id: trackID }, function(groups) {
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