<?php
require_once 'config/db_connexion.php';

/*
 * هذا الملف هو نقطة النهاية (Endpoint) لطلبات AJAX 
 * من جميع صفحات لوحة التحكم في الهيكل الجديد.
 */

// =================================================================
// جلب المسارات المتاحة لشعبة معينة
// يُستخدم هذا عند اختيار فصل متقدم (5 أو 6) لعرض المسارات المتاحة فقط
// =================================================================
if (!empty($_POST["division_id_for_tracks"])) {
    $division_id = filter_input(INPUT_POST, 'division_id_for_tracks', FILTER_VALIDATE_INT);
    if ($division_id) {
        $stmt = $pdo->prepare("SELECT track_id, track_name FROM tracks WHERE division_id = ? ORDER BY track_name");
        $stmt->execute([$division_id]);
        
        echo '<option value="" disabled selected>-- اختر المسار --</option>';
        
        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch()) {
                echo '<option value="' . $row['track_id'] . '">' . htmlspecialchars($row['track_name']) . '</option>';
            }
        } else {
            echo '<option value="" disabled>-- لا توجد مسارات لهذه الشعبة --</option>';
        }
    }
}

// =================================================================
// جلب المقررات المتاحة بناءً على الشعبة
// يُستخدم في صفحات إدارة المواد والأساتذة
// =================================================================
elseif (!empty($_POST["division_id_for_offerings"])) {
    $division_id = filter_input(INPUT_POST, 'division_id_for_offerings', FILTER_VALIDATE_INT);
    if ($division_id) {
        $stmt = $pdo->prepare("
            SELECT 
                co.offering_id,
                c.class_name,
                g.group_name,
                t.track_name
            FROM course_offerings co
            JOIN classes c ON co.class_id = c.class_id
            JOIN `groups` g ON co.group_id = g.group_id
            LEFT JOIN tracks t ON co.track_id = t.track_id
            WHERE co.division_id = ?
            ORDER BY c.class_id, g.group_id
        ");
        $stmt->execute([$division_id]);

        echo '<option value="" disabled selected>-- اختر المقرر --</option>';
        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch()) {
                $track_info = $row['track_name'] ? ' - ' . $row['track_name'] : '';
                $display_text = $row['class_name'] . ' / ' . $row['group_name'] . $track_info;
                echo '<option value="' . $row['offering_id'] . '">' . htmlspecialchars($display_text) . '</option>';
            }
        } else {
            echo '<option value="" disabled>-- لا توجد مقررات مكونة لهذه الشعبة --</option>';
        }
    }
}

// =================================================================
// جلب المواد بناءً على المقرر المتاح (مع التحقق من السياق)
// =================================================================
elseif (!empty($_POST["offering_id_for_subjects"])) {
    $offering_id = filter_input(INPUT_POST, 'offering_id_for_subjects', FILTER_VALIDATE_INT);
    $context = $_POST['context'] ?? 'default'; // السياق: lectures أو professors

    if ($offering_id) {
        $stmt_group = $pdo->prepare("SELECT group_id FROM course_offerings WHERE offering_id = ?");
        $stmt_group->execute([$offering_id]);
        $group_id = $stmt_group->fetchColumn();
        
        if ($group_id) {
            $sql = "SELECT gs.group_subject_id, s.subject_name 
                    FROM group_subjects gs
                    JOIN subjects s ON gs.subject_id = s.subject_id
                    WHERE gs.group_id = ?";
            
            // إضافة شرط بناءً على السياق
            if ($context === 'professors') {
                $sql .= " AND gs.professor_id IS NULL";
            }

            $sql .= " ORDER BY s.subject_name";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$group_id]);

            echo '<option value="" disabled selected>-- اختر المادة --</option>';
            if ($stmt->rowCount() > 0) {
                while ($row = $stmt->fetch()) {
                    echo '<option value="' . $row['group_subject_id'] . '">' . htmlspecialchars($row['subject_name']) . '</option>';
                }
            } else {
                if ($context === 'professors') {
                    echo '<option value="" disabled>-- كل المواد لديها أستاذ --</option>';
                } else {
                    echo '<option value="" disabled>-- لا توجد مواد مرتبطة بهذا المقرر --</option>';
                }
            }
        }
    }
}
?>