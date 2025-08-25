<?php
// /admin/professors/get_data_for_professors.php
require_once '../../config/db_connexion.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';
$division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);
$offering_id = filter_input(INPUT_POST, 'offering_id', FILTER_VALIDATE_INT);

try {
    // الحالة الأولى: جلب المقررات المتاحة بناءً على الشعبة
    if ($action === 'get_offerings' && $division_id) {
        $stmt = $pdo->prepare("
            SELECT
                co.offering_id, c.class_name, g.group_name, t.track_name
            FROM course_offerings co
            JOIN classes c ON co.class_id = c.class_id
            JOIN `groups` g ON co.group_id = g.group_id
            LEFT JOIN tracks t ON co.track_id = t.track_id
            WHERE co.division_id = ?
            ORDER BY c.class_id, g.group_id
        ");
        $stmt->execute([$division_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    // الحالة الثانية: جلب المواد التي ليس لها أستاذ في مقرر معين
    elseif ($action === 'get_unassigned_subjects' && $offering_id) {
        $stmt = $pdo->prepare("
            SELECT os.offering_subject_id, s.subject_name
            FROM offering_subjects os
            JOIN subjects s ON os.subject_id = s.subject_id
            WHERE os.offering_id = ? AND os.professor_id IS NULL
            ORDER BY s.subject_name
        ");
        $stmt->execute([$offering_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    else {
        echo json_encode([]);
    }
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}
?>