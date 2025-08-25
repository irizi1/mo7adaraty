<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

$offering_id = filter_input(INPUT_POST, 'offering_id', FILTER_VALIDATE_INT);
$group_link = filter_input(INPUT_POST, 'group_link', FILTER_VALIDATE_URL);
$group_name = trim(htmlspecialchars($_POST['group_name']));

if (!$offering_id) {
    header("Location: index.php");
    exit();
}

try {
    // التحقق إذا كان هناك رابط موجود بالفعل لهذا المقرر
    $stmt_check = $pdo->prepare("SELECT link_id FROM whatsapp_groups WHERE offering_id = ?");
    $stmt_check->execute([$offering_id]);
    $existing_link = $stmt_check->fetch();

    if (empty($group_link)) {
        // إذا كان الحقل فارغًا وهناك سجل قديم، قم بحذفه
        if ($existing_link) {
            $stmt_delete = $pdo->prepare("DELETE FROM whatsapp_groups WHERE offering_id = ?");
            $stmt_delete->execute([$offering_id]);
        }
    } else {
        if ($existing_link) {
            // إذا كان هناك سجل، قم بتحديثه
            $stmt = $pdo->prepare("UPDATE whatsapp_groups SET group_link = ?, group_name = ? WHERE offering_id = ?");
            $stmt->execute([$group_link, $group_name, $offering_id]);
        } else {
            // إذا لم يكن هناك سجل، قم بإضافة سجل جديد
            $stmt = $pdo->prepare("INSERT INTO whatsapp_groups (offering_id, group_link, group_name, added_by_user_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$offering_id, $group_link, $group_name, $_SESSION['user_id']]);
        }
    }
    
    $_SESSION['whatsapp_status'] = "تم تحديث الرابط بنجاح!";

} catch (PDOException $e) {
    $_SESSION['whatsapp_status'] = "حدث خطأ: " . $e->getMessage();
}

header("Location: index.php");
exit();
?>