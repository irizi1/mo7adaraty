<?php
require_once 'config/db_connexion.php';
$class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
if ($class_id) {
    $stmt = $pdo->prepare("SELECT group_id FROM chat_groups WHERE class_id = ?");
    $stmt->execute([$class_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        echo json_encode(['group_id' => $result['group_id']]);
    } else {
        echo json_encode(['error' => 'Chat group not found.']);
    }
}
?>