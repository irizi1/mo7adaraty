<?php
require_once 'config/db_connexion.php';

header('Content-Type: application/json; charset=utf-8');

$class_id = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['class_id'])) {
    $class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['class_id'])) {
    $class_id = filter_input(INPUT_GET, 'class_id', FILTER_VALIDATE_INT);
}

if ($class_id === false || $class_id <= 0) {
    echo json_encode(['error' => 'معرف الفصل غير صالح']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT group_id, group_name FROM `groups` WHERE class_id = ? ORDER BY group_name");
    $stmt->execute([$class_id]);
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($groups)) {
        echo json_encode(['error' => 'لا توجد أفواج متاحة لهذا الفصل']);
    } else {
        echo json_encode($groups);
    }
} catch (PDOException $e) {
    error_log("خطأ في get_groups.php: " . $e->getMessage());
    echo json_encode(['error' => 'حدث خطأ أثناء جلب الأفواج']);
}
?>