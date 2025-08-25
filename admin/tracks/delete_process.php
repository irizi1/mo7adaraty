<?php
session_start();
require_once '../../config/db_connexion.php';
// ... (كود الحماية والتحقق من صلاحيات الأدمن) ...

$track_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$track_id) { header("Location: index.php"); exit(); }

try {
    // تحقق أولاً إذا كان المسار مرتبطًا بفصول
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM classes WHERE track_id = ?");
    $stmt_check->execute([$track_id]);
    if ($stmt_check->fetchColumn() > 0) {
        header("Location: index.php?error=لا يمكن حذف المسار لارتباطه بفصول دراسية.");
        exit();
    }

    // إذا لم يكن مرتبطًا، قم بالحذف
    $stmt = $pdo->prepare("DELETE FROM tracks WHERE track_id = ?");
    $stmt->execute([$track_id]);
    header("Location: index.php?status=deleted");
    exit();
} catch (PDOException $e) { /* ... */ }
?>