<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

try {
    // استعلام جديد لجلب المواد بناءً على الهيكل الصحيح
    $stmt = $pdo->query("
        SELECT 
            s.subject_id, s.subject_name,
            os.offering_subject_id,
            p.professor_name,
            d.division_name,
            c.class_name,
            g.group_name,
            t.track_name
        FROM offering_subjects os
        JOIN subjects s ON os.subject_id = s.subject_id
        JOIN course_offerings co ON os.offering_id = co.offering_id
        LEFT JOIN professors p ON os.professor_id = p.professor_id
        JOIN divisions d ON co.division_id = d.division_id
        JOIN classes c ON co.class_id = c.class_id
        JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        ORDER BY d.division_name, c.class_name, g.group_name, s.subject_name
    ");
    $all_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // إعادة هيكلة البيانات للعرض حسب الشعبة
    $structured_subjects = [];
    foreach ($all_subjects as $subject) {
        $division_key = $subject['division_name'];
        $offering_key = $subject['class_name'] . ' / ' . $subject['group_name'] . ($subject['track_name'] ? ' / ' . $subject['track_name'] : '');
        $structured_subjects[$division_key][$offering_key][] = $subject;
    }

    // جلب الشعب لنموذج الإضافة
    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) { 
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة المواد الدراسية</title>
                <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">

    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require_once '../templates/header.php'; ?>
<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-book"></i> إدارة المواد الدراسية</h2>
        <p>من هنا يمكنك إضافة المواد وربطها بالمقررات المتاحة التي قمت بتكوينها.</p>

        <?php
        if (isset($_GET['status'])) {
            if ($_GET['status'] == 'added') echo '<p class="status-message success">تمت إضافة المادة بنجاح!</p>';
            if ($_GET['status'] == 'deleted') echo '<p class="status-message success">تم حذف ربط المادة بنجاح.</p>';
            if ($_GET['status'] == 'updated') echo '<p class="status-message success">تم تحديث اسم المادة بنجاح.</p>';
        }
        if (isset($_GET['error'])) { echo '<p class="status-message error">' . htmlspecialchars($_GET['error']) . '</p>'; }
        ?>

        <div class="form-section">
            <h3>إضافة مادة جديدة لمقرر</h3>
            <form action="add_process.php" method="POST">
                <div class="filter-form">
                    <div class="form-group">
                        <label for="division">اختر الشعبة:</label>
                        <select id="division" name="division_id" required>
                            <option value="">-- اختر --</option>
                            <?php foreach ($divisions as $division): ?>
                                <option value="<?php echo $division['division_id']; ?>"><?php echo htmlspecialchars($division['division_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="offering">اختر المقرر المتاح:</label>
                        <select id="offering" name="offering_id" required disabled>
                            <option value="">-- اختر الشعبة أولاً --</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="subject_name">اسم المادة الجديدة:</label>
                    <input type="text" name="subject_name" id="subject_name" required>
                </div>
                <button type="submit"><i class="fa-solid fa-plus"></i> إضافة وربط المادة</button>
            </form>
        </div>

        <div class="table-section">
            <h3>المواد الحالية المسندة للمقررات</h3>
            <?php if (empty($structured_subjects)): ?>
                <p>لا توجد مواد مسندة حالياً. قم بتكوين المقررات أولاً ثم أضف المواد إليها.</p>
            <?php else: ?>
                <?php foreach ($structured_subjects as $division_name => $offerings_in_division): ?>
                    <h4 class="division-title"><?php echo htmlspecialchars($division_name); ?></h4>
                    <?php foreach ($offerings_in_division as $offering_key => $subjects): ?>
                        <h5 class="offering-title">المقرر: <?php echo htmlspecialchars($offering_key); ?></h5>
                        <table>
                            <thead>
                                <tr>
                                    <th>المادة</th>
                                    <th>الأستاذ</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjects as $subject): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['professor_name'] ?? 'غير معين'); ?></td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $subject['subject_id']; ?>" class="action-btn btn-edit" title="تعديل اسم المادة"><i class="fa-solid fa-pen"></i></a>
                                        <a href="delete_process.php?id=<?php echo $subject['offering_subject_id']; ?>" class="action-btn btn-delete" title="حذف ربط المادة بهذا المقرر" onclick="return confirm('هل أنت متأكد من حذف هذا الربط؟');"><i class="fa-solid fa-unlink"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../../assets/js/admin_script.js"></script>
</body>
</html>