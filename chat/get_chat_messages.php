<?php
session_start();
require_once '../config/db_connexion.php';

// ==========================================================
// 1. حماية الملف والتحقق من الصلاحيات
// ==========================================================
if (isset($_SESSION['user_id']) && isset($_POST['group_id'])) {
    $user_id = $_SESSION['user_id'];
    $group_id = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT);
    $last_id = filter_input(INPUT_POST, 'last_id', FILTER_VALIDATE_INT) ?? 0;

    if (!$group_id) {
        echo json_encode([]);
        exit();
    }

    // ==========================================================
    // 2. جلب الرسائل من قاعدة البيانات
    // ==========================================================
    try {
        $stmt = $pdo->prepare("
            SELECT 
                cm.message_id, cm.user_id, cm.message_text, cm.sent_at, cm.updated_at,
                cm.message_type, cm.file_path, cm.file_name,
                cm.parent_message_id,
                u.username, 
                u.profile_picture,
                parent_msg.message_text as parent_text,
                parent_user.username as parent_username
            FROM chat_messages cm 
            JOIN users u ON cm.user_id = u.user_id 
            LEFT JOIN chat_messages parent_msg ON cm.parent_message_id = parent_msg.message_id
            LEFT JOIN users parent_user ON parent_msg.user_id = parent_user.user_id
            WHERE cm.group_id = ? AND cm.message_id > ? 
            ORDER BY cm.sent_at ASC
        ");
        $stmt->execute([$group_id, $last_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $message_ids = array_column($messages, 'message_id');
        if (count($message_ids) > 0) {
            $placeholders = implode(',', array_fill(0, count($message_ids), '?'));
            $stmt_reactions = $pdo->prepare("
                SELECT message_id, reaction_type, COUNT(*) as count 
                FROM message_reactions 
                WHERE message_id IN ($placeholders)
                GROUP BY message_id, reaction_type
            ");
            $stmt_reactions->execute($message_ids);
            $reactions_data = $stmt_reactions->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

            // === [   التعديلات هنا   ] ===
            foreach ($messages as &$message) {
                // التحقق من الصورة الشخصية وتعيين الافتراضية عند الحاجة
                $message['profile_picture'] = !empty($message['profile_picture']) ? $message['profile_picture'] : 'default_profile.png';
                
                // إضافة بيانات التفاعلات ونوع الرسالة (مرسلة/مستقبلة)
                $message['reactions'] = $reactions_data[$message['message_id']] ?? [];
                $message['type'] = ($message['user_id'] == $user_id) ? 'sent' : 'received';
            }
            // =========================
        }

        // ==========================================================
        // 3. إرجاع النتيجة بصيغة JSON
        // ==========================================================
        echo json_encode($messages);

    } catch (PDOException $e) {
        error_log("Chat messages fetch failed: " . $e->getMessage());
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
?>