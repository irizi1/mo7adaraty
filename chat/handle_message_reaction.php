<?php
session_start();
require_once '../config/db_connexion.php';

// التحقق من أن المستخدم مسجل دخوله وأن الطلب صحيح
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 403 Forbidden');
    exit();
}

$user_id = $_SESSION['user_id'];
$message_id = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);
$reaction_type = $_POST['reaction_type'] ?? '';

if ($message_id && !empty($reaction_type)) {
    try {
        // التحقق إذا كان المستخدم قد تفاعل بنفس التفاعل من قبل
        $stmt_check = $pdo->prepare("SELECT reaction_id FROM message_reactions WHERE user_id = ? AND message_id = ? AND reaction_type = ?");
        $stmt_check->execute([$user_id, $message_id, $reaction_type]);
        
        if ($stmt_check->fetch()) {
            // إذا كان التفاعل موجودًا، قم بإزالته
            $stmt_delete = $pdo->prepare("DELETE FROM message_reactions WHERE user_id = ? AND message_id = ? AND reaction_type = ?");
            $stmt_delete->execute([$user_id, $message_id, $reaction_type]);
        } else {
            // إذا لم يكن موجودًا، قم بإضافته
            $stmt_insert = $pdo->prepare("INSERT INTO message_reactions (user_id, message_id, reaction_type) VALUES (?, ?, ?)");
            $stmt_insert->execute([$user_id, $message_id, $reaction_type]);
        }
        echo json_encode(['success' => true]);

    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
    }
} else {
    header('HTTP/1.1 400 Bad Request');
}
?>