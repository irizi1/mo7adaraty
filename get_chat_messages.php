<?php
session_start();
require_once 'config/db_connexion.php';
if (isset($_SESSION['user_id']) && isset($_POST['group_id'])) {
    $user_id = $_SESSION['user_id'];
    $group_id = $_POST['group_id'];
    $last_id = $_POST['last_id'] ?? 0;

    $stmt = $pdo->prepare("
        SELECT cm.*, u.username 
        FROM chat_messages cm 
        JOIN users u ON cm.user_id = u.user_id 
        WHERE cm.group_id = ? AND cm.message_id > ? 
        ORDER BY cm.sent_at ASC
    ");
    $stmt->execute([$group_id, $last_id]);
    $messages = $stmt->fetchAll();

    foreach ($messages as &$message) {
        $message['type'] = ($message['user_id'] == $user_id) ? 'sent' : 'received';
    }

    echo json_encode($messages);
}
?>