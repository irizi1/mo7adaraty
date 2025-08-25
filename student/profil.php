<?php
session_start();
require_once '../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

try {
    // جلب بيانات المستخدم
    $stmt_user = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt_user->execute([$user_id]);
    $user_info = $stmt_user->fetch();

    if (!$user_info) {
        session_destroy();
        header("Location: ../login.php");
        exit();
    }
    
    $profile_picture = $user_info['profile_picture'] ?: 'default_profile.png';

    // استعلام لجلب تسجيلات الطالب
    $stmt_enrollments = $pdo->prepare("
        SELECT 
            d.division_name, c.class_name, g.group_name, t.track_name,
            co.offering_id, s.subject_name, p.professor_name
        FROM student_enrollments se
        JOIN course_offerings co ON se.offering_id = co.offering_id
        JOIN divisions d ON co.division_id = d.division_id
        JOIN classes c ON co.class_id = c.class_id
        JOIN `groups` g ON co.group_id = g.group_id
        LEFT JOIN tracks t ON co.track_id = t.track_id
        LEFT JOIN offering_subjects os ON co.offering_id = os.offering_id
        LEFT JOIN subjects s ON os.subject_id = s.subject_id
        LEFT JOIN professors p ON os.professor_id = p.professor_id
        WHERE se.user_id = ?
        ORDER BY d.division_name, c.class_id, g.group_name, s.subject_name
    ");
    $stmt_enrollments->execute([$user_id]);
    $all_enrollments = $stmt_enrollments->fetchAll(PDO::FETCH_ASSOC);

    // إعادة هيكلة البيانات للعرض
    $structured_data = [];
    foreach ($all_enrollments as $enrollment) {
        $division = $enrollment['division_name'];
        $offering_id = $enrollment['offering_id'];
        $offering_key = $enrollment['class_name'] . ' / ' . $enrollment['group_name'] . ($enrollment['track_name'] ? ' / ' . $enrollment['track_name'] : '');

        if (!isset($structured_data[$division][$offering_id])) {
            $structured_data[$division][$offering_id] = [
                'description' => $offering_key,
                'subjects'    => []
            ];
        }
        if ($enrollment['subject_name']) {
            $structured_data[$division][$offering_id]['subjects'][] = [
                'subject_name'   => $enrollment['subject_name'],
                'professor_name' => $enrollment['professor_name'] ?? 'لم يحدد'
            ];
        }
    }

} catch (PDOException $e) {
    error_log("خطأ في ملف profil.php: " . $e->getMessage());
    header("Location: ../login.php?error=خطأ في جلب البيانات");
    exit();
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ملفي الشخصي - Mo7adaraty</title>
  <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    :root {
      --brand-color: #007bff; --bg-color: #f4f8fc; --card-bg: #ffffff;
      --border-color: #e9ecef; --text-primary: #212529; --text-secondary: #6c757d;
    }
    body { font-family: 'Tajawal', sans-serif; background-color: var(--bg-color); margin: 0; color: var(--text-primary); }
    .container { max-width: 900px; margin: 20px auto; padding: 0 15px; }
    .profile-header { text-align: center; margin-bottom: 30px; padding: 25px; background-color: var(--card-bg); border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); position: relative; }
    /* === [   CSS جديد للأزرار   ] === */
    .profile-actions { position: absolute; top: 20px; left: 20px; display: flex; gap: 10px; }
    .action-btn { background-color: #f8f9fa; color: var(--text-secondary); border: 1px solid var(--border-color); padding: 8px 12px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.2s ease; display: flex; align-items: center; gap: 6px; }
    .action-btn:hover { background-color: var(--brand-color); color: white; border-color: var(--brand-color); }
    /* ============================== */
    .profile-header img { width: 100px; height: 100px; border-radius: 50%; border: 4px solid var(--brand-color); object-fit: cover; margin-bottom: 10px; }
    .profile-header h1 { margin: 5px 0; font-size: 24px; color: var(--brand-color); }
    .profile-header p { color: var(--text-secondary); font-size: 16px; margin: 0; }
    .division-group { background-color: var(--card-bg); border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); margin-bottom: 25px; overflow: hidden; }
    .division-header { background: linear-gradient(135deg, var(--brand-color), #0056b3); color: white; padding: 15px 20px; }
    .division-header h2 { margin: 0; font-size: 20px; }
    .offerings-grid { padding: 20px; display: grid; grid-template-columns: 1fr; gap: 15px; }
    .class-card { border: 1px solid var(--border-color); border-radius: 10px; background-color: #fcfdff; }
    .class-card-header { padding: 15px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
    .class-info h4 { margin: 0; font-size: 17px; color: var(--brand-color); }
    .btn-enter { padding: 8px 16px; background-color: var(--brand-color); color: #fff !important; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; transition: background-color 0.3s; white-space: nowrap; }
    .btn-enter:hover { background-color: #0056b3; }
    .class-card-body { padding: 15px; }
    .subjects-list { list-style: none; padding: 0; margin: 0; }
    .subjects-list li { padding: 12px 10px; font-size: 15px; border-bottom: 1px solid #f0f2f5; }
    .subjects-list li:last-child { border-bottom: none; }
    .subjects-list .subject-name { font-weight: 600; }
    .subjects-list .professor-name { color: var(--text-secondary); margin-right: 15px; }
    .no-data { text-align: center; padding: 25px; background-color: var(--card-bg); border-radius: 12px; color: var(--text-secondary); }
  </style>
</head>
<body>
    <?php require_once '../templates/header.php'; ?>

<div class="container">
  <header class="profile-header">
    <div class="profile-actions">
        <a href="my_lectures/index.php" class="action-btn">
            <i class="fa-solid fa-hourglass-half"></i> محاضراتي قيد المراجعة
        </a>
        <a href="profile_settings/index.php" class="action-btn">
            <i class="fa-solid fa-cog"></i> إعدادات الحساب
        </a>
    </div>
    <img src="../uploads/profile_pictures/<?php echo htmlspecialchars($profile_picture); ?>" alt="الصورة الشخصية" onerror="this.onerror=null;this.src='../uploads/profile_pictures/default_profile.png';">
    <h1><?php echo htmlspecialchars($user_info['username']); ?></h1>
    <p><?php echo htmlspecialchars($user_info['email']); ?></p>
  </header>

  <?php if (empty($structured_data)): ?>
    <p class="no-data">لم تقم بأي تسجيلات دراسية بعد. <a href="../signup.php">سجل في مقرر الآن</a>.</p>
  <?php else: ?>
    <?php foreach ($structured_data as $division_name => $offerings): ?>
      <div class="division-group">
        <div class="division-header">
          <h2><i class="fa-solid fa-graduation-cap"></i> <?php echo htmlspecialchars($division_name); ?></h2>
        </div>
        <div class="offerings-grid">
            <?php foreach ($offerings as $offering_id => $offering_data): ?>
                <div class="class-card">
                    <div class="class-card-header">
                        <div class="class-info">
                            <h4><?php echo htmlspecialchars($offering_data['description']); ?></h4>
                        </div>
                        <a href="class_lectures.php?id=<?php echo $offering_id; ?>" class="btn-enter">
                            <i class="fa-solid fa-door-open"></i> الدخول للمقرر
                        </a>
                    </div>
                    <div class="class-card-body">
                        <ul class="subjects-list">
                            <?php if (!empty($offering_data['subjects'])): ?>
                                <?php foreach ($offering_data['subjects'] as $subject): ?>
                                    <li>
                                        <span class="subject-name"><?php echo htmlspecialchars($subject['subject_name']); ?></span>
                                        <span class="professor-name">الأستاذ: <?php echo htmlspecialchars($subject['professor_name']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li>لا توجد مواد مسندة لهذا المقرر بعد.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

</body>
</html>