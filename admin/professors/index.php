<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

try {
    // 1. جلب كل الأساتذة المعينين مع كامل تفاصيل المقررات
    $stmt = $pdo->query("
        SELECT 
            p.professor_id, p.professor_name,
            s.subject_name,
            os.offering_subject_id,
            d.division_name,
            c.class_name,
            g.group_name,
            t.track_name
        FROM professors p
        JOIN offering_subjects os ON p.professor_id = os.professor_id
        JOIN subjects s ON os.subject_id = s.subject_id
        JOIN course_offerings co ON os.offering_id = co.offering_id
        JOIN divisions d ON co.division_id = d.division_id
        JOIN classes c ON co.class_id = c.class_id
        JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        ORDER BY d.division_name, c.class_id, g.group_id, t.track_name, s.subject_name
    ");
    $all_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. إعادة هيكلة البيانات لتجميعها بشكل هرمي
    $structured_assignments = [];
    foreach ($all_assignments as $assignment) {
        $division_key = $assignment['division_name'];
        $offering_key = $assignment['class_name'] . ' / ' . $assignment['group_name'] . ($assignment['track_name'] ? ' / ' . $assignment['track_name'] : '');
        
        $structured_assignments[$division_key][$offering_key][] = $assignment;
    }

    // 3. جلب البيانات اللازمة لنموذج الإضافة
    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) { 
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأساتذة</title>
               <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">

    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .division-block { margin-bottom: 25px; padding: 20px; background: #fff; border-radius: 8px; border: 1px solid #e9ecef; }
        .division-title { font-size: 22px; color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-top: 0; }
        .offering-group { margin-top: 20px; padding-right: 15px; border-right: 3px solid #f1f1f1; }
        .offering-title { font-size: 18px; color: #343a40; margin-bottom: 15px; }
    </style>
</head>
<body>
<?php require_once '../templates/header.php'; ?>
<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-user-tie"></i> إدارة الأساتذة</h2>
        <p>من هنا يمكنك إضافة أساتذة جدد وتعيينهم للمواد داخل المقررات المتاحة.</p>

        <?php
        if (isset($_SESSION['settings_msg'])) {
            $msg_type = $_SESSION['settings_msg']['type'];
            $message = $_SESSION['settings_msg']['message'];
            echo "<p class='status-message $msg_type'>$message</p>";
            unset($_SESSION['settings_msg']);
        }
        ?>

        <div class="form-section">
            <h3>تعيين أستاذ لمادة</h3>
            <form action="add_process.php" method="POST" data-context="professors">
                <div class="filter-form">
                    <div class="form-group">
                        <label for="division">الشعبة:</label>
                        <select id="division" name="division_id" required>
                            <option value="">-- اختر --</option>
                            <?php foreach ($divisions as $division): ?>
                                <option value="<?php echo $division['division_id']; ?>"><?php echo htmlspecialchars($division['division_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="offering">المقرر المتاح:</label>
                        <select id="offering" name="offering_id" required disabled></select>
                    </div>
                    <div class="form-group">
                        <label for="subject">المادة:</label>
                        <select id="subject" name="offering_subject_id" required disabled></select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="professor_name">اسم الأستاذ:</label>
                    <input type="text" name="professor_name" id="professor_name" required>
                </div>
                <button type="submit"><i class="fa-solid fa-plus"></i> إضافة وتعيين الأستاذ</button>
            </form>
        </div>

        <div class="table-section">
            <h3>الأساتذة المعينون حالياً</h3>
            <?php if (empty($structured_assignments)): ?>
                <p>لا يوجد أساتذة معينون للمواد حالياً.</p>
            <?php else: ?>
                <?php foreach ($structured_assignments as $division_name => $offerings_in_division): ?>
                    <div class="division-block">
                        <h3 class="division-title"><?php echo htmlspecialchars($division_name); ?></h3>
                        <?php foreach ($offerings_in_division as $offering_key => $assignments): ?>
                            <div class="offering-group">
                                <h4 class="offering-title">المقرر: <?php echo htmlspecialchars($offering_key); ?></h4>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>المادة</th>
                                            <th>الأستاذ</th>
                                            <th>إجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assignments as $prof): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($prof['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($prof['professor_name']); ?></td>
                                            <td>
                                                <a href="delete_process.php?id=<?php echo $prof['offering_subject_id']; ?>" class="action-btn btn-delete" title="إلغاء تعيين الأستاذ" onclick="return confirm('هل أنت متأكد من إلغاء تعيين هذا الأستاذ من المادة؟');"><i class="fa-solid fa-unlink"></i></a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="professors_script.js"></script>
</body>
</html>