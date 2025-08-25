<?php
session_start();
require_once '../config/db_connexion.php';

// ==========================================================
// 1. حماية الصفحة والتحقق من الصلاحيات
// ==========================================================
if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$class_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$class_id) {
    header("Location: profil.php?error=رابط غير صحيح");
    exit();
}

try {
    // التحقق من أن الطالب مسجل في فوج ينتمي لهذا الفصل
    $stmt_check = $pdo->prepare("
        SELECT COUNT(*) 
        FROM student_enrollments se
        JOIN `groups` g ON se.group_id = g.group_id
        WHERE se.user_id = ? AND g.class_id = ?
    ");
    $stmt_check->execute([$user_id, $class_id]);
    if ($stmt_check->fetchColumn() == 0) {
        header("Location: profil.php?error=ليس لديك صلاحية الوصول لهذا الفصل");
        exit();
    }
} catch (PDOException $e) {
    error_log("Access permission check error: " . $e->getMessage());
    die("خطأ في التحقق من صلاحيات الوصول: تواصل مع الدعم الفني.");
}

// ==========================================================
// 2. جلب بيانات الصفحة من قاعدة البيانات
// ==========================================================
try {
    // أ. جلب معلومات الفصل الأساسية
    $stmt_class = $pdo->prepare("SELECT * FROM classes WHERE class_id = ?");
    $stmt_class->execute([$class_id]);
    $class_info = $stmt_class->fetch();

    if (!$class_info) {
        die("خطأ: لم يتم العثور على الفصل المطلوب.");
    }

    // ب. جلب بيانات المستخدم الحالي
    $stmt_user = $pdo->prepare("SELECT username, profile_picture FROM users WHERE user_id = ?");
    $stmt_user->execute([$user_id]);
    $user_info = $stmt_user->fetch();

} catch (PDOException $e) {
    error_log("Error fetching class or user data: " . $e->getMessage());
    die("خطأ: لا يمكن جلب بيانات الفصل أو المستخدم. تواصل مع الدعم الفني.");
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
           <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">

   
    <title>دردشة <?php echo htmlspecialchars($class_info['class_name']); ?> - Mo7adaraty</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Tajawal', sans-serif; margin:0; }

        .container { max-width: 900px; margin: 20px auto; padding: 20px; }
        .page-header { background-color: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 25px; }
        .page-header h1 { margin: 0 0 10px 0; color:#1a73e8; }
        .page-header a { color: #1a73e8; text-decoration: none; font-weight: 500; }
        .class-nav { display: flex; justify-content: center; background-color: #fff; padding: 10px; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .class-nav a { margin: 0 15px; color: #333; text-decoration: none; font-weight: 500; }
        .class-nav a.active { color: #1a73e8; font-weight: 600; }

        .chat-wrapper { background-color: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); overflow: hidden; }
        .chat-box { height: 500px; overflow-y: auto; padding: 20px; background-color: #f8f9fa; }
        
        /* === [   التعديلات الجديدة هنا   ] === */
        .chat-message { display: flex; margin-bottom: 15px; max-width: 75%; }
        .sender-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        
        .message-bubble { padding: 10px 15px; border-radius: 18px; }
        .message-sender { font-weight: bold; font-size: 14px; margin-bottom: 5px; }
        .message-text { line-height: 1.5; word-wrap: break-word; font-size: 15px; }
        .timestamp { font-size: 12px; display: block; margin-top: 5px; text-align: left; }

        /* رسالتك أنت (sent) - تظهر على اليسار */
        .chat-message.sent {
            margin-right: auto; /* يدفع الرسالة إلى اليسار */
            flex-direction: row; /* الأيقونة ثم النص */
        }
        .chat-message.sent .sender-avatar { margin-left: 10px; }
        .chat-message.sent .message-bubble {
            background-color: #007bff; /* لون مميز لرسائلك */
            color: white;
            border-bottom-left-radius: 4px;
        }
        .chat-message.sent .message-sender { color: #e9ecef; }
        .chat-message.sent .timestamp { color: #e9ecef; opacity: 0.8; }
        
        /* رسائل الآخرين (received) - تظهر على اليمين */
        .chat-message.received {
            margin-left: auto; /* يدفع الرسالة إلى اليمين */
            flex-direction: row-reverse; /* النص ثم الأيقونة */
        }
        .chat-message.received .sender-avatar { margin-right: 10px; }
        .chat-message.received .message-bubble {
            background-color: #e9ecef; /* لون مختلف لرسائلهم */
            color: #212529;
            border-bottom-right-radius: 4px;
        }
        .chat-message.received .message-sender { color: #0056b3; }
        .chat-message.received .timestamp { color: #6c757d; }
        /* ==================================== */

        .chat-form { display: flex; padding: 15px; border-top: 1px solid #ddd; background-color: #fff; align-items: center; }
        .chat-form input { flex-grow: 1; border: 1px solid #ccc; border-radius: 20px; padding: 10px 15px; font-family: 'Tajawal', sans-serif; }
        .chat-form button { border: none; background-color: #1a73e8; color: white; border-radius: 50%; width: 45px; height: 45px; margin-right: 10px; cursor: pointer; font-size: 18px; }
    </style>
</head>
<body>

<?php require_once '../templates/header.php'; ?>

<div class="container page-container">
    <header class="page-header">
        <h1>دردشة <?php echo htmlspecialchars($class_info['class_name']); ?></h1>
        <a href="profil.php">&larr; العودة إلى ملفي الشخصي</a>
    </header>

    <nav class="class-nav">
        <a href="class_lectures.php?id=<?php echo $class_id; ?>">المحاضرات</a>
        <a href="class_exams.php?id=<?php echo $class_id; ?>">الامتحانات</a>
        <a href="class_chat.php?id=<?php echo $class_id; ?>" class="active">الدردشة</a>
    </nav>

    <div class="chat-wrapper">
        <div id="chat-box" class="chat-box">
            <p class="chat-loading" style="text-align:center; color: #6c757d;">جاري تحميل الرسائل...</p>
        </div>
        <form id="chat-form" class="chat-form">
            <input type="hidden" id="chat_class_id" value="<?php echo $class_id; ?>">
            <input type="text" id="chat_message" placeholder="اكتب رسالتك هنا..." autocomplete="off" required>
            <button type="submit"><i class="fa-solid fa-paper-plane"></i></button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../assets/js/script.js"></script> 

</body>
</html>