<?php
require_once '../../config/db_connexion.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['group_id'])) {
    echo json_encode(['error' => 'طلب غير صالح']);
    exit;
}

$group_id = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT);

if ($group_id === false || $group_id <= 0) {
    echo json_encode(['error' => 'معرف الفوج غير صالح']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT s.subject_id, s.subject_name 
        FROM group_subjects gs 
        JOIN subjects s ON gs.subject_id = s.subject_id 
        WHERE gs.group_id = ? 
        ORDER BY s.subject_name
    ");
    $stmt->execute([$group_id]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($subjects)) {
        echo json_encode(['error' => 'لا توجد مواد متاحة لهذا الفوج']);
    } else {
        echo json_encode($subjects);
    }
} catch (PDOException $e) {
    error_log("خطأ في get_subjects.php: " . $e->getMessage());
    echo json_encode(['error' => 'حدث خطأ أثناء جلب المواد']);
}
?>