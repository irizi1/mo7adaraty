<?php
// الخطوة 1: بدء الجلسة (Session) لتخزين معلومات المستخدم بعد دخوله
session_start();

// الخطوة 2: استدعاء ملف الاتصال بقاعدة البيانات
require_once 'config/db_connexion.php';

// الخطوة 3: التحقق من أن الطلب تم إرساله عبر النموذج (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // استقبال البيانات من نموذج تسجيل الدخول
    $login_identifier = $_POST['login_identifier']; // قد يكون بريدًا إلكترونيًا أو اسم مستخدم
    $password = $_POST['password'];

    // التحقق من أن الحقول ليست فارغة
    if (empty($login_identifier) || empty($password)) {
        // إذا كانت فارغة، يتم إرجاعه لصفحة الدخول مع رسالة خطأ
        header("Location: login.php?error=المرجو ملء جميع الحقول");
        exit();
    }

    // استخدام try-catch للتعامل مع أي أخطاء محتملة في قاعدة البيانات
    try {
        // الخطوة 4: البحث عن المستخدم في قاعدة البيانات عبر البريد الإلكتروني أو اسم المستخدم
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$login_identifier, $login_identifier]);
        $user = $stmt->fetch();

        // الخطوة 5: التحقق من وجود المستخدم ومطابقة كلمة المرور
        // password_verify() هي الدالة الآمنة لمقارنة كلمة المرور المدخلة مع النسخة المشفرة
        if ($user && password_verify($password, $user['password_hash'])) {
            // -- تسجيل الدخول ناجح --

            // أ. تخزين بيانات المستخدم المهمة في الجلسة
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

      // 5. توجيه المستخدم حسب دوره (Role)
if ($user['role'] === 'admin') {
    // إذا كان المستخدم أدمن، يتم توجيهه للوحة التحكم
    header("Location: admin/index.php");
} else {
    // إذا كان المستخدم طالباً، يتم توجيهه لملفه الشخصي
    header("Location: student/profil.php");
}
exit();

        } else {
            // -- تسجيل الدخول فاشل --
            // إعادة توجيهه لصفحة الدخول مع رسالة خطأ
            header("Location: login.php?error=البريد الإلكتروني/اسم المستخدم أو كلمة المرور غير صحيحة");
            exit();
        }

    } catch (PDOException $e) {
        // في حال حدوث خطأ استثنائي في قاعدة البيانات
        // die("Error: " . $e->getMessage()); // يمكن استخدامها أثناء التطوير فقط
        header("Location: login.php?error=حدث خطأ ما، يرجى المحاولة لاحقاً");
        exit();
    }

} else {
    // إذا حاول شخص الوصول للملف مباشرة بدون استخدام النموذج، يتم توجيهه للصفحة الرئيسية
    header("Location: index.php");
    exit();
}
?>