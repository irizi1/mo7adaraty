<?php
require_once 'config/db_connexion.php';

echo "<h1>بدء عملية الإصلاح والمعالجة لكلمة المرور</h1>";

$username_to_fix = 'admin';
$new_password = 'password123';

try {
    // 1. إنشاء هاش جديد باستخدام بيئة PHP لديك
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    echo "<p><b>الهاش الجديد الذي تم إنشاؤه الآن:</b><br> " . htmlspecialchars($new_hash) . "</p>";

    // 2. تحديث قاعدة البيانات مباشرة بالهاش الجديد
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
    $stmt->execute([$new_hash, $username_to_fix]);
    echo "<p style='color:blue; font-weight:bold;'>تم تحديث قاعدة البيانات بنجاح بالهاش الجديد.</p>";

    // 3. إعادة قراءة الهاش من قاعدة البيانات للتأكد من أنه تم حفظه بشكل صحيح
    $stmt_select = $pdo->prepare("SELECT password_hash FROM users WHERE username = ?");
    $stmt_select->execute([$username_to_fix]);
    $user_data = $stmt_select->fetch();
    $fetched_hash = $user_data['password_hash'];
    echo "<p><b>الهاش الذي تم جلبه من قاعدة البيانات بعد التحديث:</b><br> " . htmlspecialchars($fetched_hash) . "</p>";
    echo "<hr>";

    // 4. التحقق النهائي للتأكد 100%
    if (password_verify($new_password, $fetched_hash)) {
        echo "<h2 style='color: green; font-weight: bold;'>✔ نجاح! تم إصلاح كلمة المرور والتحقق منها بنجاح.</h2>";
        echo "<h3>يمكنك الآن الذهاب إلى صفحة تسجيل الدخول، ستعمل بشكل صحيح.</h3>";
    } else {
        echo "<h2 style='color: red; font-weight: bold;'>❌ فشل غريب بعد الإصلاح. إذا رأيت هذه الرسالة، فهناك مشكلة غير عادية في إعدادات PHP لديك.</h2>";
    }

} catch (PDOException $e) {
    echo "خطأ في قاعدة البيانات: " . $e->getMessage();
}
?>