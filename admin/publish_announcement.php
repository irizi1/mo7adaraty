<?php
session_start();
require_once '../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=ليس لديك صلاحية الوصول لهذه الصفحة");
    exit();
}

$status_message = '';
$error_message = '';

// جلب البيانات للقوائم المنسدلة
try {
    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $divisions = [];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $admin_user_id = $_SESSION['user_id'];
    
    // استقبال بيانات الاستهداف
    $division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT) ?: null;
    $class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT) ?: null;
    $group_id = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT) ?: null;

    if (empty($title) || empty($content)) {
        $error_message = "الرجاء ملء حقل العنوان والمحتوى.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. إضافة الإعلان إلى قاعدة البيانات
            $stmt_insert = $pdo->prepare("INSERT INTO global_announcements (admin_user_id, title, content) VALUES (?, ?, ?)");
            $stmt_insert->execute([$admin_user_id, $title, $content]);

            // 2. بناء استعلام جلب الطلاب بناءً على الاستهداف
            $sql_students = "SELECT DISTINCT se.user_id FROM student_enrollments se JOIN course_offerings co ON se.offering_id = co.offering_id";
            $conditions = [];
            $params = [];

            if ($division_id) {
                $conditions[] = "co.division_id = ?";
                $params[] = $division_id;
            }
            if ($class_id) {
                $conditions[] = "co.class_id = ?";
                $params[] = $class_id;
            }
            if ($group_id) {
                $conditions[] = "co.group_id = ?";
                $params[] = $group_id;
            }

            if (!empty($conditions)) {
                $sql_students .= " WHERE " . implode(' AND ', $conditions);
            } else {
                // إذا لم يتم تحديد أي فلتر، أرسل لجميع الطلاب
                $sql_students = "SELECT user_id FROM users WHERE role = 'student'";
            }

            $stmt_students = $pdo->prepare($sql_students);
            $stmt_students->execute($params);
            $students = $stmt_students->fetchAll(PDO::FETCH_COLUMN);

            // 3. إرسال الإشعارات للطلاب المستهدفين
            if (count($students) > 0) {
                $notification_message = "إعلان جديد من الإدارة: " . $title;
                $notification_link = "student/announcements.php"; 
                
                $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
                foreach ($students as $student_id) {
                    $stmt_notify->execute([$student_id, $notification_message, $notification_link]);
                }
            }

            $pdo->commit();
            $status_message = "تم نشر الإعلان بنجاح لـ " . count($students) . " طالب.";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "حدث خطأ أثناء نشر الإعلان: " . $e->getMessage();
        }
    }
}

// جلب آخر 5 إعلانات لعرضها
try {
    $recent_announcements = $pdo->query("SELECT * FROM global_announcements ORDER BY created_at DESC LIMIT 5")->fetchAll();
} catch (PDOException $e) {
    $recent_announcements = [];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>نشر إعلان - لوحة التحكم</title>
       <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require_once 'templates/header.php'; ?>
<div class="admin-container">
    <?php require_once 'templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-bullhorn"></i> نشر إعلان</h2>

        <?php
        if ($status_message) echo '<p class="status-message success">' . $status_message . '</p>';
        if ($error_message) echo '<p class="status-message error">' . $error_message . '</p>';
        ?>

        <div class="form-section">
            <h3>إنشاء إعلان جديد</h3>
            <p>اختر الجمهور المستهدف أو اترك الحقول فارغة لإرسال الإعلان للجميع.</p>
            <form action="publish_announcement.php" method="POST">
                <div class="form-group">
                    <label for="title">عنوان الإعلان:</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="content">محتوى الإعلان:</label>
                    <textarea id="content" name="content" rows="8" required></textarea>
                </div>
                
                <fieldset style="border: 1px solid #ddd; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <legend>تحديد الجمهور (اختياري)</legend>
                    <div class="filter-form">
                        <div class="form-group">
                            <label for="division">الشعبة:</label>
                            <select id="division" name="division_id">
                                <option value="">-- كل الشعب --</option>
                                <?php foreach ($divisions as $div): ?>
                                    <option value="<?php echo $div['division_id']; ?>"><?php echo htmlspecialchars($div['division_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="class">الفصل:</label>
                            <select id="class" name="class_id" disabled>
                                <option value="">-- كل الفصول --</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="group">الفوج:</label>
                            <select id="group" name="group_id" disabled>
                                <option value="">-- كل الأفواج --</option>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <button type="submit">نشر الإعلان</button>
            </form>
        </div>

        <div class="table-section">
            <h3>آخر الإعلانات المنشورة</h3>
            <table>
                <thead><tr><th>العنوان</th><th>تاريخ النشر</th></tr></thead>
                <tbody>
                    <?php if (count($recent_announcements) > 0): ?>
                        <?php foreach ($recent_announcements as $ann): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ann['title']); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($ann['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2">لم يتم نشر أي إعلانات بعد.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#division').on('change', function() {
        var divisionId = $(this).val();
        var classSelect = $('#class');
        var groupSelect = $('#group');

        classSelect.html('<option value="">-- كل الفصول --</option>').prop('disabled', true);
        groupSelect.html('<option value="">-- كل الأفواج --</option>').prop('disabled', true);

        if (divisionId) {
            classSelect.prop('disabled', false).html('<option value="">جارٍ التحميل...</option>');
            $.post('../ajax/get_data_for_settings.php', { action: 'get_classes', division_id: divisionId }, function(classes) {
                classSelect.html('<option value="">-- كل الفصول --</option>');
                classes.forEach(function(cls) {
                    classSelect.append(`<option value="${cls.class_id}">${cls.class_name}</option>`);
                });
            }, 'json');
        }
    });
    
    $('#class').on('change', function() {
        var classId = $(this).val();
        var groupSelect = $('#group');
        groupSelect.html('<option value="">-- كل الأفواج --</option>').prop('disabled', true);

        if (classId) {
            groupSelect.prop('disabled', false).html('<option value="">جارٍ التحميل...</option>');
            $.post('../ajax/get_data_for_settings.php', { action: 'get_groups', class_id: classId }, function(groups) {
                groupSelect.html('<option value="">-- كل الأفواج --</option>');
                 groups.forEach(function(group) {
                    groupSelect.append(`<option value="${group.offering_id}">${group.group_name}</option>`);
                });
            }, 'json');
        }
    });
});
</script>
</body>
</html>