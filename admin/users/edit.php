<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$user_id_to_edit = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$user_id_to_edit) {
    header("Location: index.php?error=معرف غير صحيح");
    exit();
}

try {
    // جلب بيانات المستخدم الأساسية
    $stmt = $pdo->prepare("SELECT user_id, username, email, role FROM users WHERE user_id = ?");
    $stmt->execute([$user_id_to_edit]);
    $user = $stmt->fetch();

    if (!$user) {
        header("Location: index.php?error=لم يتم العثور على المستخدم");
        exit();
    }

    // جلب التسجيلات الدراسية الحالية للمستخدم
    $stmt_enrollments = $pdo->prepare("
        SELECT 
            se.enrollment_id, d.division_name, c.class_name, g.group_name, t.track_name
        FROM student_enrollments se
        JOIN course_offerings co ON se.offering_id = co.offering_id
        JOIN divisions d ON co.division_id = d.division_id
        JOIN classes c ON co.class_id = c.class_id
        JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        WHERE se.user_id = ?
    ");
    $stmt_enrollments->execute([$user_id_to_edit]);
    $enrollments = $stmt_enrollments->fetchAll(PDO::FETCH_ASSOC);
    
    // جلب الشعب لنموذج الإضافة
    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name")->fetchAll();

} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تعديل مستخدم - <?php echo htmlspecialchars($user['username']); ?></title>
    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .form-section {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .track-group {
            display: none; /* إخفاء المسار مبدئياً */
        }
    </style>
</head>
<body>
<?php require_once '../templates/header.php'; ?>
<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-user-pen"></i> تعديل المستخدم: <?php echo htmlspecialchars($user['username']); ?></h2>
        <p><a href="index.php">&larr; العودة إلى قائمة المستخدمين</a></p>

        <?php if (isset($_SESSION['user_update_msg'])): ?>
            <p class="status-message success"><?php echo htmlspecialchars($_SESSION['user_update_msg']); ?></p>
            <?php unset($_SESSION['user_update_msg']); ?>
        <?php endif; ?>

        <div class="form-section">
            <h3><i class="fa-solid fa-id-card"></i> المعلومات الأساسية</h3>
            <form action="update_process.php" method="POST">
                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                <div class="form-group"><label>اسم المستخدم:</label><input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required></div>
                <div class="form-group"><label>البريد الإلكتروني:</label><input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required></div>
                <div class="form-group"><label>الدور:</label><select name="role" required><option value="student" <?php if ($user['role'] === 'student') echo 'selected'; ?>>طالب</option><option value="admin" <?php if ($user['role'] === 'admin') echo 'selected'; ?>>أدمن</option></select></div>
                <button type="submit"><i class="fa-solid fa-save"></i> حفظ التعديلات</button>
            </form>
        </div>
        
        <div class="form-section">
            <h3><i class="fa-solid fa-graduation-cap"></i> إدارة التسجيلات الدراسية</h3>
            <h4>التسجيلات الحالية:</h4>
            <?php if (empty($enrollments)): ?>
                <p>هذا المستخدم غير مسجل في أي مقرر حالياً.</p>
            <?php else: ?>
                <table>
                    <thead><tr><th>المقرر الدراسي</th><th>إجراء</th></tr></thead>
                    <tbody>
                        <?php foreach($enrollments as $enroll): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($enroll['division_name'] . ' > ' . $enroll['class_name'] . ' / ' . $enroll['group_name'] . ($enroll['track_name'] ? ' / ' . $enroll['track_name'] : '')); ?></td>
                            <td>
                                <form action="delete_enrollment.php" method="POST" onsubmit="return confirm('هل أنت متأكد؟');">
                                    <input type="hidden" name="enrollment_id" value="<?php echo $enroll['enrollment_id']; ?>">
                                    <input type="hidden" name="user_id" value="<?php echo $user_id_to_edit; ?>">
                                    <button type="submit" class="action-btn btn-delete"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <hr>
            
            <h4 style="margin-top: 20px;">إضافة تسجيل جديد:</h4>
            <form id="add-enrollment-form" action="add_enrollment.php" method="POST">
                <input type="hidden" name="user_id" value="<?php echo $user_id_to_edit; ?>">
                <div class="filter-form">
                    <div class="form-group">
                        <label>الشعبة:</label>
                        <select id="division" required>
                            <option value="">-- اختر --</option>
                            <?php foreach ($divisions as $div): ?>
                                <option value="<?php echo $div['division_id']; ?>"><?php echo htmlspecialchars($div['division_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>الفصل:</label>
                        <select id="class" class="class-select" required disabled></select>
                    </div>
                    <div class="form-group track-group">
                        <label>المسار:</label>
                        <select id="track" class="track-select" disabled></select>
                    </div>
                    <div class="form-group">
                        <label>الفوج:</label>
                        <select id="group" name="offering_id" class="group-select" required disabled></select>
                    </div>
                    <button type="submit"><i class="fa-solid fa-plus"></i> إضافة التسجيل</button>
                </div>
            </form>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        const ADVANCED_CLASSES_IDS = [5, 6];

        // --- المنطق الخاص بنموذج إضافة التسجيل ---
        const $form = $('#add-enrollment-form');
        const $divisionSelect = $form.find('#division');
        const $classSelect = $form.find('.class-select');
        const $trackGroup = $form.find('.track-group');
        const $trackSelect = $form.find('.track-select');
        const $groupSelect = $form.find('.group-select');

        $divisionSelect.on('change', function() {
            const divisionID = $(this).val();
            $classSelect.html('<option>...</option>').prop('disabled', true);
            $trackGroup.hide();
            $trackSelect.html('').prop('disabled', true);
            $groupSelect.html('').prop('disabled', true);

            if (divisionID) {
                $.post('../../ajax/get_data_for_settings.php', { action: 'get_classes', division_id: divisionID }, function(classes) {
                    $classSelect.html('<option value="">-- اختر الفصل --</option>');
                    classes.forEach(cls => {
                        $classSelect.append(`<option value="${cls.class_id}">${cls.class_name}</option>`);
                    });
                    $classSelect.prop('disabled', false);
                }, 'json');
            }
        });

        $classSelect.on('change', function() {
            const classID = parseInt($(this).val());
            const divisionID = $divisionSelect.val();
            $trackGroup.hide();
            $trackSelect.html('').prop('required', false).prop('disabled', true);
            $groupSelect.html('<option>...</option>').prop('disabled', true);

            if (ADVANCED_CLASSES_IDS.includes(classID)) {
                $trackGroup.show();
                $trackSelect.prop('required', true).prop('disabled', false);
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
                    $groupSelect.prop('disabled', false);
                }, 'json');
            }
        });

        $trackSelect.on('change', function() {
            const classID = $classSelect.val();
            const trackID = $(this).val();
            $groupSelect.html('<option>...</option>').prop('disabled', true);

            if (trackID) {
                 $.post('../../ajax/get_data_for_settings.php', { action: 'get_groups', class_id: classID, track_id: trackID }, function(groups) {
                    $groupSelect.html('<option value="">-- اختر الفوج --</option>');
                    groups.forEach(group => {
                        $groupSelect.append(`<option value="${group.offering_id}">${group.group_name}</option>`);
                    });
                    $groupSelect.prop('disabled', false);
                }, 'json');
            }
        });
    });
</script>

</body>
</html>