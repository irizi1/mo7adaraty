<?php
// أداة تشخيص المشاكل لميزة التعليقات
session_start();
header('Content-Type: text/html; charset=utf-8');

// دالة لطباعة النتائج بتنسيق جيد
function print_check($title, $status, $message = '') {
    $status_icon = $status ? "<span style='color:green;'>✔ نجاح</span>" : "<span style='color:red;'>❌ فشل</span>";
    echo "<tr><td>{$title}</td><td>{$status_icon}</td><td>{$message}</td></tr>";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <title>أداة تشخيص الأخطاء</title>
    <style>
        body { font-family: 'Tajawal', sans-serif; direction: rtl; background-color: #f4f7f6; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: right; }
        th { background-color: #f2f2f2; }
        .instructions { margin-top: 30px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9; }
    </style>
</head>
<body>
<div class="container">
    <h1>تقرير فحص نظام التعليقات</h1>
    <table>
        <thead><tr><th>الفحص</th><th>الحالة</th><th>ملاحظات</th></tr></thead>
        <tbody>
            <?php
            // --- 1. فحص الاتصال بقاعدة البيانات ---
            $db_ok = false;
            try {
                require_once '../config/db_connexion.php';
                print_check("الاتصال بقاعدة البيانات", true, "تم الاتصال بنجاح.");
                $db_ok = true;
            } catch (Exception $e) {
                print_check("الاتصال بقاعدة البيانات", false, "فشل الاتصال! تحقق من ملف config/db_connexion.php.");
            }

            if ($db_ok) {
                // --- 2. فحص وجود الجداول والأعمدة ---
                try {
                    $pdo->query("SELECT comment_id, content_type, content_id, user_id, comment_text FROM comments LIMIT 1");
                    print_check("جدول `comments` وأعمدته الأساسية", true, "الجدول موجود والأعمدة صحيحة.");
                } catch (PDOException $e) {
                    print_check("جدول `comments` وأعمدته الأساسية", false, "الجدول غير موجود أو الأعمدة ناقصة! أعد بناء قاعدة البيانات.");
                }
            }

            // --- 3. فحص وجود الملفات ---
            $files_to_check = [
                '../comments/add_comment.php',
                '../comments/get_comments.php',
                '../comments/assets/js/comments.js',
                '../assets/js/script.js'
            ];
            foreach ($files_to_check as $file) {
                print_check("وجود ملف: {$file}", file_exists($file), file_exists($file) ? 'موجود' : 'غير موجود!');
            }
            ?>
        </tbody>
    </table>

    <div class="instructions">
        <h2>الخطوة التالية: فحص المتصفح</h2>
        <p>إذا كانت جميع الفحوصات أعلاه ناجحة، فالمشكلة غالبًا في المتصفح. اتبع الخطوات التالية بدقة:</p>
        <ol>
            <li>اذهب إلى صفحة المحاضرات: <a href="class_lectures.php?id=1" target="_blank">student/class_lectures.php?id=1</a></li>
            <li>اضغط `F12` في لوحة المفاتيح لفتح "أدوات المطور".</li>
            <li>اختر تبويب **"Console"**.</li>
            <li>قم بتحديث الصفحة بالضغط على `Ctrl + R` أو `F5`.</li>
            <li>إذا رأيت أي **رسائل خطأ باللون الأحمر**، قم بتصوير الشاشة وأرسلها لي. هذه الرسالة هي مفتاح الحل.</li>
        </ol>
    </div>
</div>
</body>
</html>