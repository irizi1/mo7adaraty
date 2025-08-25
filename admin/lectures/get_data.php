<?php
require_once '../../config/db_connexion.php';

/*
 * هذا الملف هو نقطة النهاية (Endpoint) لطلبات AJAX
 * الخاصة بصفحة إدارة المحاضرات في لوحة التحكم.
 */

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';

try {
    // الحالة 1: جلب المقررات المتاحة بناءً على الشعبة المختارة
    if ($action === 'get_offerings' && !empty($_POST['division_id'])) {
        $division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);
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
        $offerings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($offerings);

    // الحالة 2: جلب المواد المتاحة بناءً على المقرر المختار
    } elseif ($action === 'get_subjects' && !empty($_POST['offering_id'])) {
        $offering_id = filter_input(INPUT_POST, 'offering_id', FILTER_VALIDATE_INT);
        $stmt = $pdo->prepare("
            SELECT os.offering_subject_id, s.subject_name
            FROM offering_subjects os
            JOIN subjects s ON os.subject_id = s.subject_id
            WHERE os.offering_id = ?
            ORDER BY s.subject_name
        ");
        $stmt->execute([$offering_id]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($subjects);

    } else {
        // في حال عدم تحديد أي إجراء
        echo json_encode(['error' => 'طلب غير صالح أو缺少参数.']);
    }
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    error_log("Error in get_lecture_data.php: " . $e->getMessage());
    echo json_encode(['error' => 'حدث خطأ في قاعدة البيانات.']);
}
?>