<?php
// /admin/ajax/get_data_for_filters.php
require_once '../../config/db_connexion.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';
$division_id = filter_input(INPUT_POST, 'division_id', FILTER_VALIDATE_INT);
$track_id = filter_input(INPUT_POST, 'track_id', FILTER_VALIDATE_INT);
$class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);

try {
    if ($action === 'get_tracks' && $division_id) {
        $stmt = $pdo->prepare("SELECT track_id, track_name FROM tracks WHERE division_id = ? ORDER BY track_name");
        $stmt->execute([$division_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    elseif ($action === 'get_classes' && $track_id) {
        $stmt = $pdo->prepare("SELECT class_id, class_name FROM classes WHERE track_id = ? ORDER BY class_name");
        $stmt->execute([$track_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    elseif ($action === 'get_groups' && $class_id) {
        $stmt = $pdo->prepare("SELECT group_id, group_name FROM `groups` WHERE class_id = ? ORDER BY group_name");
        $stmt->execute([$class_id]);
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