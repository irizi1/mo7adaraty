<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة والتأكد من أن المستخدم هو أدمن
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php"); 
    exit();
}

try {
    // جلب الأفواج مع بيانات الفصول، المسارات، والشعب
    $stmt = $pdo->query("
        SELECT g.group_id, g.group_name, c.class_name, t.track_name, d.division_name
        FROM `groups` g
        JOIN classes c ON g.class_id = c.class_id
        JOIN tracks t ON c.track_id = t.track_id
        JOIN divisions d ON t.division_id = d.division_id
        ORDER BY d.division_name, t.track_name, c.class_name, g.group_name
    ");
    $all_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // إعادة تجميع الأفواج في مصفوفة حسب اسم الشعبة
    $grouped_groups = [];
    foreach ($all_groups as $group) {
        $grouped_groups[$group['division_name']][] = $group;
    }

    // جلب الشعب للقائمة المنسدلة
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
    <title>إدارة الأفواج</title>
                <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">
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
        #track-group {
            display: none; /* إخفاء حقل المسار مبدئياً */
        }
    </style>
</head>
<body>
<?php require_once '../templates/header.php'; ?>
<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-users-rectangle"></i> إدارة الأفواج</h2>
        
        <?php
        if (isset($_GET['status'])) {
            if ($_GET['status'] === 'added') echo '<p class="status-message success">تمت إضافة الفوج بنجاح.</p>';
            if ($_GET['status'] === 'updated') echo '<p class="status-message success">تم تحديث الفوج بنجاح.</p>';
            if ($_GET['status'] === 'deleted') echo '<p class="status-message success">تم حذف الفوج بنجاح.</p>';
        }
        if (isset($_GET['error'])) echo '<p class="status-message error">' . htmlspecialchars($_GET['error']) . '</p>';
        ?>

        <div class="form-section">
            <h3>إضافة فوج جديد</h3>
            <form action="add_process.php" method="POST">
                <div class="form-group">
                    <label for="division">اختر الشعبة:</label>
                    <select id="division" name="division_id" required>
                        <option value="" disabled selected>-- اختر الشعبة --</option>
                        <?php foreach ($divisions as $division): ?>
                            <option value="<?php echo $division['division_id']; ?>"><?php echo htmlspecialchars($division['division_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="class">اختر الفصل:</label>
                    <select id="class" name="class_id" required disabled>
                        <option value="" disabled selected>-- اختر الشعبة أولاً --</option>
                    </select>
                </div>
                <div class="form-group" id="track-group">
                    <label for="track">اختر المسار (للفصول المتقدمة):</label>
                    <select id="track" name="track_id" disabled>
                        <option value="" disabled selected>-- اختر الفصل أولاً --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="group_name">اسم الفوج:</label>
                    <input type="text" name="group_name" id="group_name" required>
                </div>
                <button type="submit"><i class="fa-solid fa-plus"></i> إضافة الفوج</button>
            </form>
        </div>

        <div class="table-section">
            <h3>الأفواج الحالية</h3>
            <?php if (empty($grouped_groups)): ?>
                <p>لا توجد أفواج مضافة حالياً.</p>
            <?php else: ?>
                <?php foreach ($grouped_groups as $division_name => $groups_in_division): ?>
                    <h4 style="margin-top: 30px; border-bottom: 2px solid #007bff; padding-bottom: 5px;">
                        الشعبة: <?php echo htmlspecialchars($division_name); ?>
                    </h4>
                    <table>
                        <thead>
                            <tr>
                                <th>اسم الفوج</th>
                                <th>الفصل</th>
                                <th>المسار</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($groups_in_division as $group): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($group['group_name']); ?></td>
                                <td><?php echo htmlspecialchars($group['class_name']); ?></td>
                                <td><?php echo htmlspecialchars($group['track_name']); ?></td>
                                <td>
                                    <a href="edit.php?id=<?php echo $group['group_id']; ?>" class="action-btn btn-edit"><i class="fa-solid fa-pen-to-square"></i> تعديل</a>
                                    <a href="delete_process.php?id=<?php echo $group['group_id']; ?>" class="action-btn btn-delete" onclick="return confirm('هل أنت متأكد؟');"><i class="fa-solid fa-trash-can"></i> حذف</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../../assets/js/admin_script.js"></script>
</body>
</html>