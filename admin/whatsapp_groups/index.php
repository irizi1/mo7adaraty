<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

try {
    // جلب جميع المقررات المتاحة مع روابط الواتساب الحالية
    $stmt = $pdo->query("
        SELECT 
            co.offering_id,
            d.division_name,
            c.class_name,
            g.group_name,
            t.track_name,
            wg.group_link,
            wg.group_name as whatsapp_group_name
        FROM course_offerings co
        JOIN divisions d ON co.division_id = d.division_id
        JOIN classes c ON co.class_id = c.class_id
        JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        LEFT JOIN whatsapp_groups wg ON co.offering_id = wg.offering_id
        ORDER BY d.division_name, c.class_name, g.group_name
    ");
    $offerings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
   
    <title>إدارة مجموعات الواتساب - لوحة التحكم</title>
                <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">

    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require_once '../templates/header.php'; ?>
<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fab fa-whatsapp"></i> إدارة مجموعات الواتساب</h2>
        <p>من هنا يمكنك إضافة أو تحديث روابط مجموعات الواتساب لكل مقرر دراسي.</p>

        <?php if (isset($_SESSION['whatsapp_status'])): ?>
            <p class="status-message success"><?php echo htmlspecialchars($_SESSION['whatsapp_status']); ?></p>
            <?php unset($_SESSION['whatsapp_status']); ?>
        <?php endif; ?>

        <div class="table-section">
            <h3>قائمة المقررات</h3>
            <table>
                <thead>
                    <tr>
                        <th>المقرر الدراسي</th>
                        <th>اسم المجموعة</th>
                        <th>رابط المجموعة الحالي</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($offerings as $offering): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($offering['division_name'] . ' > ' . $offering['class_name'] . ' / ' . $offering['group_name'] . ($offering['track_name'] ? ' / ' . $offering['track_name'] : '')); ?></td>
                            <form action="process.php" method="POST">
                                <input type="hidden" name="offering_id" value="<?php echo $offering['offering_id']; ?>">
                                <td>
                                    <input type="text" name="group_name" value="<?php echo htmlspecialchars($offering['whatsapp_group_name'] ?? ''); ?>" placeholder="اسم وصفي للمجموعة" style="width: 100%;">
                                </td>
                                <td>
                                    <input type="url" name="group_link" value="<?php echo htmlspecialchars($offering['group_link'] ?? ''); ?>" placeholder="https://chat.whatsapp.com/..." style="width: 100%; text-align:left; direction: ltr;">
                                </td>
                                <td>
                                    <button type="submit" class="action-btn" style="background-color: #28a745;"><i class="fa-solid fa-save"></i> حفظ</button>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>