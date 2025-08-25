<?php
session_start();
require_once '../../config/db_connexion.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

$division_name = trim($_POST['division_name']);
if (!empty($division_name)) {
    try {
        $stmt = $pdo->prepare("INSERT INTO divisions (division_name) VALUES (?)");
        $stmt->execute([$division_name]);
    } catch (PDOException $e) {
        header("Location: index.php?error=حدث خطأ ما");
        exit();
    }
}
header("Location: index.php?status=added");
exit();
?>