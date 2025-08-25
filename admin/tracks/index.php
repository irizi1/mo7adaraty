<?php
session_start();
require_once '../../config/db_connexion.php';
// ... (كود الحماية والتحقق من صلاحيات الأدمن) ...
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php"); exit();
}

try {
    // 1. جلب كل المسارات مع بيانات الشعبة، وترتيبها حسب الشعبة
    $stmt = $pdo->query("
        SELECT t.track_id, t.track_name, d.division_name 
        FROM tracks t 
        JOIN divisions d ON t.division_id = d.division_id 
        ORDER BY d.division_name, t.track_name
    ");
    $all_tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. إعادة تجميع المسارات في مصفوفة حسب اسم الشعبة
    $grouped_tracks = [];
    foreach ($all_tracks as $track) {
        $grouped_tracks[$track['division_name']][] = $track;
    }

    // 3. جلب الشعب لنموذج الإضافة
    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name")->fetchAll();

} catch (PDOException $e) { 
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
                <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">

<title>إدارة المسارات</title>
    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require_once '../templates/header.php'; ?>
<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-route"></i> إدارة المسارات</h2>
        
        <div class="form-section">
            <h3>إضافة مسار جديد</h3>
            <form action="add_process.php" method="POST">
                <div class="form-group">
                    <label for="track_name">اسم المسار:</label>
                    <input type="text" id="track_name" name="track_name" required>
                </div>
                <div class="form-group">
                    <label for="division_id">اختر الشعبة التابع لها:</label>
                    <select id="division_id" name="division_id" required>
                        <option value="" disabled selected>-- اختر --</option>
                        <?php foreach ($divisions as $division): ?>
                            <option value="<?php echo $division['division_id']; ?>"><?php echo htmlspecialchars($division['division_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit"><i class="fa-solid fa-plus"></i> إضافة مسار</button>
            </form>
        </div>

        <div class="table-section">
            <h3>المسارات الحالية</h3>
            <?php if (empty($grouped_tracks)): ?>
                <p>لا توجد مسارات مضافة حالياً.</p>
            <?php else: ?>
                <?php foreach ($grouped_tracks as $division_name => $tracks_in_division): ?>
                    <h4 style="margin-top: 30px; border-bottom: 2px solid #007bff; padding-bottom: 5px;">
                        الشعبة: <?php echo htmlspecialchars($division_name); ?>
                    </h4>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>اسم المسار</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tracks_in_division as $track): ?>
                            <tr>
                                <td><?php echo $track['track_id']; ?></td>
                                <td><?php echo htmlspecialchars($track['track_name']); ?></td>
                                <td>
                                    <a href="edit.php?id=<?php echo $track['track_id']; ?>" class="action-btn btn-edit"><i class="fa-solid fa-pen-to-square"></i> تعديل</a>
                                    <a href="delete_process.php?id=<?php echo $track['track_id']; ?>" class="action-btn btn-delete" onclick="return confirm('هل أنت متأكد؟');"><i class="fa-solid fa-trash-can"></i> حذف</a>
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