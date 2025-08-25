<?php
require_once __DIR__ . '/config/db_connexion.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['group_ids']) || !is_array($_GET['group_ids']) || empty($_GET['group_ids'])) {
    echo json_encode(['success' => false, 'message' => 'IDs غير صحيحة.']);
    exit;
}

$groupIds = $_GET['group_ids'];

$placeholders = implode(',', array_fill(0, count($groupIds), '?'));

try {
    if (!isset($pdo)) {
        throw new PDOException("فشل الاتصال بقاعدة البيانات.");
    }

    // قم بتعديل هذا الاستعلام
    $stmt = $pdo->prepare("
        SELECT 
            s.subject_name,
            p.professor_name,
            c.class_name,
            g.group_name -- <<< أضف هذا السطر لجلب اسم الفوج
        FROM 
            subjects s
        JOIN 
            group_subjects sg ON s.subject_id = sg.subject_id
        LEFT JOIN 
            professors p ON sg.professor_id = p.professor_id
        JOIN 
            `groups` g ON sg.group_id = g.group_id -- الانضمام مع جدول الأفواج لجلب الاسم
        JOIN 
            classes c ON g.class_id = c.class_id
        WHERE 
            sg.group_id IN ($placeholders)
        ORDER BY 
            c.class_name, s.subject_name
    ");

    $stmt->execute($groupIds);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($subjects) {
        echo json_encode(['success' => true, 'subjects' => $subjects]);
    } else {
        echo json_encode(['success' => true, 'subjects' => [], 'message' => 'لا توجد مواد مسجلة لهذه الأفواج.']);
    }

} catch (PDOException $e) {
    error_log("Database Error in get_subjects_for_groups.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطأ في الاستعلام عن المواد.']);
}
?>