<?php
session_start();
require_once '../../config/db_connexion.php';

// 1. حماية الصفحة والتحقق من الصلاحيات
if (!isset($_SESSION['user_id'])){
    header("Location: ../../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// 2. استقبال ID المحاضرة والتحقق منه
$lecture_id = filter_input(INPUT_POST, 'lecture_id', FILTER_VALIDATE_INT);
if (!$lecture_id) {
    // إذا لم يتم إرسال ID، لا تفعل شيئاً وعد
    header("Location: index.php");
    exit();
}

try {
    $pdo->beginTransaction();

    // 3. التأكد من أن الطالب هو مالك المحاضرة وأنها لا تزال قيد المراجعة
    $stmt_check = $pdo->prepare("SELECT file_path FROM lectures WHERE lecture_id = ? AND uploader_user_id = ? AND status = 'pending'");
    $stmt_check->execute([$lecture_id, $user_id]);
    $file_path = $stmt_check->fetchColumn();

    if ($file_path) {
        // 4. إذا تم العثور على المحاضرة، قم بحذفها
        $stmt_delete = $pdo->prepare("DELETE FROM lectures WHERE lecture_id = ?");
        $stmt_delete->execute([$lecture_id]);

        // 5. [تحسين مهم] التأكد من أن عملية الحذف قد تمت بالفعل (أثرت على صف واحد)
        if ($stmt_delete->rowCount() > 0) {
            // 6. إذا نجح الحذف من قاعدة البيانات، قم بحذف الملف من الخادم
            if (file_exists('../../' . $file_path)) {
                unlink('../../' . $file_path);
            }
            
            $pdo->commit();
            $_SESSION['lecture_action_status'] = ['type' => 'success', 'message' => 'تم حذف المحاضرة بنجاح.'];

        } else {
            // إذا لم يتم حذف أي صف، فهذا يعني أن هناك خطأ ما
            throw new Exception("فشل حذف سجل المحاضرة من قاعدة البيانات.");
        }
        
    } else {
        // إذا لم يتم العثور على المحاضرة، فهذا يعني أنها ليست ملكه أو ليست قيد المراجعة
        throw new Exception("محاولة حذف محاضرة غير مسموح بها أو تمت مراجعتها بالفعل.");
    }

} catch (Exception $e) {
    // في حال حدوث أي خطأ، تراجع عن كل شيء
    $pdo->rollBack();
    error_log("Lecture deletion by student failed: " . $e->getMessage());
    $_SESSION['lecture_action_status'] = ['type' => 'error', 'message' => 'حدث خطأ أثناء محاولة حذف المحاضرة.'];
}

// 7. إعادة التوجيه إلى الصفحة السابقة
header("Location: index.php");
exit();
?>