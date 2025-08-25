<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../login.php");
    exit();
}

// استقبال البيانات والتحقق منها
$user_id = $_POST['user_id'];
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$role = $_POST['role'];

if (empty($username) || empty($email) || empty($role) || empty($user_id)) {
    header("Location: index.php?error=البيانات غير كاملة");
    exit();
}

// تحديث البيانات في قاعدة البيانات
try {
    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE user_id = ?");
    $stmt->execute([$username, $email, $role, $user_id]);
    header("Location: index.php?status=updated");
    exit();
} catch (PDOException $e) {
    header("Location: index.php?error=حدث خطأ ما");
    exit();
}
?>