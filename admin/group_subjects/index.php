<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة والتأكد من أن المستخدم هو أدمن
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

try {
    // جلب كل الأفواج مع بيانات الفصول التابعة لها لعرضها
    $groups = $pdo->query("
        SELECT g.group_id, g.group_name, c.class_name 
        FROM `groups` g 
        JOIN classes c ON g.class_id = c.class_id 
        ORDER BY c.class_name, g.group_name
    ")->fetchAll();

    // جلب المواد والأساتذة لملء قوائم الإضافة المنسدلة
    $subjects = $pdo->query("SELECT * FROM subjects ORDER BY subject_name")->fetchAll();
    $professors = $pdo->query("SELECT * FROM professors ORDER BY professor_name")->fetchAll();

} catch (PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة مقررات الأفواج</title>
                <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">

    <link rel="stylesheet" href="../../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<?php require_once '../templates/header.php'; ?>
<div class="admin-container">
    <?php require_once '../templates/sidebar.php'; ?>
    <main class="admin-main-content">
        <h2><i class="fa-solid fa-chalkboard-user"></i> إدارة مقررات الأفواج</h2>
        <p>من هنا يمكنك تعيين المواد والأساتذة لكل فوج دراسي.</p>

        <?php
        if (isset($_GET['status'])) {
            if ($_GET['status'] == 'added') echo '<p class="status-message success">تمت إضافة المقرر بنجاح!</p>';
            if ($_GET['status'] == 'deleted') echo '<p class="status-message success">تم حذف المقرر بنجاح!</p>';
        }
        if (isset($_GET['error'])) {
            echo '<p class="status-message error">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        ?>

        <div class="table-section">
            <h3>قائمة الأفواج والمقررات</h3>
            <?php if (empty($groups)): ?>
                <p>لا توجد أفواج حالياً. يرجى إضافة الأفواج أولاً من صفحة "إدارة الأفواج".</p>
            <?php else: ?>
                <?php foreach ($groups as $group): ?>
                    <div class="form-section" style="margin-bottom: 25px;">
                        <h4>الفوج: <?php echo htmlspecialchars($group['group_name']); ?> (<?php echo htmlspecialchars($group['class_name']); ?>)</h4>
                        
                        <table>
                            <thead><tr><th>المادة</th><th>الأستاذ المسؤول</th><th>إجراء</th></tr></thead>
                            <tbody>
                                <?php
                                $stmt_subjects = $pdo->prepare("
                                    SELECT gs.group_subject_id, s.subject_name, p.professor_name 
                                    FROM group_subjects gs 
                                    JOIN subjects s ON gs.subject_id = s.subject_id 
                                    JOIN professors p ON gs.professor_id = p.professor_id 
                                    WHERE gs.group_id = ?
                                ");
                                $stmt_subjects->execute([$group['group_id']]);
                                $assigned_subjects = $stmt_subjects->fetchAll();
                                
                                if (count($assigned_subjects) > 0):
                                    foreach($assigned_subjects as $as):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($as['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($as['professor_name']); ?></td>
                                    <td>
                                        <a href="delete_process.php?id=<?php echo $as['group_subject_id']; ?>" class="action-btn btn-delete" onclick="return confirm('هل أنت متأكد؟');">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php 
                                    endforeach;
                                else:
                                ?>
                                <tr><td colspan="3">لم يتم تعيين أي مواد لهذا الفوج بعد.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        
                        <form action="add_process.php" method="POST" style="margin-top:15px; display:flex; gap:10px; align-items:flex-end;">
                            <input type="hidden" name="group_id" value="<?php echo $group['group_id']; ?>">
                            <div class="form-group" style="flex-grow:1;">
                                <label>إضافة مادة جديدة:</label>
                                <select name="subject_id" required>
                                    <option value="">-- اختر مادة --</option>
                                    <?php foreach($subjects as $subject): ?> 
                                        <option value="<?php echo $subject['subject_id']; ?>"><?php echo htmlspecialchars($subject['subject_name']); ?></option> 
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" style="flex-grow:1;">
                                <label>تعيين أستاذ:</label>
                                <select name="professor_id" required>
                                    <option value="">-- اختر أستاذ --</option>
                                    <?php foreach($professors as $prof): ?> 
                                        <option value="<?php echo $prof['professor_id']; ?>"><?php echo htmlspecialchars($prof['professor_name']); ?></option> 
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit">إضافة</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
</main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../../assets/js/admin_script.js"></script>
    </body>
</html>