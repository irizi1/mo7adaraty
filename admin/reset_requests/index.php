<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة والتأكد من أن المستخدم هو أدمن
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// معالجة إتمام الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_request'])) {
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    if ($request_id) {
        $stmt = $pdo->prepare("UPDATE password_reset_requests SET status = 'completed' WHERE request_id = ?");
        $stmt->execute([$request_id]);
        $_SESSION['request_status_msg'] = "تم تحديد الطلب كمكتمل بنجاح.";
        header("Location: index.php");
        exit();
    }
}

try {
    // [تم التحديث] - استعلام جديد لجلب معلومات تسجيل الطالب مع الطلب
    $stmt = $pdo->query("
        SELECT 
            prr.*,
            (
                SELECT GROUP_CONCAT(
                    DISTINCT CONCAT(d.division_name, ' > ', c.class_name, ' / ', g.group_name, IF(t.track_name IS NOT NULL, CONCAT(' / ', t.track_name), '')) 
                    SEPARATOR '<br>'
                )
                FROM student_enrollments se
                JOIN course_offerings co ON se.offering_id = co.offering_id
                JOIN divisions d ON co.division_id = d.division_id
                JOIN classes c ON co.class_id = c.class_id
                JOIN `groups` g ON co.group_id = g.group_id
                LEFT JOIN tracks t ON co.track_id = t.track_id
                WHERE se.user_id = prr.user_id
            ) as enrollment_info
        FROM password_reset_requests prr
        WHERE prr.status = 'pending' 
        ORDER BY prr.request_date ASC
    ");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
               <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">

    <title>طلبات إعادة التعيين - لوحة التحكم</title>
    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require_once '../templates/header.php'; ?>
<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-key"></i> طلبات إعادة تعيين كلمة المرور</h2>

        <?php
        if (isset($_SESSION['request_status_msg'])) {
            echo '<p class="status-message success">' . htmlspecialchars($_SESSION['request_status_msg']) . '</p>';
            unset($_SESSION['request_status_msg']);
        }
        ?>

        <div class="table-section">
            <h3>الطلبات المعلقة (<?php echo count($requests); ?>)</h3>
            <?php if (empty($requests)): ?>
                <p>لا توجد طلبات معلقة حالياً.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>الطالب</th>
                            <th>المقررات المسجل بها</th>
                            <th>وسيلة التواصل</th>
                            <th>الإجراء الموصى به</th>
                            <th>إتمام الطلب</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($request['username']); ?></strong></td>
                            <td><?php echo $request['enrollment_info'] ?: '<em>غير مسجل</em>'; ?></td>
                            <td><?php echo htmlspecialchars($request['contact_info']); ?></td>
                            <td>
                                <ol style="padding-right: 20px; text-align: right;">
                                    <li>اذهب إلى <a href="../users/edit.php?id=<?php echo $request['user_id']; ?>" target="_blank">صفحة تعديل المستخدم</a>.</li>
                                    <li>قم بإنشاء كلمة مرور جديدة وقوية.</li>
                                    <li>أرسلها للطالب عبر وسيلة التواصل المذكورة.</li>
                                    <li>اضغط على "تم" لإنهاء الطلب.</li>
                                </ol>
                            </td>
                            <td>
                                <form action="index.php" method="POST" onsubmit="return confirm('هل أنت متأكد من إتمام هذا الطلب؟')">
                                    <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                    <button type="submit" name="complete_request" class="action-btn" style="background-color: #28a745;">
                                        <i class="fa-solid fa-check"></i> تم
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