<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة والتأكد من أن المستخدم هو أدمن
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php"); 
    exit();
}

// التأكد من وجود معرف الفوج
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: index.php?error=معرف الفوج غير صالح");
    exit();
}

$group_id = $_GET['id'];

try {
    // جلب بيانات الفوج المحدد مع المسار والشعبة
    $stmt = $pdo->prepare("
        SELECT g.group_id, g.group_name, g.class_id, c.track_id, t.division_id
        FROM `groups` g
        JOIN classes c ON g.class_id = c.class_id
        JOIN tracks t ON c.track_id = t.track_id
        WHERE g.group_id = ?
    ");
    $stmt->execute([$group_id]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$group) {
        header("Location: index.php?error=الفوج غير موجود");
        exit();
    }

    // جلب الشعب
    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name")->fetchAll(PDO::FETCH_ASSOC);

    // جلب المسارات للشعبة الحالية
    $stmt = $pdo->prepare("SELECT track_id, track_name FROM tracks WHERE division_id = ? ORDER BY track_name");
    $stmt->execute([$group['division_id']]);
    $tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // جلب الفصول للمسار الحالي
    $stmt = $pdo->prepare("SELECT class_id, class_name FROM classes WHERE track_id = ? ORDER BY class_name");
    $stmt->execute([$group['track_id']]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("خطأ في جلب البيانات: " . $e->getMessage());
    header("Location: index.php?error=خطأ في جلب البيانات");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل الفوج</title>
    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .form-group select, .form-group input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .form-group select:disabled {
            background-color: #f0f0f0;
            cursor: not-allowed;
        }
        .error-message {
            color: red;
            font-size: 12px;
            margin-top: 5px;
            display: none;
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
    </style>
</head>
<body>
<?php require_once '../templates/header.php'; ?>
<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-users-rectangle"></i> تعديل الفوج</h2>
        
        <?php
        if (isset($_GET['error'])) {
            echo '<p class="status-message error">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        ?>

        <div class="form-section">
            <h3>تعديل الفوج: <?php echo htmlspecialchars($group['group_name']); ?></h3>
            <form action="update_process.php" method="POST">
                <input type="hidden" name="group_id" value="<?php echo $group['group_id']; ?>">
                <div class="form-group">
                    <label for="division_id">اختر الشعبة:</label>
                    <select name="division_id" id="division_id" required>
                        <option value="" disabled>-- اختر الشعبة --</option>
                        <?php foreach ($divisions as $division): ?>
                            <option value="<?php echo $division['division_id']; ?>" <?php echo $division['division_id'] == $group['division_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($division['division_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="division-error" class="error-message"></div>
                </div>
                <div class="form-group">
                    <label for="track_id">اختر المسار:</label>
                    <select name="track_id" id="track_id" required>
                        <option value="" disabled>-- اختر المسار --</option>
                        <?php foreach ($tracks as $track): ?>
                            <option value="<?php echo $track['track_id']; ?>" <?php echo $track['track_id'] == $group['track_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($track['track_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="track-error" class="error-message"></div>
                </div>
                <div class="form-group">
                    <label for="class_id">اختر الفصل:</label>
                    <select name="class_id" id="class_id" required>
                        <option value="" disabled>-- اختر الفصل --</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['class_id']; ?>" <?php echo $class['class_id'] == $group['class_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="class-error" class="error-message"></div>
                </div>
                <div class="form-group">
                    <label for="group_name">اسم الفوج:</label>
                    <input type="text" name="group_name" id="group_name" value="<?php echo htmlspecialchars($group['group_name']); ?>" required>
                </div>
                <button type="submit"><i class="fa-solid fa-save"></i> حفظ التعديلات</button>
            </form>
        </div>
    </main>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // عند تغيير الشعبة، جلب المسارات
    $('#division_id').change(function() {
        var division_id = $(this).val();
        $('#track_id').prop('disabled', true).html('<option value="" disabled selected>-- جارٍ التحميل... --</option>');
        $('#class_id').prop('disabled', true).html('<option value="" disabled selected>-- اختر المسار أولاً --</option>');
        $('#division-error').hide();
        $('#track-error').hide();
        $('#class-error').hide();

        if (division_id) {
            $.ajax({
                url: './get_tracks.php',
                type: 'POST',
                data: { division_id: division_id },
                dataType: 'json',
                success: function(data) {
                    if (data.error) {
                        $('#track-error').text(data.error).show();
                        $('#track_id').html('<option value="" disabled selected>-- اختر المسار --</option>');
                    } else if (data.length === 0) {
                        $('#track-error').text('لا توجد مسارات متاحة لهذه الشعبة. أضف مسارات في قسم إدارة المسارات.').show();
                        $('#track_id').html('<option value="" disabled selected>-- لا توجد مسارات --</option>');
                    } else {
                        var options = '<option value="" disabled selected>-- اختر المسار --</option>';
                        $.each(data, function(index, track) {
                            options += '<option value="' + track.track_id + '">' + track.track_name + '</option>';
                        });
                        $('#track_id').html(options).prop('disabled', false);
                        $('#track-error').hide();
                    }
                },
                error: function(xhr, status, error) {
                    $('#track-error').text('خطأ في الاتصال بالخادم: ' + status + '. تحقق من الكونسول للتفاصيل.').show();
                    $('#track_id').html('<option value="" disabled selected>-- حدث خطأ --</option>');
                    console.log('AJAX Error: ' + status + ' - ' + error);
                    console.log('Response: ' + xhr.responseText);
                }
            });
        } else {
            $('#track_id').html('<option value="" disabled selected>-- اختر الشعبة أولاً --</option>');
        }
    });

    // عند تغيير المسار، جلب الفصول
    $('#track_id').change(function() {
        var track_id = $(this).val();
        $('#class_id').prop('disabled', true).html('<option value="" disabled selected>-- جارٍ التحميل... --</option>');
        $('#class-error').hide();

        if (track_id) {
            $.ajax({
                url: './get_classes.php',
                type: 'POST',
                data: { track_id: track_id },
                dataType: 'json',
                success: function(data) {
                    if (data.error) {
                        $('#class-error').text(data.error).show();
                        $('#class_id').html('<option value="" disabled selected>-- اختر الفصل --</option>');
                    } else if (data.length === 0) {
                        $('#class-error').text('لا توجد فصول متاحة لهذا المسار. أضف فصول في قسم إدارة الفصول.').show();
                        $('#class_id').html('<option value="" disabled selected>-- لا توجد فصول --</option>');
                    } else {
                        var options = '<option value="" disabled selected>-- اختر الفصل --</option>';
                        $.each(data, function(index, class_item) {
                            options += '<option value="' + class_item.class_id + '">' + class_item.class_name + '</option>';
                        });
                        $('#class_id').html(options).prop('disabled', false);
                        $('#class-error').hide();
                    }
                },
                error: function(xhr, status, error) {
                    $('#class-error').text('خطأ في الاتصال بالخادم: ' + status + '. تحقق من الكونسول للتفاصيل.').show();
                    $('#class_id').html('<option value="" disabled selected>-- حدث خطأ --</option>');
                    console.log('AJAX Error: ' + status + ' - ' + error);
                    console.log('Response: ' + xhr.responseText);
                }
            });
        } else {
            $('#class_id').html('<option value="" disabled selected>-- اختر المسار أولاً --</option>');
        }
    });
});
</script>
</body>
</html>