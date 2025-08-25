<?php
// /ajax/get_data_for_signup.php
require_once '../config/db_connexion.php';
header('Content-Type: application/json; charset=utf-8');

// هذا الملف يخدم الآن صفحة التسجيل فقط
$action = $_POST['action'] ?? '';
$division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);
$class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
$track_id = filter_input(INPUT_POST, 'track_id', FILTER_VALIDATE_INT);

try {
    if ($action === 'get_classes' && $division_id) {
        $stmt = $pdo->prepare("
            SELECT DISTINCT c.class_id, c.class_name
            FROM classes c
            JOIN course_offerings co ON c.class_id = co.class_id
            WHERE co.division_id = ? ORDER BY c.class_id
        ");
        $stmt->execute([$division_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } 
    elseif ($action === 'get_tracks' && $division_id) {
        $stmt = $pdo->prepare("SELECT track_id, track_name FROM tracks WHERE division_id = ? ORDER BY track_name");
        $stmt->execute([$division_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    elseif ($action === 'get_groups' && $class_id) {
        $sql = "SELECT co.offering_id, g.group_name 
                FROM course_offerings co
                JOIN `groups` g ON co.group_id = g.group_id
                WHERE co.class_id = ?";
        $params = [$class_id];

        if ($track_id) {
            $sql .= " AND co.track_id = ?";
            $params[] = $track_id;
        } else {
            $sql .= " AND co.track_id IS NULL";
        }
        
        $stmt = $pdo->prepare($sql . " ORDER BY g.group_name");
        $stmt->execute($params);
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