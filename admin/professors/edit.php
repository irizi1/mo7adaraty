<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة والتحقق من الصلاحيات
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php?error=وصول غير مصرح به");
    exit();
}

// استقبال معرف الأستاذ ومعرف الربط
$professor_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$group_subject_id = filter_input(INPUT_GET, 'group_subject_id', FILTER_VALIDATE_INT);
if (!$professor_id || !$group_subject_id) {
    header("Location: index.php?error=معرف غير صحيح");
    exit();
}

try {
    // جلب بيانات الأستاذ وربطه
    $stmt = $pdo->prepare("
        SELECT 
            p.professor_id, p.professor_name,
            gs.group_subject_id, gs.group_id, gs.subject_id,
            g.class_id, c.track_id, t.division_id
        FROM professors p
        JOIN group_subjects gs ON p.professor_id = gs.professor_id
        JOIN `groups` g ON gs.group_id = g.group_id
        JOIN classes c ON g.class_id = c.class_id
        JOIN tracks t ON c.track_id = t.track_id
        WHERE p.professor_id = ? AND gs.group_subject_id = ?
    ");
    $stmt->execute([$professor_id, $group_subject_id]);
    $professor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$professor) {
        header("Location: index.php?error=لم يتم العثور على الأستاذ أو الربط");
        exit();
    }

    // جلب الشعب
    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name")->fetchAll(PDO::FETCH_ASSOC);

    // جلب المسارات للشعبة الحالية
    $stmt = $pdo->prepare("SELECT track_id, track_name FROM tracks WHERE division_id = ? ORDER BY track_name");
    $stmt->execute([$professor['division_id']]);
    $tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // جلب الفصول للمسار الحالي
    $stmt = $pdo->prepare("SELECT class_id, class_name FROM classes WHERE track_id = ? ORDER BY class_name");
    $stmt->execute([$professor['track_id']]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // جلب الأفواج للفصل الحالي
    $stmt = $pdo->prepare("SELECT group_id, group_name FROM `groups` WHERE class_id = ? ORDER BY group_name");
    $stmt->execute([$professor['class_id']]);
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // جلب المواد للفوج الحالي
    $stmt = $pdo->prepare("
        SELECT s.subject_id, s.subject_name 
        FROM group_subjects gs 
        JOIN subjects s ON gs.subject_id = s.subject_id 
        WHERE gs.group_id = ? 
        ORDER BY s.subject_name
    ");
    $stmt->execute([$professor['group_id']]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>تعديل أستاذ - لوحة التحكم</title>
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
        <h2><i class="fa-solid fa-pen-to-square"></i> تعديل بيانات الأستاذ: <?php echo htmlspecialchars($professor['professor_name']); ?></h2>
        <p><a href="index.php">&larr; العودة إلى قائمة الأساتذة</a></p>

        <?php
        if (isset($_GET['error'])) {
            echo '<p class="status-message error">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        ?>

        <div class="form-section">
            <form action="update_process.php" method="POST">
                <input type="hidden" name="professor_id" value="<?php echo $professor['professor_id']; ?>">
                <input type="hidden" name="group_subject_id" value="<?php echo $professor['group_subject_id']; ?>">
                
                <div class="form-group">
                    <label for="division_id">اختر الشعبة:</label>
                    <select name="division_id" id="division_id" required>
                        <option value="" disabled>-- اختر الشعبة --</option>
                        <?php foreach ($divisions as $division): ?>
                            <option value="<?php echo $division['division_id']; ?>" <?php echo $division['division_id'] == $professor['division_id'] ? 'selected' : ''; ?>>
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
                            <option value="<?php echo $track['track_id']; ?>" <?php echo $track['track_id'] == $professor['track_id'] ? 'selected' : ''; ?>>
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
                            <option value="<?php echo $class['class_id']; ?>" <?php echo $class['class_id'] == $professor['class_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="class-error" class="error-message"></div>
                </div>
                <div class="form-group">
                    <label for="group_id">اختر الفوج:</label>
                    <select name="group_id" id="group_id" required>
                        <option value="" disabled>-- اختر الفوج --</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?php echo $group['group_id']; ?>" <?php echo $group['group_id'] == $professor['group_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($group['group_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="group-error" class="error-message"></div>
                </div>
                <div class="form-group">
                    <label for="subject_id">اختر المادة:</label>
                    <select name="subject_id" id="subject_id" required>
                        <option value="" disabled>-- اختر المادة --</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['subject_id']; ?>" <?php echo $subject['subject_id'] == $professor['subject_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="subject-error" class="error-message"></div>
                </div>
                <div class="form-group">
                    <label for="professor_name">اسم الأستاذ الكامل:</label>
                    <input type="text" id="professor_name" name="professor_name" value="<?php echo htmlspecialchars($professor['professor_name']); ?>" required>
                </div>
                <button type="submit"><i class="fa-solid fa-save"></i> حفظ التعديلات</button>
            </form>
        </div>
    </main>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#division_id').change(function() {
        var division_id = $(this).val();
        $('#track_id').prop('disabled', true).html('<option value="" disabled selected>-- جارٍ التحميل... --</option>');
        $('#class_id').prop('disabled', true).html('<option value="" disabled selected>-- اختر المسار أولاً --</option>');
        $('#group_id').prop('disabled', true).html('<option value="" disabled selected>-- اختر الفصل أولاً --</option>');
        $('#subject_id').prop('disabled', true).html('<option value="" disabled selected>-- اختر الفوج أولاً --</option>');
        $('#division-error').hide();
        $('#track-error').hide();
        $('#class-error').hide();
        $('#group-error').hide();
        $('#subject-error').hide();

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
                        $('#track-error').text('لا توجد مسارات متاحة لهذه الشعبة.').show();
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
                    $('#track-error').text('خطأ في الاتصال: ' + status).show();
                    $('#track_id').html('<option value="" disabled selected>-- حدث خطأ --</option>');
                    console.log('AJAX Error: ' + status + ' - ' + error);
                }
            });
        } else {
            $('#track_id').html('<option value="" disabled selected>-- اختر الشعبة أولاً --</option>');
        }
    });

    $('#track_id').change(function() {
        var track_id = $(this).val();
        $('#class_id').prop('disabled', true).html('<option value="" disabled selected>-- جارٍ التحميل... --</option>');
        $('#group_id').prop('disabled', true).html('<option value="" disabled selected>-- اختر الفصل أولاً --</option>');
        $('#subject_id').prop('disabled', true).html('<option value="" disabled selected>-- اختر الفوج أولاً --</option>');
        $('#class-error').hide();
        $('#group-error').hide();
        $('#subject-error').hide();

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
                        $('#class-error').text('لا توجد فصول متاحة لهذا المسار.').show();
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
                    $('#class-error').text('خطأ في الاتصال: ' + status).show();
                    $('#class_id').html('<option value="" disabled selected>-- حدث خطأ --</option>');
                    console.log('AJAX Error: ' + status + ' - ' + error);
                }
            });
        } else {
            $('#class_id').html('<option value="" disabled selected>-- اختر المسار أولاً --</option>');
        }
    });

    $('#class_id').change(function() {
        var class_id = $(this).val();
        $('#group_id').prop('disabled', true).html('<option value="" disabled selected>-- جارٍ التحميل... --</option>');
        $('#subject_id').prop('disabled', true).html('<option value="" disabled selected>-- اختر الفوج أولاً --</option>');
        $('#group-error').hide();
        $('#subject-error').hide();

        if (class_id) {
            $.ajax({
                url: './get_groups.php',
                type: 'POST',
                data: { class_id: class_id },
                dataType: 'json',
                success: function(data) {
                    if (data.error) {
                        $('#group-error').text(data.error).show();
                        $('#group_id').html('<option value="" disabled selected>-- اختر الفوج --</option>');
                    } else if (data.length === 0) {
                        $('#group-error').text('لا توجد أفواج متاحة لهذا الفصل.').show();
                        $('#group_id').html('<option value="" disabled selected>-- لا توجد أفواج --</option>');
                    } else {
                        var options = '<option value="" disabled selected>-- اختر الفوج --</option>';
                        $.each(data, function(index, group) {
                            options += '<option value="' + group.group_id + '">' + group.group_name + '</option>';
                        });
                        $('#group_id').html(options).prop('disabled', false);
                        $('#group-error').hide();
                    }
                },
                error: function(xhr, status, error) {
                    $('#group-error').text('خطأ في الاتصال: ' + status).show();
                    $('#group_id').html('<option value="" disabled selected>-- حدث خطأ --</option>');
                    console.log('AJAX Error: ' + status + ' - ' + error);
                }
            });
        } else {
            $('#group_id').html('<option value="" disabled selected>-- اختر الفصل أولاً --</option>');
        }
    });

    $('#group_id').change(function() {
        var group_id = $(this).val();
        $('#subject_id').prop('disabled', true).html('<option value="" disabled selected>-- جارٍ التحميل... --</option>');
        $('#subject-error').hide();

        if (group_id) {
            $.ajax({
                url: './get_subjects.php',
                type: 'POST',
                data: { group_id: group_id },
                dataType: 'json',
                success: function(data) {
                    if (data.error) {
                        $('#subject-error').text(data.error).show();
                        $('#subject_id').html('<option value="" disabled selected>-- اختر المادة --</option>');
                    } else if (data.length === 0) {
                        $('#subject-error').text('لا توجد مواد متاحة لهذا الفوج.').show();
                        $('#subject_id').html('<option value="" disabled selected>-- لا توجد مواد --</option>');
                    } else {
                        var options = '<option value="" disabled selected>-- اختر المادة --</option>';
                        $.each(data, function(index, subject) {
                            options += '<option value="' + subject.subject_id + '">' + subject.subject_name + '</option>';
                        });
                        $('#subject_id').html(options).prop('disabled', false);
                        $('#subject-error').hide();
                    }
                },
                error: function(xhr, status, error) {
                    $('#subject-error').text('خطأ في الاتصال: ' + status).show();
                    $('#subject_id').html('<option value="" disabled selected>-- حدث خطأ --</option>');
                    console.log('AJAX Error: ' + status + ' - ' + error);
                }
            });
        } else {
            $('#subject_id').html('<option value="" disabled selected>-- اختر الفوج أولاً --</option>');
        }
    });
});
</script>
</body>
</html>