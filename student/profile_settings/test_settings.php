<?php
// صفحة اختبار لتشخيص مشكلة جلب المقررات
require_once '../../config/db_connexion.php';

try {
    $divisions = $pdo->query("SELECT * FROM divisions ORDER BY division_name")->fetchAll();
} catch (PDOException $e) {
    die("خطأ قاتل: لا يمكن الاتصال بقاعدة البيانات. " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>صفحة اختبار - إعدادات الملف الشخصي</title>
    <style>
        body { font-family: Arial, sans-serif; direction: rtl; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 700px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { font-weight: bold; display: block; margin-bottom: 5px; }
        select { width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc; }
        #debug-log { margin-top: 20px; padding: 15px; border: 1px solid #ddd; background: #f9f9f9; border-radius: 4px; font-family: monospace; white-space: pre-wrap; word-wrap: break-word; }
        .log-status { font-weight: bold; }
        .log-success { color: green; }
        .log-error { color: red; }
    </style>
</head>
<body>

<div class="container">
    <h1><i class="fa-solid fa-vial"></i> صفحة اختبار جلب المقررات</h1>
    <p>هذه الصفحة مصممة لاختبار الاتصال بين الواجهة الأمامية والملف المسؤول عن جلب بيانات المقررات (`get_offerings_for_signup.php`).</p>

    <div class="form-group">
        <label for="division">1. اختر الشعبة:</label>
        <select id="division">
            <option value="">-- اختر --</option>
            <?php foreach ($divisions as $division): ?>
                <option value="<?php echo $division['division_id']; ?>"><?php echo htmlspecialchars($division['division_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>2. النتائج التي تم جلبها:</label>
        <div id="offerings-container" style="border: 1px solid #ccc; min-height: 100px; padding: 10px; border-radius: 4px;">
            </div>
    </div>

    <div id="debug-log">
        <p class="log-status">سجل التشخيص:</p>
        <div id="log-content">الرجاء اختيار شعبة لبدء الاختبار...</div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#division').on('change', function() {
        var divisionID = $(this).val();
        var $offeringsContainer = $('#offerings-container');
        var $logContent = $('#log-content');

        // إعادة تعيين الحقول
        $offeringsContainer.html('');
        $logContent.html('');

        if (!divisionID) {
            $logContent.html('تم إلغاء الاختيار.');
            return;
        }

        $logContent.append('<div><span class="log-status">1. إرسال الطلب:</span> يتم الآن إرسال طلب إلى `../../get_offerings_for_signup.php` مع `division_id = ' + divisionID + '`</div>');

        $.ajax({
            url: '../../get_offerings_for_signup.php',
            type: 'POST',
            data: { division_id: divisionID },
            dataType: 'json', // نتوقع استجابة من نوع JSON
            success: function(response) {
                $logContent.append('<div><span class="log-status log-success">2. نجاح الطلب:</span> تم استلام استجابة ناجحة.</div>');
                
                if (response && Array.isArray(response)) {
                    $logContent.append('<div><span class="log-status">3. تحليل الاستجابة:</span> الاستجابة هي مصفوفة (Array) وتحتوي على ' + response.length + ' عنصر.</div>');
                    
                    if (response.length > 0) {
                        $offeringsContainer.html('<ul></ul>');
                        response.forEach(function(offering) {
                            var offeringLabel = `${offering.class_name} / ${offering.group_name}`;
                            if (offering.track_name) {
                                offeringLabel += ` / ${offering.track_name}`;
                            }
                            $offeringsContainer.find('ul').append(`<li>${offeringLabel} (ID: ${offering.offering_id})</li>`);
                        });
                        $logContent.append('<div><span class="log-status log-success">4. النتيجة:</span> تم عرض المقررات بنجاح.</div>');
                    } else {
                        $offeringsContainer.html('<p style="color: orange;">لا توجد مقررات متاحة لهذه الشعبة.</p>');
                        $logContent.append('<div><span class="log-status" style="color: orange;">4. النتيجة:</span> الاستجابة كانت فارغة، مما يعني عدم وجود مقررات.</div>');
                    }
                } else {
                     $logContent.append('<div><span class="log-status log-error">3. تحليل الاستجابة:</span> فشل! الاستجابة التي تم استلامها ليست مصفوفة (Array).</div>');
                     $logContent.append('<hr><div><strong>الاستجابة الخام:</strong><br>' + JSON.stringify(response) + '</div>');
                }
            },
            error: function(xhr, status, error) {
                $logContent.append('<div><span class="log-status log-error">2. فشل الطلب:</span> حدث خطأ أثناء الاتصال بالخادم.</div>');
                $logContent.append('<div><strong>- الحالة:</strong> ' + status + '</div>');
                $logContent.append('<div><strong>- الخطأ:</strong> ' + error + '</div>');
                $logContent.append('<hr><div><strong>الاستجابة الخام من الخادم:</strong><br>' + xhr.responseText + '</div>');
            }
        });
    });
});
</script>

</body>
</html>