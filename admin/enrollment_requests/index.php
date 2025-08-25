<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

try {
    // [تم التحديث هنا] - استعلام جديد لجلب الطلبات بناءً على الهيكل الجديد
    $stmt = $pdo->query("
        SELECT 
            req.request_id, req.user_id, req.request_date, req.reason,
            req.current_enrollment_info, u.username,
            d.division_name as requested_division_name,
            c.class_name as requested_class_name,
            g.group_name as requested_group_name,
            t.track_name as requested_track_name
        FROM enrollment_change_requests req
        JOIN users u ON req.user_id = u.user_id
        JOIN course_offerings co ON req.requested_offering_id = co.offering_id
        JOIN divisions d ON co.division_id = d.division_id
        JOIN classes c ON co.class_id = c.class_id
        JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        WHERE req.status = 'pending'
        ORDER BY req.request_date ASC
    ");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("خطأ في جلب البيانات: ". $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    
<meta charset="UTF-8">
    <title>طلبات تغيير المقرر - لوحة التحكم</title>
            <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">
    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require_once '../templates/header.php'; ?>
<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-exchange-alt"></i> طلبات تغيير المقرر الدراسي</h2>

        <?php if (isset($_SESSION['request_status_msg'])): ?>
            <p class="status-message success"><?php echo htmlspecialchars($_SESSION['request_status_msg']); ?></p>
            <?php unset($_SESSION['request_status_msg']); ?>
        <?php endif; ?>

        <div class="table-section">
            <h3>الطلبات المعلقة (<?php echo count($requests); ?>)</h3>
            <?php if (empty($requests)): ?>
                <p>لا توجد طلبات معلقة حالياً.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>الطالب</th>
                            <th>التسجيل الحالي</th>
                            <th>المقرر المطلوب</th>
                            <th>السبب</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['username']); ?></td>
                            <td style="white-space: pre-wrap; font-size: 0.9em;"><?php echo htmlspecialchars($request['current_enrollment_info']); ?></td>
                            <td><?php echo htmlspecialchars($request['requested_division_name'] . ' > ' . $request['requested_class_name'] . ' / ' . $request['requested_group_name'] . ($request['requested_track_name'] ? ' / ' . $request['requested_track_name'] : '')); ?></td>
                            <td><?php echo htmlspecialchars($request['reason'] ?: '<em>لم يذكر سبب</em>'); ?></td>
                            <td>
                                <form action="process_request.php" method="POST" onsubmit="return confirm('هل أنت متأكد؟ سيتم حذف تسجيلات الطالب القديمة وتسجيله في المقررات الجديدة.');">
                                    <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                    <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                    <button type="submit" name="action" value="completed" class="action-btn" style="background-color: #28a745;" title="الموافقة على الطلب وتغيير تسجيل الطالب تلقائياً">
                                        <i class="fa-solid fa-check"></i> موافقة
                                    </button>
                                </form>
                                <form action="process_request.php" method="POST" style="margin-top: 5px;">
                                     <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                     <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                    <button type="submit" name="action" value="rejected" class="action-btn btn-delete" title="رفض الطلب">
                                        <i class="fa-solid fa-times"></i> رفض
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>