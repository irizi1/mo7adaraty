<?php
require_once 'config/db_connexion.php';

// تعريف الفصول المتقدمة التي تتطلب اختيار مسار
define('ADVANCED_CLASSES_IDS', [5, 6]);

// =================================================================
// جلب الفصول كـ Checkboxes بناءً على الشعبة المختارة
// =================================================================
if (isset($_POST["division_id_for_classes"])) {
    $division_id = filter_input(INPUT_POST, 'division_id_for_classes', FILTER_VALIDATE_INT);
    if ($division_id) {
        $stmt = $pdo->prepare("
            SELECT c.class_id, c.class_name
            FROM classes c
            JOIN tracks t ON c.track_id = t.track_id
            WHERE t.division_id = ?
            ORDER BY c.class_name
        ");
        $stmt->execute([$division_id]);

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch()) {
                // إضافة خاصية "data-advanced" لتمييز الفصول المتقدمة في الواجهة الأمامية
                $is_advanced = in_array($row['class_id'], ADVANCED_CLASSES_IDS) ? 'true' : 'false';
                echo '<div>';
                echo '<input type="checkbox" class="class-checkbox" id="class-'. $row['class_id'] .'" value="'. $row['class_id'] .'" data-class-name="'. htmlspecialchars($row['class_name']) .'" data-advanced="'. $is_advanced .'">';
                echo '<label for="class-'. $row['class_id'] .'">'. htmlspecialchars($row['class_name']) .'</label>';
                echo '</div>';
            }
        } else {
            echo '<p>لا توجد فصول متاحة لهذه الشعبة.</p>';
        }
    }
}

// =================================================================
// جلب المسارات بناءً على الفصل (فقط للفصول المتقدمة)
// =================================================================
elseif (isset($_POST["class_id_for_tracks"])) {
    $class_id = filter_input(INPUT_POST, 'class_id_for_tracks', FILTER_VALIDATE_INT);
    if ($class_id && in_array($class_id, ADVANCED_CLASSES_IDS)) {
        $stmt = $pdo->prepare("
            SELECT t.track_id, t.track_name
            FROM tracks t
            JOIN classes c ON t.track_id = c.track_id
            WHERE c.class_id = ?
            ORDER BY t.track_name
        ");
        $stmt->execute([$class_id]);
        echo '<option value="" disabled selected>-- اختر المسار --</option>';
        while ($row = $stmt->fetch()) {
            echo '<option value="' . $row['track_id'] . '">' . htmlspecialchars($row['track_name']) . '</option>';
        }
    }
}

// =================================================================
// جلب الأفواج (إما بناءً على الفصل مباشرة، أو بناءً على المسار للفصول المتقدمة)
// =================================================================
elseif (isset($_POST["class_id_for_groups"])) {
    $class_id = filter_input(INPUT_POST, 'class_id_for_groups', FILTER_VALIDATE_INT);
    $track_id = filter_input(INPUT_POST, 'track_id', FILTER_VALIDATE_INT);

    if ($class_id) {
        $sql = "SELECT g.group_id, g.group_name FROM `groups` g WHERE g.class_id = ?";
        $params = [$class_id];

        // إذا كان الفصل متقدماً، تتم فلترة الأفواج بناءً على المسار المختار أيضاً
        if ($track_id && in_array($class_id, ADVANCED_CLASSES_IDS)) {
            $sql .= " AND g.class_id IN (SELECT c.class_id FROM classes c WHERE c.track_id = ?)";
            $params[] = $track_id;
        }

        $stmt = $pdo->prepare($sql . " ORDER BY g.group_name");
        $stmt->execute($params);

        echo '<option value="" disabled selected>-- اختر الفوج --</option>';
        while ($row = $stmt->fetch()) {
            echo '<option value="' . $row['group_id'] . '">' . htmlspecialchars($row['group_name']) . '</option>';
        }
    }
}

// =================================================================
// جلب المواد مع أسماء الأساتذة بناءً على الفوج المختار
// =================================================================
elseif (!empty($_POST["group_id_for_subject"])) {
    header('Content-Type: application/json; charset=utf-8');
    $stmt = $pdo->prepare("
        SELECT s.subject_name, p.professor_name
        FROM subjects s
        JOIN group_subjects gs ON s.subject_id = gs.subject_id
        LEFT JOIN professors p ON gs.professor_id = p.professor_id
        WHERE gs.group_id = ?
    ");
    $stmt->execute([$_POST['group_id_for_subject']]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($subjects ?: []);
    exit(); // مهم: إنهاء التنفيذ هنا لأننا نرجع JSON
}


// =================================================================
// الأكواد القديمة الأخرى تبقى كما هي لضمان عمل باقي أجزاء الموقع (مثل لوحة التحكم)
// =================================================================
elseif (isset($_POST["division_id"])) {
    $stmt = $pdo->prepare("SELECT * FROM tracks WHERE division_id = ? ORDER BY track_name");
    $stmt->execute([$_POST['division_id']]);
    echo '<option value="" disabled selected>-- اختر المسار --</option>';
    while ($row = $stmt->fetch()) {
        echo '<option value="' . $row['track_id'] . '">' . htmlspecialchars($row['track_name']) . '</option>';
    }
}
?>