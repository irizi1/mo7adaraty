<?php
session_start();
require_once '../../config/db_connexion.php';
// ... (كود الحماية والتحقق من صلاحيات الأدمن) ...

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $track_name = trim($_POST['track_name']);
    $division_id = $_POST['division_id'];
    if (!empty($track_name) && !empty($division_id)) {
        $stmt = $pdo->prepare("INSERT INTO tracks (track_name, division_id) VALUES (?, ?)");
        $stmt->execute([$track_name, $division_id]);
    }
    header("Location: index.php?status=added");
    exit();
}
?>