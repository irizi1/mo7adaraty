<?php
require_once '../../config/db_connexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['track_id'])) {
    $track_id = filter_input(INPUT_POST, 'track_id', FILTER_VALIDATE_INT);
    
    if ($track_id === false || $track_id <= 0) {
        echo json_encode(['error' => 'معرف المسار غير صالح']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT class_id, class_name FROM classes WHERE track_id = ? ORDER BY class_name");
        $stmt->execute([$track_id]);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($classes)) {
            echo json_encode(['error' => 'لا توجد فصول متاحة لهذا المسار']);
        } else {
            echo json_encode($classes);
        }
    } catch (PDOException $e) {
        error_log("خطأ في get_classes.php: " . $e->getMessage());
        echo json_encode(['error' => 'حدث خطأ أثناء جلب الفصول']);
    }
} else {
    echo json_encode(['error' => 'طلب غير صالح']);
}
?>