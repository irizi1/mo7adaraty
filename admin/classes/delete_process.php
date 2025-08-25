<?php
session_start();
require_once '../../config/db_connexion.php';
// ... (كود الحماية والتحقق من صلاحيات الأدمن) ...

$class_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$class_id) { header("Location: index.php"); exit(); }

try {
    // تحقق أولاً إذا كان الفصل مرتبطًا بأفواج
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM `groups` WHERE class_id = ?");
    $stmt_check->execute([$class_id]);
    if ($stmt_check->fetchColumn() > 0) {
        header("Location: index.php?error=لا يمكن حذف الفصل لارتباطه بأفواج.");
        exit();
    }
    // ملاحظة: يجب إضافة المزيد من عمليات التحقق هنا (مثل المحاضرات، الامتحانات، إلخ)
    
    // إذا لم يكن مرتبطًا، قم بالحذف
    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM chat_groups WHERE class_id = ?")->execute([$class_id]);
    $pdo->prepare("DELETE FROM classes WHERE class_id = ?")->execute([$class_id]);
    $pdo->commit();

    header("Location: index.php?status=deleted");
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: index.php?error=db_error_deleting");
    exit();
}
?>