<?php
session_start();
require_once '../../config/db_connexion.php';

// 1. حماية الصفحة والتحقق من أن المستخدم مسجل دخوله
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

try {
    // جلب بيانات المستخدم الحالية للمقارنة والتحقق
    $stmt_user = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt_user->execute([$user_id]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if (!$user) { throw new Exception("المستخدم غير موجود."); }

    $pdo->beginTransaction();
    $updates = [];
    $params = [];

    // 2. تحديث اسم المستخدم والبريد الإلكتروني
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);

    if ($username !== $user['username']) {
        // التحقق من أن اسم المستخدم الجديد غير مستخدم
        $stmt_check = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt_check->execute([$username]);
        if ($stmt_check->fetch()) {
            throw new Exception("اسم المستخدم الجديد موجود بالفعل.");
        }
        $updates[] = "username = ?";
        $params[] = $username;
    }

    if ($email !== $user['email']) {
        // التحقق من أن البريد الإلكتروني الجديد غير مستخدم
        $stmt_check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt_check->execute([$email]);
        if ($stmt_check->fetch()) {
            throw new Exception("البريد الإلكتروني الجديد موجود بالفعل.");
        }
        $updates[] = "email = ?";
        $params[] = $email;
    }

    // 3. تحديث كلمة المرور (إذا تم إدخالها)
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($new_password)) {
        if (empty($current_password) || !password_verify($current_password, $user['password_hash'])) {
            throw new Exception("كلمة المرور الحالية غير صحيحة.");
        }
        if ($new_password !== $confirm_password) {
            throw new Exception("كلمتا المرور الجديدتان غير متطابقتين.");
        }
        if (strlen($new_password) < 8) {
            throw new Exception("كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل.");
        }
        $updates[] = "password_hash = ?";
        $params[] = password_hash($new_password, PASSWORD_DEFAULT);
    }
    
    // 4. تحديث الصورة الشخصية (إذا تم رفع صورة جديدة)
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        // حذف الصورة القديمة (إذا لم تكن الافتراضية)
        if ($user['profile_picture'] !== 'default_profile.png' && file_exists('../../uploads/profile_pictures/' . $user['profile_picture'])) {
            unlink('../../uploads/profile_pictures/' . $user['profile_picture']);
        }

        // رفع الصورة الجديدة
        $file = $_FILES['profile_picture'];
        $upload_dir = '../../uploads/profile_pictures/';
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_image_name = 'user_' . uniqid() . '.' . $extension;
        
        if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_image_name)) {
            $updates[] = "profile_picture = ?";
            $params[] = $new_image_name;
        } else {
            throw new Exception("فشل رفع الصورة الجديدة.");
        }
    }

    // 5. تنفيذ التحديث في قاعدة البيانات (فقط إذا كانت هناك تغييرات)
    if (!empty($updates)) {
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE user_id = ?";
        $params[] = $user_id;
        $stmt_update = $pdo->prepare($sql);
        $stmt_update->execute($params);

        // تحديث اسم المستخدم في الجلسة إذا تم تغييره
        if ($username !== $user['username']) {
            $_SESSION['username'] = $username;
        }
    }
    
    $pdo->commit();
    $_SESSION['settings_msg'] = ['type' => 'success', 'message' => 'تم تحديث معلوماتك بنجاح.'];

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['settings_msg'] = ['type' => 'error', 'message' => $e->getMessage()];
}

header("Location: index.php");
exit();
?>