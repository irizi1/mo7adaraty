<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php"); exit();
}

$division_id = $_POST['division_id'];
$division_name = trim($_POST['division_name']);

if (!empty($division_name) && !empty($division_id)) {
    try {
        $stmt = $pdo->prepare("UPDATE divisions SET division_name = ? WHERE division_id = ?");
        $stmt->execute([$division_name, $division_id]);
    } catch (PDOException $e) { /* ... handle error ... */ }
}
header("Location: index.php?status=updated");
exit();
?>