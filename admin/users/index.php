<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

try {
    // [تم التحديث هنا] استعلام جديد لجلب المستخدمين مع مقرراتهم المسجلين فيها
    $stmt = $pdo->query("
        SELECT 
            u.user_id, u.username, u.email, u.role, u.created_at,
            GROUP_CONCAT(
                DISTINCT CONCAT(d.division_name, ' > ', c.class_name, ' / ', g.group_name, IF(t.track_name IS NOT NULL, CONCAT(' / ', t.track_name), '')) 
                SEPARATOR '<br>'
            ) as enrolled_offerings
        FROM users u
        LEFT JOIN student_enrollments se ON u.user_id = se.user_id
        LEFT JOIN course_offerings co ON se.offering_id = co.offering_id
        LEFT JOIN divisions d ON co.division_id = d.division_id
        LEFT JOIN classes c ON co.class_id = c.class_id
        LEFT JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        GROUP BY u.user_id
        ORDER BY u.user_id DESC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطأ في جلب بيانات المستخدمين: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
                <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">

    <title>إدارة المستخدمين</title>
    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    
<?php require_once '../templates/header.php'; ?>

<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-users"></i> إدارة المستخدمين</h2>
        
        <?php
        if (isset($_GET['status'])) {
            if ($_GET['status'] == 'updated') echo '<p class="status-message success">تم تحديث المستخدم بنجاح!</p>';
            if ($_GET['status'] == 'deleted_successfully') echo '<p class="status-message success">تم حذف المستخدم بنجاح!</p>';
        }
        if (isset($_GET['error'])) {
            echo '<p class="status-message error">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        ?>

        <div class="table-section">
            <h3>قائمة المستخدمين المسجلين (<?php echo count($users); ?>)</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>اسم المستخدم</th>
                        <th>البريد الإلكتروني</th>
                        <th>الدور</th>
                        <th>المقررات المسجل بها</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="6">لا يوجد مستخدمون حالياً.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span style="color: #dc3545; font-weight: bold;"><i class="fa-solid fa-user-shield"></i> أدمن</span>
                                    <?php else: ?>
                                        <i class="fa-solid fa-user-graduate"></i> طالب
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $user['enrolled_offerings'] ?? '<i>لم يسجل بعد</i>'; ?></td>
                                <td>
                                    <a href="edit.php?id=<?php echo $user['user_id']; ?>" class="action-btn btn-edit"><i class="fa-solid fa-pen-to-square"></i> تعديل</a>
                                    <a href="delete_process.php?id=<?php echo $user['user_id']; ?>" class="action-btn btn-delete" onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟ سيتم حذف جميع بياناته المتعلقة.');"><i class="fa-solid fa-trash-can"></i> حذف</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../../assets/js/admin_script.js"></script>
</body>
</html>