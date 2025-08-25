<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

try {
    // جلب جميع الرسائل السابقة لهذا الطالب
    $stmt = $pdo->prepare("SELECT * FROM admin_messages WHERE student_user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في جلب الرسائل: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
           <link rel="icon" type="image/png" href="/mo7adaraty/assets/images/favicon.png">
    <title>التواصل مع الإدارة - Mo7adaraty</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --brand-color: #007bff;
            --card-bg: #ffffff;
            --border-color: #e9ecef;
            --bg-color: #f4f8fc;
            --text-secondary: #6c757d;
        }
        .page-container { max-width: 800px; margin: 20px auto; padding: 20px; }
        .page-header h1 {
            text-align: center;
            color: var(--brand-color);
            margin-bottom: 10px;
        }
        .page-header p {
            text-align: center;
            margin-bottom: 30px;
        }
        .page-header a {
            color: var(--brand-color);
            font-weight: 600;
            text-decoration: none;
        }

        .card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.07);
            margin-bottom: 25px;
            overflow: hidden;
        }
        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            background-color: #f8f9fa;
        }
        .card-header h3 { margin: 0; font-size: 18px; }
        .card-body { padding: 25px; }

        .message-thread {
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .message-thread:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .thread-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .thread-header h4 { margin: 0; font-size: 16px; }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending { background-color: #fffbeb; color: #b45309; }
        .status-replied { background-color: #f0fdf4; color: #15803d; }
        
        .message-bubble {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 10px;
        }
        .message-bubble strong {
            display: block;
            margin-bottom: 5px;
        }
        .student-message {
            background-color: #e7f1ff;
            border-right: 4px solid var(--brand-color);
        }
        .admin-reply {
            background-color: #f0fdf4;
            border-right: 4px solid #28a745;
        }
        .message-time {
            font-size: 12px;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
<?php require_once '../../templates/header.php'; ?>
<div class="page-container">
    <div class="page-header">
        <h1><i class="fa-solid fa-envelope"></i> التواصل مع الإدارة</h1>
        <p><a href="../profil.php">&larr; العودة إلى الملف الشخصي</a></p>
    </div>

    <?php if (isset($_SESSION['contact_msg'])): ?>
        <p class="status-message <?php echo $_SESSION['contact_msg']['type']; ?>">
            <?php echo $_SESSION['contact_msg']['message']; ?>
        </p>
        <?php unset($_SESSION['contact_msg']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-header"><h3>إرسال رسالة جديدة</h3></div>
        <div class="card-body">
            <form action="process.php" method="POST">
                <div class="form-group">
                    <label for="message_subject">الموضوع:</label>
                    <input type="text" id="message_subject" name="message_subject" required>
                </div>
                <div class="form-group">
                    <label for="message_content">نص الرسالة:</label>
                    <textarea id="message_content" name="message_content" rows="5" required></textarea>
                </div>
                <button type="submit"><i class="fa-solid fa-paper-plane"></i> إرسال</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>سجل الرسائل</h3></div>
        <div class="card-body">
            <?php if (empty($messages)): ?>
                <p style="text-align: center;">لا توجد رسائل سابقة لعرضها.</p>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="message-thread">
                        <div class="thread-header">
                            <h4><?php echo htmlspecialchars($msg['message_subject']); ?></h4>
                            <?php if ($msg['status'] === 'replied'): ?>
                                <span class="status-badge status-replied"><i class="fa-solid fa-check-circle"></i> تم الرد</span>
                            <?php else: ?>
                                <span class="status-badge status-pending"><i class="fa-solid fa-clock"></i> بانتظار الرد</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="message-bubble student-message">
                            <strong><i class="fa-solid fa-user"></i> رسالتك:</strong>
                            <p><?php echo nl2br(htmlspecialchars($msg['message_content'])); ?></p>
                            <small class="message-time"><?php echo date('Y-m-d H:i', strtotime($msg['created_at'])); ?></small>
                        </div>

                        <?php if ($msg['admin_reply']): ?>
                            <div class="message-bubble admin-reply">
                                <strong><i class="fa-solid fa-user-shield"></i> رد الإدارة:</strong>
                                <p><?php echo nl2br(htmlspecialchars($msg['admin_reply'])); ?></p>
                                <small class="message-time"><?php echo date('Y-m-d H:i', strtotime($msg['replied_at'])); ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>