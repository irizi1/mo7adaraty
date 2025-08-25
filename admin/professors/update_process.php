<?php
session_start();
require_once '../../config/db_connexion.php';
// ... (كود الحماية) ...
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $professor_id = $_POST['professor_id'];
    $professor_name = trim($_POST['professor_name']);
    if (!empty($professor_name) && !empty($professor_id)) {
        $stmt = $pdo->prepare("UPDATE professors SET professor_name = ? WHERE professor_id = ?");
        $stmt->execute([$professor_name, $professor_id]);
    }
    header("Location: index.php?status=updated");
    exit();
}
?>