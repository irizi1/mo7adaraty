<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة والتأكد من أن المستخدم هو أدمن
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php"); 
    exit();
}

try {
    // جلب كل الفصول مع بيانات الشعبة والمسار
    $stmt = $pdo->query("
        SELECT c.class_id, c.class_name, t.track_name, d.division_name 
        FROM classes c
        JOIN tracks t ON c.track_id = t.track_id
        JOIN divisions d ON t.division_id = d.division_id
        ORDER BY d.division_name, t.track_name, c.class_name
    ");
    $all_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // إعادة تجميع الفصول للعرض بشكل منظم
    $grouped_classes = [];
    foreach ($all_classes as $class) {
        $grouped_classes[$class['division_name']][$class['track_name']][] = $class;
    }

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الفصول الدراسية</title>
           <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">
    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require_once '../templates/header.php'; ?>
<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-school"></i> إدارة الفصول الدراسية</h2>
        
        <?php
        if (isset($_GET['status'])) {
            if ($_GET['status'] == 'added') echo '<p class="status-message success">تمت إضافة الفصل بنجاح!</p>';
            if ($_GET['status'] == 'updated') echo '<p class="status-message success">تم تحديث الفصل بنجاح!</p>';
            if ($_GET['status'] == 'deleted') echo '<p class="status-message success">تم حذف الفصل بنجاح!</p>';
        }
        if (isset($_GET['error'])) {
            echo '<p class="status-message error">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        ?>

        <div class="form-section">
            <h3>إضافة فصل جديد</h3>
            <form action="add_process.php" method="POST">
                <div class="filter-form">
                    <div class="form-group">
                        <label for="division">الشعبة:</label>
                        <select id="division" name="division_id" required>
                            <option value="">-- اختر الشعبة --</option>
                            <?php foreach ($divisions as $division): ?>
                                <option value="<?php echo $division['division_id']; ?>"><?php echo htmlspecialchars($division['division_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="track">المسار:</label>
                        <select id="track" name="track_id" required disabled>
                            <option value="">-- اختر الشعبة أولاً --</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="class_name">اسم الفصل الجديد:</label>
                    <input type="text" id="class_name" name="class_name" required>
                </div>
                <button type="submit"><i class="fa-solid fa-plus"></i> إضافة الفصل</button>
            </form>
        </div>

        <div class="table-section">
            <h3>الفصول الحالية</h3>
            <?php if (empty($grouped_classes)): ?>
                <p>لا توجد فصول دراسية مضافة حالياً.</p>
            <?php else: ?>
                <?php foreach ($grouped_classes as $division_name => $tracks): ?>
                    <h4 style="margin-top: 30px; border-bottom: 2px solid #007bff; padding-bottom: 5px;"><?php echo htmlspecialchars($division_name); ?></h4>
                    <?php foreach ($tracks as $track_name => $classes_in_track): ?>
                        <h5>المسار: <?php echo htmlspecialchars($track_name); ?></h5>
                        <table>
                            <thead><tr><th>ID</th><th>اسم الفصل</th><th>إجراءات</th></tr></thead>
                            <tbody>
                                <?php foreach ($classes_in_track as $class): ?>
                                <tr>
                                    <td><?php echo $class['class_id']; ?></td>
                                    <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $class['class_id']; ?>" class="action-btn btn-edit"><i class="fa-solid fa-pen-to-square"></i> تعديل</a>
                                        <a href="delete_process.php?id=<?php echo $class['class_id']; ?>" class="action-btn btn-delete" onclick="return confirm('هل أنت متأكد؟');"><i class="fa-solid fa-trash-can"></i> حذف</a>
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