<?php
session_start();
require_once 'config/db_connexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("لا يمكن الوصول إلى هذه الصفحة مباشرة.");
}

$username = trim($_POST['username']);
$email = trim($_POST['email']);
$password = $_POST['password'];
// [تم التعديل هنا] استخلاص معرفات المقررات من مصفوفة enrollments
$offering_ids = [];
if (isset($_POST['enrollments']) && is_array($_POST['enrollments'])) {
    foreach ($_POST['enrollments'] as $enrollment) {
        if (!empty($enrollment['offering_id'])) {
            $offering_ids[] = $enrollment['offering_id'];
        }
    }
}

if (empty($username) || empty($email) || empty($password) || empty($offering_ids)) {
    header('Location: signup.php?error=يرجى ملء جميع الحقول واختيار مقرر واحد على الأقل.');
    exit();
}
if (count($offering_ids) > 2) {
    header('Location: signup.php?error=لا يمكنك التسجيل في أكثر من مقررين.');
    exit();
}

// ... باقي كود التحقق من البريد وكلمة المرور واسم المستخدم (يبقى كما هو) ...
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $email]);
if ($stmt->fetch()) {
    header('Location: signup.php?error=اسم المستخدم أو البريد الإلكتروني مسجل بالفعل.');
    exit();
}

// ... كود رفع الصورة وتشفير كلمة المرور (يبقى كما هو) ...
$profile_picture_name = 'default.png';
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
    $profile_picture_name = uniqid('user_', true) . '.' . $extension;
    $upload_dir = __DIR__ . '/uploads/profile_pictures/';
    move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $profile_picture_name);
}
$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    $stmt_user = $pdo->prepare(
        "INSERT INTO users (username, email, password_hash, profile_picture, role) VALUES (?, ?, ?, ?, 'student')"
    );
    $stmt_user->execute([$username, $email, $password_hash, $profile_picture_name]);
    $user_id = $pdo->lastInsertId();

    $stmt_enroll = $pdo->prepare("INSERT INTO student_enrollments (user_id, offering_id) VALUES (?, ?)");
    foreach ($offering_ids as $offering_id) {
        $stmt_enroll->execute([$user_id, (int)$offering_id]);
    }

    $pdo->commit();

    header('Location: login.php?success=تم إنشاء حسابك بنجاح. يمكنك الآن تسجيل الدخول.');
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: signup.php?error=حدث خطأ غير متوقع أثناء إنشاء الحساب.');
    exit();
}
?>