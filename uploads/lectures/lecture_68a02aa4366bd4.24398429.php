<?php
// بيانات الاتصال بقاعدة البيانات
$db_host = "localhost";         // عادة يكون localhost
$db_name = "mo7adaraty_db";     // اسم قاعدة البيانات التي أنشأتها
$db_user = "root";              // اسم مستخدم قاعدة البيانات (غالباً root في البيئة المحلية)
$db_pass = "";                  // كلمة مرور قاعدة البيانات (غالباً فارغة في البيئة المحلية)

try {
    // إنشاء اتصال جديد باستخدام PDO
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);

    // ضبط خصائص PDO للتعامل مع الأخطاء بشكل احترافي
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // لضمان أن البيانات العربية تظهر بشكل صحيح
    $pdo->exec("SET NAMES 'utf8'");

} catch (PDOException $e) {
    // في حالة فشل الاتصال، يتم إيقاف الموقع وعرض رسالة خطأ عامة
    // لا تعرض أبدًا تفاصيل الخطأ للمستخدم النهائي لأسباب أمنية
    die("Erreur: Impossible de se connecter à la base de données. " . $e->getMessage()); // سنغير هذه الرسالة لاحقاً
}
?>