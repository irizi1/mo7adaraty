<?php
// 1. استدعاء ملف الاتصال بقاعدة البيانات
// تأكد من أن هذا المسار صحيح بالنسبة لموقع ملف get_tracks.php
require_once __DIR__ . '/config/db_connexion.php';

// 2. تحديد نوع المحتوى ليكون JSON
header('Content-Type: application/json; charset=utf-8');

// 3. التحقق من أن الطلب يحتوي على division_id
if (!isset($_GET['division_id']) || empty($_GET['division_id'])) {
    echo json_encode([]); // إرجاع مصفوفة فارغة إذا لم يتم إرسال ID
    exit;
}

// 4. تحويل الـ ID إلى عدد صحيح للحماية
$divisionId = (int)$_GET['division_id'];

try {
    // 5. التأكد من أن متغير الاتصال $pdo موجود
    if (!isset($pdo)) {
        // إرجاع خطأ إذا كان الاتصال غير موجود
        echo json_encode(['success' => false, 'message' => 'فشل الاتصال بقاعدة البيانات.']);
        exit;
    }
    
    // 6. تجهيز وتنفيذ الاستعلام باستخدام Prepared Statements
    // تأكد من أن أسماء الجداول والأعمدة تطابق قاعدة بياناتك
    // الجدول: tracks | الأعمدة: track_id, track_name, division_id
    $stmt = $pdo->prepare("SELECT track_id, track_name FROM tracks WHERE division_id = ? ORDER BY track_name");
    $stmt->execute([$divisionId]);
    
    // 7. جلب كل النتائج
    $tracks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 8. طباعة النتائج بصيغة JSON
    // json_encode سيقوم تلقائياً بتحويل مصفوفة فارغة إلى []
    echo json_encode($tracks);

} catch (PDOException $e) {
    // في حالة حدوث أي خطأ في قاعدة البيانات، قم بتسجيله
    error_log("Database Error in get_tracks.php: " . $e->getMessage());
    
    // أرجع رسالة خطأ بصيغة JSON
    http_response_code(500); // للدلالة على وجود خطأ في الخادم
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء جلب المسارات.']);
}
?>