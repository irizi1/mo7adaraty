<?php
session_start();
require_once '../../config/db_connexion.php';
// ... (كود الحماية والتحقق من صلاحيات الأدمن) ...

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $track_id = $_POST['track_id'];
    $track_name = trim($_POST['track_name']);
    $division_id = $_POST['division_id'];

    if (!empty($track_name) && !empty($division_id) && !empty($track_id)) {
        $stmt = $pdo->prepare("UPDATE tracks SET track_name = ?, division_id = ? WHERE track_id = ?");
        $stmt->execute([$track_name, $division_id, $track_id]);
    }
    header("Location: index.php?status=updated");
    exit();
}
?>