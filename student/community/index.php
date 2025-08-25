<?php
session_start();
require_once '../../config/db_connexion.php';

// حماية الصفحة
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// جلب المنشورات
try {
    $stmt = $pdo->prepare("
        SELECT 
            p.post_id, p.post_text, p.image_path, p.created_at, p.background_style,
            u.username, u.profile_picture,
            (SELECT COUNT(*) FROM content_reactions WHERE content_id = p.post_id AND content_type = 'post' AND reaction_type = 'like') as likes_count,
            (SELECT COUNT(*) FROM comments WHERE content_id = p.post_id AND content_type = 'post') as comments_count,
            (SELECT reaction_type FROM content_reactions WHERE content_id = p.post_id AND content_type = 'post' AND user_id = :user_id) as user_reaction
        FROM posts p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.status = 'approved'
        ORDER BY p.created_at DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في جلب المنشورات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ساحة المشاركات - Mo7adaraty</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/comments.css">
    <style>
        :root {
            --brand-color: #007bff; --bg-color: #f0f2f5; --card-bg: #ffffff;
            --border-color: #e0e0e0; --text-primary: #1c1e21; --text-secondary: #65676b;
            --green-color: #42b72a; --red-color: #fa383e; --warning-color: #f7b928;
            --bg-blue: #E7F3FF; --bg-red: #FFE5E5; --bg-green: #E5FFE5; --bg-yellow: #FFF9E5; --bg-purple: #F3E7FF;
        }
        body { font-family: 'Tajawal', sans-serif; background: var(--bg-color); margin: 0; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; }
        h1 { text-align: center; color: var(--text-primary); margin-bottom: 25px; }
        
        /* Create Post Card */
        .create-post-card { background: var(--card-bg); border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 25px; }
        .create-post-card h3 { margin: 0 0 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; }
        .create-post-card textarea { width: 100%; border: none; padding: 15px; font-size: 18px; resize: vertical; min-height: 80px; background-color: #f0f2f5; border-radius: 8px; box-sizing: border-box;}
        .create-post-card textarea:focus { outline: none; }
        .form-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; }
        .file-label { background: #e7f3ff; color: var(--brand-color); padding: 8px 15px; border-radius: 20px; cursor: pointer; font-weight: 600; transition: background .2s; }
        .file-label:hover { background: #d0e7ff; }
        .file-label input[type="file"] { display: none; }
        .form-actions button { background: var(--brand-color); color: white; border: none; padding: 8px 25px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: background .2s; }
        .form-actions button:hover { background: #0056b3; }
        #image-preview { font-size: 14px; color: var(--text-secondary); margin-top: 10px; }
        .color-selector { margin-top: 10px; display: flex; gap: 10px; align-items: center; }
        .color-option { width: 30px; height: 30px; border-radius: 50%; cursor: pointer; border: 2px solid transparent; transition: border .2s; }
        .color-option.selected { border: 2px solid var(--text-primary); }
        .color-option[data-style="none"] { background: #f0f2f5; }
        .color-option[data-style="blue"] { background: var(--bg-blue); }
        .color-option[data-style="red"] { background: var(--bg-red); }
        .color-option[data-style="green"] { background: var(--bg-green); }
        .color-option[data-style="yellow"] { background: var(--bg-yellow); }
        .color-option[data-style="purple"] { background: var(--bg-purple); }

        /* Posts Feed */
        .posts-feed { }
        .post-card { background: var(--card-bg); border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .post-header { display: flex; align-items: center; padding: 15px; }
        .post-header img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; margin-left: 10px; }
        .post-author-info .author-name { font-weight: bold; color: var(--text-primary); }
        .post-author-info .post-time { font-size: 13px; color: var(--text-secondary); }
        .post-body { padding: 0 15px 15px; }
        .post-text { white-space: pre-wrap; margin-bottom: 10px; line-height: 1.6; padding: 15px; border-radius: 8px; }
        .post-text.bg-blue { background: var(--bg-blue); }
        .post-text.bg-red { background: var(--bg-red); color: #1c2526; }
        .post-text.bg-green { background: var(--bg-green); }
        .post-text.bg-yellow { background: var(--bg-yellow); }
        .post-text.bg-purple { background: var(--bg-purple); }
        .post-text.bg-blue, .post-text.bg-red, .post-text.bg-green, .post-text.bg-yellow, .post-text.bg-purple {
            text-align: center;
            font-weight: bold;
            font-size: 18px;
            line-height: 1.8;
        }
        .post-image img { max-width: 100%; border-radius: 8px; margin-top: 10px; }
        .post-footer { display: flex; justify-content: space-around; border-top: 1px solid var(--border-color); padding: 5px; }
        .footer-action { flex: 1; text-align: center; padding: 10px; border-radius: 8px; cursor: pointer; transition: background .2s; font-weight: 600; color: var(--text-secondary); }
        .footer-action:hover { background: #f0f2f5; }
        .footer-action.liked { color: var(--brand-color); font-weight: bold; }
        .comments-section { padding: 15px; border-top: 1px solid var(--border-color); }
        
        .modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px 30px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; position: relative; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-bottom: 20px; }
        .modal-header h2 { margin: 0; font-size: 20px; }
        .close-btn { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        .modal-body .form-group label { font-weight: bold; }
        .modal-body .form-group textarea { width: 100%; min-height: 100px; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .modal-body button { width: 100%; }
        .error-message, .success-message { text-align: center; padding: 10px; border-radius: 8px; margin-bottom: 15px; }
        .error-message { color: var(--red-color); background: #ffe5e5; }
        .success-message { color: var(--green-color); background: #e5ffe5; }
    </style>
</head>
<body>
    <?php require_once '../../templates/header.php'; ?>

    <div class="container">
        <?php if (isset($_GET['error'])): ?>
            <p class="error-message">
                <?php
                $errors = [
                    'empty_post' => 'يرجى إدخال نص أو رفع صورة.',
                    'background_with_image_not_allowed' => 'لا يمكن اختيار خلفية مع صورة.',
                    'invalid_image' => 'نوع الصورة غير مدعوم أو الحجم كبير جدًا (الحد الأقصى 5 ميجابايت).',
                    'upload_failed' => 'فشل رفع الصورة، حاول مرة أخرى.',
                    'background_without_text' => 'لا يمكن اختيار خلفية بدون نص.',
                    'db_error' => 'خطأ في قاعدة البيانات: ' . htmlspecialchars($_GET['message'] ?? ''),
                ];
                echo $errors[$_GET['error']] ?? 'خطأ غير معروف.';
                ?>
            </p>
        <?php elseif (isset($_GET['status']) && $_GET['status'] === 'post_submitted'): ?>
            <p class="success-message">
                تم إرسال المنشور بنجاح، في انتظار الموافقة.
            </p>
        <?php endif; ?>

        <h1><i class="fa-solid fa-users"></i> ساحة المشاركات</h1>

        <div class="create-post-card">
            <h3>إنشاء منشور جديد</h3>
            <form action="process_post.php" method="POST" enctype="multipart/form-data">
                <textarea name="post_text" placeholder="بماذا تفكر يا <?php echo htmlspecialchars($_SESSION['username']); ?>؟"></textarea>
                <div id="image-preview"></div>
                <div class="color-selector">
                    <span>اختر لون الخلفية:</span>
                    <div class="color-option selected" data-style="none" onclick="selectStyle('none')"></div>
                    <div class="color-option" data-style="blue" onclick="selectStyle('blue')"></div>
                    <div class="color-option" data-style="red" onclick="selectStyle('red')"></div>
                    <div class="color-option" data-style="green" onclick="selectStyle('green')"></div>
                    <div class="color-option" data-style="yellow" onclick="selectStyle('yellow')"></div>
                    <div class="color-option" data-style="purple" onclick="selectStyle('purple')"></div>
                    <input type="hidden" name="background_style" id="background_style" value="none">
                </div>
                <div class="form-actions">
                    <label for="post_image" class="file-label">
                        <i class="fa-solid fa-image"></i> إضافة صورة
                        <input type="file" id="post_image" name="post_image" accept="image/*" onchange="updateFileName(this)">
                    </label>
                    <button type="submit"><i class="fa-solid fa-paper-plane"></i> نشر</button>
                </div>
            </form>
        </div>

        <div class="posts-feed">
            <?php if (empty($posts)): ?>
                <p style="text-align:center; color: var(--text-secondary); padding: 20px; background: var(--card-bg); border-radius: 12px;">لا توجد منشورات لعرضها حالياً. كن أول من ينشر!</p>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post-card" id="post-<?php echo $post['post_id']; ?>">
                        <div class="post-header">
                            <img src="../../Uploads/profile_pictures/<?php echo htmlspecialchars($post['profile_picture'] ?: 'default_profile.png'); ?>" alt="صورة المستخدم" onerror="this.onerror=null;this.src='../../Uploads/profile_pictures/default_profile.png';">
                            <div class="post-author-info">
                                <span class="author-name"><?php echo htmlspecialchars($post['username']); ?></span>
                                <div class="post-time"><?php echo date('d M Y, H:i', strtotime($post['created_at'])); ?></div>
                            </div>
                        </div>
                        <div class="post-body">
                            <?php if (!empty($post['post_text'])): ?>
                                <p class="post-text <?php echo !empty($post['background_style']) && $post['background_style'] !== 'none' ? 'bg-' . HTMLSpecialChars($post['background_style']) : ''; ?>">
                                    <?php echo nl2br(htmlspecialchars($post['post_text'])); ?>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($post['image_path'])): ?>
                                <div class="post-image">
                                    <img src="../../<?php echo htmlspecialchars($post['image_path']); ?>" alt="صورة المنشور">
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="post-footer">
                            <div class="footer-action like-btn <?php echo $post['user_reaction'] === 'like' ? 'liked' : ''; ?>" data-content-id="<?php echo $post['post_id']; ?>" data-content-type="post">
                                <i class="fa-solid fa-thumbs-up"></i> إعجاب (<span class="likes-count"><?php echo $post['likes_count']; ?></span>)
                            </div>
                            <div class="footer-action comment-toggle-btn">
                                <i class="fa-solid fa-comment"></i> تعليق (<span class="comments-count"><?php echo $post['comments_count']; ?></span>)
                            </div>
                            <div class="footer-action report-btn" data-content-id="<?php echo $post['post_id']; ?>" data-content-type="post">
                                <i class="fa-solid fa-flag"></i> إبلاغ
                            </div>
                        </div>
                        <div class="comments-section" style="display: none;">
                            <div class="comments-container" data-content-id="<?php echo $post['post_id']; ?>" data-content-type="post"></div>
                            <form class="comment-form" action="add_comment.php" method="POST">
                                <input type="hidden" name="content_id" value="<?php echo $post['post_id']; ?>">
                                <input type="hidden" name="content_type" value="post">
                                <textarea name="comment_text" placeholder="اكتب تعليقك هنا..." required></textarea>
                                <button type="submit"><i class="fa-solid fa-paper-plane"></i></button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div id="reportModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>الإبلاغ عن منشور</h2>
                <span class="close-btn">&times;</span>
            </div>
            <div class="modal-body">
                <form id="reportForm" action="submit_report.php" method="POST">
                    <input type="hidden" name="content_id" id="report_content_id">
                    <input type="hidden" name="content_type" id="report_content_type" value="post">
                    <div class="form-group">
                        <label for="reason">سبب البلاغ:</label>
                        <textarea id="reason" name="reason" placeholder="يرجى توضيح سبب الإبلاغ..." required></textarea>
                    </div>
                    <button type="submit" class="main-button" style="background-color: var(--brand-color);"><i class="fa-solid fa-paper-plane"></i> إرسال البلاغ</button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="community_script.js"></script>
    <script>
        function updateFileName(input) {
            const preview = document.getElementById('image-preview');
            if (input.files.length > 0) {
                preview.textContent = 'تم اختيار الصورة: ' + input.files[0].name;
            } else {
                preview.textContent = '';
            }
        }

        function selectStyle(style) {
            const colorOptions = document.querySelectorAll('.color-option');
            colorOptions.forEach(option => option.classList.remove('selected'));
            document.querySelector(`.color-option[data-style="${style}"]`).classList.add('selected');
            document.getElementById('background_style').value = style;
            const textarea = document.querySelector('.create-post-card textarea');
            textarea.className = '';
            textarea.style.textAlign = style !== 'none' ? 'center' : 'right';
            textarea.style.fontWeight = style !== 'none' ? 'bold' : 'normal';
            textarea.style.fontSize = style !== 'none' ? '18px' : '18px';
            if (style !== 'none') {
                textarea.classList.add('bg-' + style);
            } else {
                textarea.style.backgroundColor = '#f0f2f5';
            }
        }
    </script>
</body>
</html>