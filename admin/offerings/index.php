<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

try {
    // 1. جلب كل المقررات المتاحة مع تفاصيلها للعرض
    $stmt = $pdo->query("
        SELECT 
            co.offering_id,
            d.division_name,
            c.class_name,
            g.group_name,
            t.track_name
        FROM course_offerings co
        JOIN divisions d ON co.division_id = d.division_id
        JOIN classes c ON co.class_id = c.class_id
        JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        ORDER BY d.division_name, c.class_id, t.track_name, g.group_id
    ");
    $all_offerings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. [تم التعديل هنا] إعادة هيكلة البيانات لتجميعها بشكل هرمي (شعبة -> فصل -> مسار -> أفواج)
    $structured_offerings = [];
    foreach ($all_offerings as $offering) {
        $track_key = $offering['track_name'] ?? 'عام'; // استخدام "عام" ك مفتاح للمسارات الفارغة
        $structured_offerings[$offering['division_name']][$offering['class_name']][$track_key][] = $offering;
    }

    // 3. جلب البيانات اللازمة لنموذج الإضافة
    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name")->fetchAll();
    $classes = $pdo->query("SELECT * FROM classes ORDER BY class_id")->fetchAll();
    $groups = $pdo->query("SELECT * FROM `groups` ORDER BY group_id")->fetchAll();

} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المقررات المتاحة</title>
                <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">

    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        #track-group { display: none; }
        .division-block { margin-bottom: 25px; padding: 20px; background: #fff; border-radius: 8px; border: 1px solid #e9ecef; }
        .division-title { font-size: 22px; color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-top: 0; }
        .class-group { margin-top: 20px; padding-right: 15px; border-right: 3px solid #f1f1f1; }
        .class-title { font-size: 18px; color: #343a40; margin-bottom: 15px; }
        .track-title { font-size: 16px; font-weight: bold; color: #6c757d; margin-bottom: 10px; }
    </style>
</head>
<body>
<?php require_once '../templates/header.php'; ?>
<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-layer-group"></i> إدارة المقررات المتاحة للتسجيل</h2>
        <p>من هنا يمكنك تكوين المقررات التي يمكن للطلاب التسجيل فيها.</p>
        
        <?php
        if (isset($_GET['status'])) {
            if ($_GET['status'] == 'added') echo '<p class="status-message success">تمت إضافة المقرر بنجاح!</p>';
            if ($_GET['status'] == 'deleted') echo '<p class="status-message success">تم حذف المقرر بنجاح!</p>';
        }
        if (isset($_GET['error'])) {
            echo '<p class="status-message error">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        ?>

        <div class="form-section">
            <h3>تكوين مقرر جديد</h3>
            <form action="add_process.php" method="POST">
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
                        <label for="class">الفصل:</label>
                        <select id="class" name="class_id" required>
                             <option value="">-- اختر --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['class_id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                     <div class="form-group" id="track-group">
                        <label for="track">المسار (للفصول المتقدمة):</label>
                        <select id="track" name="track_id" disabled>
                            <option value="">-- اختر الشعبة أولاً --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="group">الفوج:</label>
                        <select id="group" name="group_id" required>
                             <option value="">-- اختر --</option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?php echo $group['group_id']; ?>"><?php echo htmlspecialchars($group['group_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit"><i class="fa-solid fa-plus"></i> إضافة المقرر</button>
            </form>
        </div>

        <div class="table-section">
            <h3>المقررات المتاحة حالياً</h3>
            <?php if (empty($structured_offerings)): ?>
                <p>لا توجد مقررات متاحة حالياً.</p>
            <?php else: ?>
                <?php foreach ($structured_offerings as $division_name => $classes_in_division): ?>
                    <div class="division-block">
                        <h3 class="division-title"><?php echo htmlspecialchars($division_name); ?></h3>
                        <?php foreach ($classes_in_division as $class_name => $tracks_in_class): ?>
                            <div class="class-group">
                                <h4 class="class-title"><?php echo htmlspecialchars($class_name); ?></h4>
                                <?php foreach ($tracks_in_class as $track_name => $offerings_in_track): ?>
                                    <h5 class="track-title">المسار: <?php echo htmlspecialchars($track_name); ?></h5>
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>الفوج</th>
                                                <th>إجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($offerings_in_track as $offering): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($offering['group_name']); ?></td>
                                                <td>
                                                    <a href="delete_process.php?id=<?php echo $offering['offering_id']; ?>" class="action-btn btn-delete" onclick="return confirm('هل أنت متأكد؟ سيؤدي هذا إلى حذف تسجيلات الطلاب والمواد المتعلقة بهذا المقرر.');"><i class="fa-solid fa-trash-can"></i> حذف</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../../assets/js/admin_script.js"></script>
</body>
</html>