<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة والتأكد من أن المستخدم هو أدمن
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

try {
    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_id DESC")->fetchAll();
} catch (PDOException $e) {
    die("خطأ في جلب الشعب: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الشعب - لوحة التحكم</title>
             <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">

    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<?php require_once '../templates/header.php'; ?>

<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>

    <main class="admin-main-content">
        <h2><i class="fa-solid fa-sitemap"></i> إدارة الشعب</h2>

        <?php
        if (isset($_GET['status'])) {
            if ($_GET['status'] == 'added') echo '<p class="status-message success">تمت إضافة الشعبة بنجاح!</p>';
            if ($_GET['status'] == 'updated') echo '<p class="status-message success">تم تحديث الشعبة بنجاح!</p>';
            if ($_GET['status'] == 'deleted') echo '<p class="status-message success">تم حذف الشعبة بنجاح!</p>';
        }
        if (isset($_GET['error'])) {
            echo '<p class="status-message error">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        ?>

        <div class="form-section">
            <h3>إضافة شعبة جديدة</h3>
            <form action="add_process.php" method="POST">
                <div class="form-group">
                    <label for="division_name">اسم الشعبة:</label>
                    <input type="text" id="division_name" name="division_name" required>
                </div>
                <button type="submit"><i class="fa-solid fa-plus"></i> إضافة شعبة</button>
            </form>
        </div>

        <div class="table-section">
            <h3>الشعب الحالية</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>اسم الشعبة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($divisions) > 0): ?>
                        <?php foreach ($divisions as $division): ?>
                        <tr>
                            <td><?php echo $division['division_id']; ?></td>
                            <td><?php echo htmlspecialchars($division['division_name']); ?></td>
                            <td>
                                <a href="edit.php?id=<?php echo $division['division_id']; ?>" class="action-btn btn-edit"><i class="fa-solid fa-pen-to-square"></i> تعديل</a>
                                <a href="delete_process.php?id=<?php echo $division['division_id']; ?>" class="action-btn btn-delete" onclick="return confirm('هل أنت متأكد؟');"><i class="fa-solid fa-trash-can"></i> حذف</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3">لا توجد شعب حالياً.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../../assets/js/admin_script.js"></script>
    </body>
</html>